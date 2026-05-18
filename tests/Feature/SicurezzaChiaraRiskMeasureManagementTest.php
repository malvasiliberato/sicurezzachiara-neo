<?php

use App\Actions\Tenant\CreateTenantWorkspace;
use App\Models\Company;
use App\Models\CompanySite;
use App\Models\EquipmentAsset;
use App\Models\EquipmentType;
use App\Models\JobRole;
use App\Models\RiskCatalogItem;
use App\Models\RiskMeasure;
use App\Models\RiskProfileItem;
use App\Models\RiskSourceLink;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerEquipmentExposure;
use App\Models\WorkerJobRoleAssignment;
use App\Support\RiskExpectedMeasureResolver;
use App\Support\RiskProfileBuilder;
use Database\Seeders\SicurezzaChiaraCoreEquipmentTypesSeeder;
use Database\Seeders\SicurezzaChiaraCoreJobRolesSeeder;
use Database\Seeders\SicurezzaChiaraCoreRiskCategoriesSeeder;
use Database\Seeders\SicurezzaChiaraCoreRisksSeeder;
use Database\Seeders\SicurezzaChiaraCoreWorkplaceTypesSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function createWorkerRiskProfileFixture(): array
{
    test()->seed([
        SicurezzaChiaraCoreJobRolesSeeder::class,
        SicurezzaChiaraCoreEquipmentTypesSeeder::class,
        SicurezzaChiaraCoreWorkplaceTypesSeeder::class,
        SicurezzaChiaraCoreRiskCategoriesSeeder::class,
        SicurezzaChiaraCoreRisksSeeder::class,
    ]);

    $user = User::factory()->create();
    $tenant = app(CreateTenantWorkspace::class)->handle($user, 'Studio Test');

    $company = Company::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Metalnova S.r.l.',
    ]);

    $site = CompanySite::query()->create([
        'company_id' => $company->id,
        'name' => 'Stabilimento principale',
        'is_headquarters' => true,
    ]);

    $worker = Worker::query()->create([
        'company_id' => $company->id,
        'primary_site_id' => $site->id,
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'status' => 'active',
    ]);

    $jobRole = JobRole::query()->where('name', 'Operatore di produzione')->firstOrFail();
    WorkerJobRoleAssignment::query()->create([
        'worker_id' => $worker->id,
        'job_role_id' => $jobRole->id,
        'is_primary' => true,
    ]);

    $risk = RiskCatalogItem::query()->where('name', 'Schiacciamento e cesoiamento')->firstOrFail();

    RiskSourceLink::query()->create([
        'risk_catalog_item_id' => $risk->id,
        'sourceable_type' => JobRole::class,
        'sourceable_id' => $jobRole->id,
        'relevance' => RiskSourceLink::RELEVANCE_PRIMARY,
    ]);

    app(RiskProfileBuilder::class)->rebuildWorker($worker);

    $profileItem = RiskProfileItem::query()
        ->where('profileable_type', Worker::class)
        ->where('profileable_id', $worker->id)
        ->where('risk_catalog_item_id', $risk->id)
        ->firstOrFail();

    return compact('user', 'tenant', 'company', 'site', 'worker', 'risk', 'profileItem');
}

test('users can attach implemented and pending measures to a risk profile item', function () {
    ['user' => $user, 'company' => $company, 'worker' => $worker, 'risk' => $risk, 'profileItem' => $profileItem] = createWorkerRiskProfileFixture();

    $risk->update([
        'expected_measures' => [
            [
                'code' => 'protections',
                'family' => RiskMeasure::FAMILY_TECHNICAL,
                'title' => 'Protezioni macchina',
                'description' => 'Presidio tecnico atteso.',
                'is_required' => true,
            ],
            [
                'code' => 'training_specific',
                'family' => RiskMeasure::FAMILY_TRAINING,
                'title' => 'Addestramento specifico',
                'description' => 'Presidio formativo atteso.',
                'is_required' => true,
            ],
        ],
    ]);

    $this->actingAs($user)
        ->post(route('workers.risk-profile.measures.store', [$worker, $profileItem]), [
            'family' => RiskMeasure::FAMILY_TECHNICAL,
            'expected_measure_code' => 'protections',
            'title' => 'Ripristino protezioni area di taglio',
            'description' => 'Presidio tecnico iniziale.',
            'status' => RiskMeasure::STATUS_IMPLEMENTED,
            'details' => [
                'owner' => 'Responsabile manutenzione',
                'verification_method' => 'Checklist tecnica settimanale',
            ],
            'due_date' => '2026-05-10',
            'notes' => 'Completata la verifica iniziale.',
        ])
        ->assertRedirect(route('workers.risk-profile.measures.show', [$worker, $profileItem]));

    $measure = RiskMeasure::query()->where('title', 'Ripristino protezioni area di taglio')->firstOrFail();

    expect($measure->risk_catalog_item_id)->toBe($risk->id)
        ->and($measure->profileable_type)->toBe(Worker::class)
        ->and($measure->expected_measure_code)->toBe('protections')
        ->and($measure->details['owner'])->toBe('Responsabile manutenzione')
        ->and($measure->completed_at)->not->toBeNull()
        ->and($profileItem->fresh()->status)->toBe(RiskProfileItem::STATUS_UNCOVERED);

    $this->post(route('workers.risk-profile.measures.store', [$worker, $profileItem]), [
        'family' => RiskMeasure::FAMILY_TRAINING,
        'expected_measure_code' => 'training_specific',
        'title' => 'Addestramento supplementare operatore',
        'description' => 'Presidio formativo aperto.',
        'status' => RiskMeasure::STATUS_IMPLEMENTED,
        'details' => [
            'provider' => 'Studio Test Formazione',
            'delivery_mode' => 'Aula',
            'valid_until' => '2027-05-20',
        ],
        'due_date' => '2026-05-20',
        'notes' => 'Completata.',
    ])->assertRedirect(route('workers.risk-profile.measures.show', [$worker, $profileItem]));

    expect($profileItem->fresh()->status)->toBe(RiskProfileItem::STATUS_COVERED);

    $this->get(route('workers.risk-profile.measures.show', [$worker, $profileItem]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sicurezzachiara/risk-measures/Manage')
            ->where('measureBridge.actions.reviewRoute', route('workers.risk-profile.review.show', [$worker, $profileItem]))
            ->where('measureBridge.actions.workerRoute', route('workers.show', $worker))
            ->where('measureBridge.actions.companyRoute', route('companies.show', $company))
            ->where('measureBridge.actions.workspaceRoute', route('measure-registries.index', [
                'company_id' => $company->id,
                'origin' => 'risk_measures',
                'focus' => 'reviews',
                'scope' => 'attention',
            ]))
            ->where('measureBridge.stats.pendingMeasures', 0)
            ->where('measureBridge.stats.gapCount', 0)
            ->where('measureBridge.decision.laneLabel', 'Corsia review')
            ->where('measureBridge.decision.tone', 'success')
            ->where('measureBridge.operationalQueue.0.laneLabel', 'Corsia copertura')
            ->where('measureBridge.operationalQueue.1.laneLabel', 'Corsia misure')
            ->where('measureBridge.operationalQueue.2.laneLabel', 'Corsia review')
            ->where('copy.linkedMeasuresHelper', 'Modello unico MVP delle misure collegate al rischio. DPI, formazione, visite mediche e presidi tecnico-organizzativi vivono qui come famiglie operative dello stesso workspace.')
            ->where('copy.newMeasureHelper', 'Inserisci una misura collegata al rischio. Le famiglie DPI, formazione e visite restano parte dello stesso workspace operativo.')
        )
        ->assertSee('expectedMeasures', false)
        ->assertSee('measureBridge', false)
        ->assertSee('training_specific', false);
});

test('updating and removing company measures keeps risk coverage aligned', function () {
    ['user' => $user, 'company' => $company, 'risk' => $risk] = createWorkerRiskProfileFixture();

    $equipmentType = EquipmentType::query()->where('name', 'Carrello elevatore')->firstOrFail();
    $equipmentAsset = EquipmentAsset::query()->create([
        'company_id' => $company->id,
        'company_site_id' => CompanySite::query()->where('company_id', $company->id)->firstOrFail()->id,
        'equipment_type_id' => $equipmentType->id,
        'name' => 'Muletto reparto A',
        'status' => 'active',
    ]);

    WorkerEquipmentExposure::query()->create([
        'worker_id' => Worker::query()->where('company_id', $company->id)->firstOrFail()->id,
        'equipment_asset_id' => $equipmentAsset->id,
        'is_primary' => true,
    ]);

    $trafficRisk = RiskCatalogItem::query()->where('name', 'Circolazione mezzi e interferenze')->firstOrFail();
    $trafficRisk->update([
        'expected_measures' => [
            [
                'code' => 'routes',
                'family' => RiskMeasure::FAMILY_ORGANIZATIONAL,
                'title' => 'Percorsi separati mezzi e pedoni',
                'description' => 'Presidio viabilita\' interna.',
                'is_required' => true,
            ],
        ],
    ]);

    RiskSourceLink::query()->updateOrCreate([
        'risk_catalog_item_id' => $trafficRisk->id,
        'sourceable_type' => EquipmentType::class,
        'sourceable_id' => $equipmentType->id,
    ], [
        'relevance' => RiskSourceLink::RELEVANCE_PRIMARY,
    ]);

    app(RiskProfileBuilder::class)->rebuildCompany($company);

    $companyProfileItem = RiskProfileItem::query()
        ->where('profileable_type', Company::class)
        ->where('profileable_id', $company->id)
        ->where('risk_catalog_item_id', $trafficRisk->id)
        ->firstOrFail();

    $this->actingAs($user)
        ->post(route('companies.risk-profile.measures.store', [$company, $companyProfileItem]), [
            'family' => RiskMeasure::FAMILY_ORGANIZATIONAL,
            'expected_measure_code' => 'routes',
            'title' => 'Percorsi separati mezzi e pedoni',
            'description' => 'Presidio di viabilita\' interna.',
            'status' => RiskMeasure::STATUS_IMPLEMENTED,
            'due_date' => '2026-05-18',
            'notes' => 'Segnaletica completata.',
        ])
        ->assertRedirect(route('companies.risk-profile.measures.show', [$company, $companyProfileItem]));

    $measure = RiskMeasure::query()->where('title', 'Percorsi separati mezzi e pedoni')->firstOrFail();

    expect($companyProfileItem->fresh()->status)->toBe(RiskProfileItem::STATUS_COVERED);

    $this->put(route('companies.risk-profile.measures.update', [$company, $companyProfileItem, $measure]), [
        'family' => $measure->family,
        'expected_measure_code' => $measure->expected_measure_code,
        'title' => $measure->title,
        'description' => $measure->description,
        'status' => RiskMeasure::STATUS_TO_VERIFY,
        'due_date' => '2026-05-18',
        'notes' => 'Da ricontrollare.',
    ])->assertRedirect(route('companies.risk-profile.measures.show', [$company, $companyProfileItem]));

    expect($companyProfileItem->fresh()->status)->toBe(RiskProfileItem::STATUS_UNCOVERED);

    $this->delete(route('companies.risk-profile.measures.destroy', [$company, $companyProfileItem, $measure]))
        ->assertRedirect(route('companies.risk-profile.measures.show', [$company, $companyProfileItem]));

    expect(RiskMeasure::query()->whereKey($measure->id)->exists())->toBeFalse();
});

test('users cannot manage risk measures outside their tenant perimeter', function () {
    ['worker' => $worker, 'profileItem' => $profileItem] = createWorkerRiskProfileFixture();

    $otherUser = User::factory()->create();
    app(CreateTenantWorkspace::class)->handle($otherUser, 'Studio Due');

    $this->actingAs($otherUser)
        ->post(route('workers.risk-profile.measures.store', [$worker, $profileItem]), [
            'family' => RiskMeasure::FAMILY_TECHNICAL,
            'title' => 'Presidio non consentito',
            'status' => RiskMeasure::STATUS_IMPLEMENTED,
        ])
        ->assertNotFound();
});

test('implemented free measures can cover an expected measure through family substitution when enabled', function () {
    ['user' => $user, 'worker' => $worker, 'risk' => $risk, 'profileItem' => $profileItem] = createWorkerRiskProfileFixture();

    $risk->update([
        'expected_measures' => [
            [
                'code' => 'training_specific',
                'family' => RiskMeasure::FAMILY_TRAINING,
                'title' => 'Addestramento specifico',
                'description' => 'Presidio formativo atteso.',
                'is_required' => true,
                'allows_family_substitution' => true,
            ],
        ],
    ]);

    $this->actingAs($user)
        ->post(route('workers.risk-profile.measures.store', [$worker, $profileItem]), [
            'family' => RiskMeasure::FAMILY_TRAINING,
            'title' => 'Sessione pratica su procedure sicure di linea',
            'description' => 'Misura registrata come presidio reale senza aggancio rigido al codice.',
            'status' => RiskMeasure::STATUS_IMPLEMENTED,
            'details' => [
                'provider' => 'Studio Test Formazione',
                'delivery_mode' => 'Affiancamento',
                'valid_until' => '2027-05-20',
            ],
            'due_date' => '2026-05-20',
            'notes' => 'Completata in reparto.',
        ])
        ->assertRedirect(route('workers.risk-profile.measures.show', [$worker, $profileItem]));

    expect($profileItem->fresh()->status)->toBe(RiskProfileItem::STATUS_COVERED);

    $snapshot = app(RiskExpectedMeasureResolver::class)->snapshotForRisk(
        $risk->fresh(),
        RiskMeasure::query()
            ->where('profileable_type', Worker::class)
            ->where('profileable_id', $worker->id)
            ->where('risk_catalog_item_id', $risk->id)
            ->get(),
    );

    expect($snapshot['summary']['substituted_count'])->toBe(1)
        ->and($snapshot['templates'][0]['coverage_mode'])->toBe('family_substitution')
        ->and($snapshot['templates'][0]['status'])->toBe('covered');
});
