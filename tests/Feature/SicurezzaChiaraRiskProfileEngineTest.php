<?php

use App\Actions\Tenant\CreateTenantWorkspace;
use App\Models\Company;
use App\Models\CompanySite;
use App\Models\EquipmentAsset;
use App\Models\EquipmentType;
use App\Models\JobRole;
use App\Models\RiskCatalogItem;
use App\Models\RiskCategory;
use App\Models\RiskMeasure;
use App\Models\RiskProfileItem;
use App\Models\RiskSourceLink;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerEquipmentExposure;
use App\Models\WorkerJobRoleAssignment;
use App\Models\WorkerWorkplaceExposure;
use App\Models\Workplace;
use App\Models\WorkplaceType;
use App\Support\RiskEngineSnapshotBuilder;
use Database\Seeders\SicurezzaChiaraCoreEquipmentTypesSeeder;
use Database\Seeders\SicurezzaChiaraCoreJobRolesSeeder;
use Database\Seeders\SicurezzaChiaraCoreRiskCategoriesSeeder;
use Database\Seeders\SicurezzaChiaraCoreRisksSeeder;
use Database\Seeders\SicurezzaChiaraCoreWorkplaceTypesSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('company and worker risk profiles are deduced from mapped domain sources', function () {
    $this->seed([
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
    $equipmentType = EquipmentType::query()->where('name', 'Carrello elevatore')->firstOrFail();
    $workplaceType = WorkplaceType::query()->where('name', 'Area di magazzino')->firstOrFail();

    WorkerJobRoleAssignment::query()->create([
        'worker_id' => $worker->id,
        'job_role_id' => $jobRole->id,
        'is_primary' => true,
    ]);

    $equipmentAsset = EquipmentAsset::query()->create([
        'company_id' => $company->id,
        'company_site_id' => $site->id,
        'equipment_type_id' => $equipmentType->id,
        'name' => 'Muletto reparto A',
        'status' => 'active',
    ]);

    WorkerEquipmentExposure::query()->create([
        'worker_id' => $worker->id,
        'equipment_asset_id' => $equipmentAsset->id,
        'is_primary' => true,
    ]);

    $workplace = Workplace::query()->create([
        'company_site_id' => $site->id,
        'workplace_type_id' => $workplaceType->id,
        'name' => 'Area picking',
        'status' => 'active',
    ]);

    WorkerWorkplaceExposure::query()->create([
        'worker_id' => $worker->id,
        'workplace_id' => $workplace->id,
        'is_primary' => true,
    ]);

    $links = [
        ['risk' => 'Schiacciamento e cesoiamento', 'source' => $jobRole],
        ['risk' => 'Circolazione mezzi e interferenze', 'source' => $equipmentType],
        ['risk' => 'Scivolamento e caduta a livello', 'source' => $workplaceType],
    ];

    foreach ($links as $linkData) {
        $risk = RiskCatalogItem::query()->where('name', $linkData['risk'])->firstOrFail();

        RiskSourceLink::query()->create([
            'risk_catalog_item_id' => $risk->id,
            'sourceable_type' => $linkData['source']::class,
            'sourceable_id' => $linkData['source']->id,
            'relevance' => RiskSourceLink::RELEVANCE_PRIMARY,
        ]);
    }

    $this->actingAs($user)
        ->get(route('companies.risk-profile.show', [
            'company' => $company->id,
            'origin' => 'dashboard',
            'focus' => 'reviews',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sicurezzachiara/risk-profiles/CompanyShow')
            ->where('workspaceBridge.origin', 'dashboard')
            ->where('workspaceBridge.focus', 'reviews')
            ->where('workspaceBridge.suggestedAction.label', 'Monitora copertura e presidi')
        )
        ->assertSee('engine', false)
        ->assertSee('workspaceBridge', false)
        ->assertSee('sourceInputs', false)
        ->assertSee('derivedRisks', false)
        ->assertSee('coreStarterPack', false)
        ->assertSee('suggestedRisksCount', false)
        ->assertSee('Schiacciamento e cesoiamento')
        ->assertSee('Circolazione mezzi e interferenze')
        ->assertSee('Scivolamento e caduta a livello');

    $this->get(route('workers.risk-profile.show', $worker))
        ->assertOk()
        ->assertSee('engine', false)
        ->assertSee('flow', false)
        ->assertSee('sourceFamilies', false)
        ->assertSee('coreStarterPack', false)
        ->assertSee('Mario Rossi')
        ->assertSee('Area di magazzino');

    $companyProfileCount = RiskProfileItem::query()
        ->where('profileable_type', Company::class)
        ->where('profileable_id', $company->id)
        ->count();

    $workerProfileCount = RiskProfileItem::query()
        ->where('profileable_type', Worker::class)
        ->where('profileable_id', $worker->id)
        ->count();

    expect($companyProfileCount)->toBe(3)
        ->and($workerProfileCount)->toBe(3);
});

test('risk profile excludes foreign tenant risk mappings linked to shared core sources', function () {
    $this->seed([
        SicurezzaChiaraCoreJobRolesSeeder::class,
        SicurezzaChiaraCoreRiskCategoriesSeeder::class,
    ]);

    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $tenant = app(CreateTenantWorkspace::class)->handle($user, 'Studio Uno');
    $otherTenant = app(CreateTenantWorkspace::class)->handle($otherUser, 'Studio Due');

    $company = Company::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Metalnova S.r.l.',
    ]);

    $worker = Worker::query()->create([
        'company_id' => $company->id,
        'first_name' => 'Laura',
        'last_name' => 'Bianchi',
        'status' => 'active',
    ]);

    $jobRole = JobRole::query()->where('name', 'Addetto di magazzino')->firstOrFail();

    WorkerJobRoleAssignment::query()->create([
        'worker_id' => $worker->id,
        'job_role_id' => $jobRole->id,
        'is_primary' => true,
    ]);

    $category = RiskCategory::query()->where('name', 'Rischi organizzativi')->firstOrFail();

    $foreignRisk = $otherTenant->riskCatalogItems()->create([
        'risk_category_id' => $category->id,
        'source' => RiskCatalogItem::SOURCE_TENANT,
        'name' => 'Rischio solo tenant esterno',
        'default_priority' => RiskCatalogItem::PRIORITY_HIGH,
        'is_active' => true,
    ]);

    RiskSourceLink::query()->create([
        'risk_catalog_item_id' => $foreignRisk->id,
        'sourceable_type' => JobRole::class,
        'sourceable_id' => $jobRole->id,
        'relevance' => RiskSourceLink::RELEVANCE_PRIMARY,
    ]);

    $this->actingAs($user)
        ->get(route('workers.risk-profile.show', $worker))
        ->assertOk()
        ->assertDontSee('Rischio solo tenant esterno');

    expect(
        RiskProfileItem::query()
            ->where('profileable_type', Worker::class)
            ->where('profileable_id', $worker->id)
            ->count()
    )->toBe(0);
});

test('engine snapshots keep company and worker measure coverage isolated even when they share the same risk', function () {
    $this->seed([
        SicurezzaChiaraCoreJobRolesSeeder::class,
        SicurezzaChiaraCoreRiskCategoriesSeeder::class,
        SicurezzaChiaraCoreRisksSeeder::class,
    ]);

    $user = User::factory()->create();
    $tenant = app(CreateTenantWorkspace::class)->handle($user, 'Studio Test');

    $company = Company::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Metalnova S.r.l.',
    ]);

    $worker = Worker::query()->create([
        'company_id' => $company->id,
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'status' => 'active',
    ]);

    $jobRole = JobRole::query()->where('name', 'Operatore di produzione')->firstOrFail();
    $risk = RiskCatalogItem::query()->where('name', 'Schiacciamento e cesoiamento')->firstOrFail();

    WorkerJobRoleAssignment::query()->create([
        'worker_id' => $worker->id,
        'job_role_id' => $jobRole->id,
        'is_primary' => true,
    ]);

    RiskSourceLink::query()->create([
        'risk_catalog_item_id' => $risk->id,
        'sourceable_type' => JobRole::class,
        'sourceable_id' => $jobRole->id,
        'relevance' => RiskSourceLink::RELEVANCE_PRIMARY,
    ]);

    $risk->update([
        'expected_measures' => [
            [
                'code' => 'protections',
                'family' => RiskMeasure::FAMILY_TECHNICAL,
                'title' => 'Protezioni macchina',
                'is_required' => true,
            ],
        ],
    ]);

    app(\App\Support\RiskProfileBuilder::class)->rebuildCompany($company);
    app(\App\Support\RiskProfileBuilder::class)->rebuildWorker($worker);

    RiskMeasure::query()->create([
        'profileable_type' => Company::class,
        'profileable_id' => $company->id,
        'risk_catalog_item_id' => $risk->id,
        'expected_measure_code' => 'protections',
        'family' => RiskMeasure::FAMILY_TECHNICAL,
        'title' => 'Protezioni su linea aziendale',
        'status' => RiskMeasure::STATUS_IMPLEMENTED,
    ]);

    RiskMeasure::query()->create([
        'profileable_type' => Worker::class,
        'profileable_id' => $worker->id,
        'risk_catalog_item_id' => $risk->id,
        'family' => RiskMeasure::FAMILY_TECHNICAL,
        'title' => 'Verifica presidio individuale non completata',
        'status' => RiskMeasure::STATUS_NOT_IMPLEMENTED,
    ]);

    app(\App\Support\RiskCoverageResolver::class)->syncForProfileableRisk($company, $risk->id);
    app(\App\Support\RiskCoverageResolver::class)->syncForProfileableRisk($worker, $risk->id);

    $snapshot = app(RiskEngineSnapshotBuilder::class)->buildFromCollections(
        RiskProfileItem::query()
            ->where(function ($query) use ($company, $worker) {
                $query
                    ->where(function ($companyQuery) use ($company) {
                        $companyQuery->where('profileable_type', Company::class)
                            ->where('profileable_id', $company->id);
                    })
                    ->orWhere(function ($workerQuery) use ($worker) {
                        $workerQuery->where('profileable_type', Worker::class)
                            ->where('profileable_id', $worker->id);
                    });
            })
            ->get(),
        RiskMeasure::query()
            ->where(function ($query) use ($company, $worker) {
                $query
                    ->where(function ($companyQuery) use ($company) {
                        $companyQuery->where('profileable_type', Company::class)
                            ->where('profileable_id', $company->id);
                    })
                    ->orWhere(function ($workerQuery) use ($worker) {
                        $workerQuery->where('profileable_type', Worker::class)
                            ->where('profileable_id', $worker->id);
                    });
            })
            ->get(),
    );

    expect($snapshot['summary']['coveredExpectedMeasures'])->toBe(1)
        ->and($snapshot['summary']['missingExpectedMeasures'])->toBe(1)
        ->and(collect($snapshot['risks'])->pluck('coverage.label')->all())->toContain('Coperto', 'Da presidiare');
});

test('users cannot open company or worker risk profiles belonging to another tenant', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $tenant = app(CreateTenantWorkspace::class)->handle($user, 'Studio Uno');
    app(CreateTenantWorkspace::class)->handle($otherUser, 'Studio Due');

    $company = Company::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Metalnova S.r.l.',
    ]);

    $worker = Worker::query()->create([
        'company_id' => $company->id,
        'first_name' => 'Marco',
        'last_name' => 'Verdi',
        'status' => 'active',
    ]);

    $this->actingAs($otherUser)
        ->get(route('companies.risk-profile.show', $company))
        ->assertNotFound();

    $this->get(route('workers.risk-profile.show', $worker))
        ->assertNotFound();
});
