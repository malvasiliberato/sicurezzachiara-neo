<?php

use App\Actions\Tenant\CreateTenantWorkspace;
use App\Models\Ateco2025;
use App\Models\Company;
use App\Models\EquipmentAsset;
use App\Models\EquipmentType;
use App\Models\JobRole;
use App\Models\User;
use App\Models\Worker;
use App\Models\Workplace;
use App\Models\WorkplaceType;
use Database\Seeders\SicurezzaChiaraShowcaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated users can create companies and sites inside their current tenant', function () {
    $user = User::factory()->create();
    $ateco = Ateco2025::query()->create([
        'codice' => '25.62.00',
        'titolo_it' => 'Lavorazioni meccaniche',
        'livello' => 6,
        'codice_padre' => '25.62.0',
        'livello_padre' => 5,
        'ordine' => 1,
    ]);

    app(CreateTenantWorkspace::class)->handle($user, 'Studio Test');

    $this->actingAs($user);

    $companyResponse = $this->post(route('companies.store'), [
        'name' => 'Metalnova S.r.l.',
        'vat_number' => 'IT01234567890',
        'tax_code' => '01234567890',
        'ateco_2025_id' => $ateco->id,
        'contact_email' => 'info@metalnova.test',
        'contact_phone' => '059000000',
        'address_line' => 'Via del Lavoro',
        'street_number' => '12',
        'city' => 'Modena',
        'province' => 'MO',
        'postal_code' => '41121',
        'notes' => 'Cliente fondativo.',
    ]);

    $company = Company::query()->firstOrFail();

    $companyResponse->assertRedirect(route('companies.edit', $company));
    expect($company->tenant_id)->toBe($user->fresh()->current_tenant_id);
    expect($company->ateco_2025_id)->toBe($ateco->id);
    expect($company->industry)->toBe('Lavorazioni meccaniche');

    $siteResponse = $this->post(route('companies.sites.store', $company), [
        'name' => 'Stabilimento principale',
        'site_code' => 'HQ',
        'is_headquarters' => true,
        'address_line' => 'Via Roma',
        'street_number' => '1',
        'postal_code' => '41121',
        'city' => 'Modena',
        'province' => 'MO',
        'notes' => 'Sede produttiva.',
        'redirect_to_company_edit' => true,
    ]);

    $site = $company->fresh()->sites()->first();

    $siteResponse->assertRedirect(route('companies.edit', $company));
    expect($company->fresh()->sites)->toHaveCount(1);
    expect($site->street_number)->toBe('1');

    $updateResponse = $this->put(route('companies.sites.update', [$company, $site]), [
        'name' => 'Stabilimento aggiornato',
        'site_code' => 'HQ-2',
        'is_headquarters' => true,
        'address_line' => 'Via Salerno',
        'street_number' => '25',
        'postal_code' => '84131',
        'city' => 'Salerno',
        'province' => 'SA',
        'notes' => 'Sede aggiornata.',
        'redirect_to_company_edit' => true,
    ]);

    $updateResponse->assertRedirect(route('companies.edit', $company));
    expect($site->fresh()->name)->toBe('Stabilimento aggiornato');
    expect($site->fresh()->street_number)->toBe('25');

    $deleteResponse = $this->delete(route('companies.sites.destroy', [$company, $site]), [
        'redirect_to_company_edit' => true,
    ]);

    $deleteResponse->assertRedirect(route('companies.edit', $company));
    expect($company->fresh()->sites)->toHaveCount(0);
});

test('authenticated users can search ateco entries by code or description', function () {
    $user = User::factory()->create();

    app(CreateTenantWorkspace::class)->handle($user, 'Studio Test');

    Ateco2025::query()->create([
        'codice' => '25.62.00',
        'titolo_it' => 'Lavorazioni meccaniche',
        'livello' => 6,
        'codice_padre' => '25.62.0',
        'livello_padre' => 5,
        'ordine' => 1,
    ]);

    Ateco2025::query()->create([
        'codice' => '25.73.10',
        'titolo_it' => 'Fabbricazione di utensileria ad azionamento manuale',
        'livello' => 6,
        'codice_padre' => '25.73.1',
        'livello_padre' => 5,
        'ordine' => 2,
    ]);

    $this->actingAs($user)
        ->get(route('ateco.search', ['q' => 'meccan']))
        ->assertOk()
        ->assertJsonPath('results.0.code', '25.62.00')
        ->assertJsonPath('results.0.title', 'Lavorazioni meccaniche');
});

test('company create form presents ateco as orientative lookup only', function () {
    $user = User::factory()->create();

    app(CreateTenantWorkspace::class)->handle($user, 'Studio Test');

    $this->actingAs($user)
        ->get(route('companies.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sicurezzachiara/companies/Create')
            ->where('atecoConfig.searchRoute', route('ateco.search'))
            ->where('atecoConfig.initialOption', null)
        )
        ->assertDontSee('cluster', false)
        ->assertDontSee('classificatore', false)
        ->assertDontSee('safety_ateco_cluster_map', false);
});

test('site deletion reports linked places equipment and workers instead of generic block', function () {
    $user = User::factory()->create();

    app(CreateTenantWorkspace::class)->handle($user, 'Studio Test');

    $this->actingAs($user);

    $company = Company::query()->create([
        'tenant_id' => $user->fresh()->current_tenant_id,
        'name' => 'Metalnova S.r.l.',
    ]);

    $site = $company->sites()->create([
        'name' => 'Stabilimento principale',
        'site_code' => 'HQ',
        'is_headquarters' => true,
    ]);

    $workplaceType = WorkplaceType::query()->create([
        'source' => WorkplaceType::SOURCE_CORE,
        'code' => 'UFF',
        'name' => 'Ufficio',
        'is_active' => true,
    ]);

    $equipmentType = EquipmentType::query()->create([
        'source' => EquipmentType::SOURCE_CORE,
        'code' => 'CARRELLO',
        'name' => 'Carrello',
        'is_active' => true,
    ]);

    Workplace::query()->create([
        'company_site_id' => $site->id,
        'workplace_type_id' => $workplaceType->id,
        'name' => 'Magazzino',
        'status' => 'active',
    ]);

    EquipmentAsset::query()->create([
        'company_id' => $company->id,
        'company_site_id' => $site->id,
        'equipment_type_id' => $equipmentType->id,
        'name' => 'Carrello elevatore',
        'status' => 'active',
    ]);

    Worker::query()->create([
        'company_id' => $company->id,
        'primary_site_id' => $site->id,
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'status' => 'active',
    ]);

    $deleteResponse = $this->delete(route('companies.sites.destroy', [$company, $site]), [
        'redirect_to_company_edit' => true,
    ]);

    $deleteResponse->assertRedirect(route('companies.edit', $company));
    $deleteResponse->assertSessionHas('error.title', 'Sede ancora in uso');
    $deleteResponse->assertSessionHas('error.references.0.label', 'Luoghi');
    $deleteResponse->assertSessionHas('error.references.1.label', 'Macchinari');
    $deleteResponse->assertSessionHas('error.references.2.label', 'Lavoratori');

    $this->get(route('companies.edit', $company))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sicurezzachiara/companies/Edit')
            ->where('company.sites.0.dependency_alert.references.0.label', 'Luoghi')
            ->where('company.sites.0.dependency_alert.references.0.items.0', 'Magazzino')
            ->where('company.sites.0.dependency_alert.references.1.items.0', 'Carrello elevatore')
            ->where('company.sites.0.dependency_alert.references.2.items.0', 'Mario Rossi')
        );

    expect($company->fresh()->sites)->toHaveCount(1);
});

test('company workplace management supports update and reports linked workers on delete', function () {
    $user = User::factory()->create();

    app(CreateTenantWorkspace::class)->handle($user, 'Studio Test');

    $this->actingAs($user);

    $company = Company::query()->create([
        'tenant_id' => $user->fresh()->current_tenant_id,
        'name' => 'Metalnova S.r.l.',
    ]);

    $site = $company->sites()->create([
        'name' => 'Stabilimento principale',
        'site_code' => 'HQ',
        'is_headquarters' => true,
    ]);

    $workplaceType = WorkplaceType::query()->create([
        'source' => WorkplaceType::SOURCE_CORE,
        'code' => 'UFF',
        'name' => 'Ufficio',
        'is_active' => true,
    ]);

    $storeResponse = $this->post(route('workplaces.store'), [
        'company_site_id' => $site->id,
        'workplace_type_id' => $workplaceType->id,
        'code' => 'UFF-01',
        'name' => 'Ufficio acquisti',
        'status' => 'active',
        'notes' => 'Area amministrativa al primo piano',
        'redirect_to_company_edit' => true,
    ]);

    $workplace = Workplace::query()->firstOrFail();

    $storeResponse->assertRedirect(route('companies.edit', $company));
    expect($workplace->name)->toBe('Ufficio acquisti');
    expect($workplace->notes)->toBe('Area amministrativa al primo piano');
    expect($workplace->description)->toBe('Area amministrativa al primo piano');

    $updateResponse = $this->put(route('workplaces.update', $workplace), [
        'company_site_id' => $site->id,
        'workplace_type_id' => $workplaceType->id,
        'code' => 'UFF-02',
        'name' => 'Ufficio tecnico',
        'status' => 'inactive',
        'notes' => 'Area progettazione al secondo piano',
        'redirect_to_company_edit' => true,
    ]);

    $updateResponse->assertRedirect(route('companies.edit', $company));
    expect($workplace->fresh()->name)->toBe('Ufficio tecnico');
    expect($workplace->fresh()->status)->toBe('inactive');
    expect($workplace->fresh()->notes)->toBe('Area progettazione al secondo piano');
    expect($workplace->fresh()->description)->toBe('Area progettazione al secondo piano');

    $worker = Worker::query()->create([
        'company_id' => $company->id,
        'primary_site_id' => $site->id,
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'status' => 'active',
    ]);

    \App\Models\WorkerWorkplaceExposure::query()->create([
        'worker_id' => $worker->id,
        'workplace_id' => $workplace->id,
        'is_primary' => true,
    ]);

    $deleteBlocked = $this->delete(route('workplaces.destroy', $workplace), [
        'redirect_to_company_edit' => true,
    ]);

    $deleteBlocked->assertRedirect(route('companies.edit', $company));
    $deleteBlocked->assertSessionHas('error.title', 'Luogo ancora in uso');
    $deleteBlocked->assertSessionHas('error.references.0.label', 'Lavoratori');

    $this->get(route('companies.edit', $company))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sicurezzachiara/companies/Edit')
            ->where('company.sites.0.workplaces.0.dependency_alert.references.0.label', 'Lavoratori')
            ->where('company.sites.0.workplaces.0.dependency_alert.references.0.items.0', 'Mario Rossi')
        );

    \App\Models\WorkerWorkplaceExposure::query()->delete();

    $deleteSuccess = $this->delete(route('workplaces.destroy', $workplace), [
        'redirect_to_company_edit' => true,
    ]);

    $deleteSuccess->assertRedirect(route('companies.edit', $company));
    expect(Workplace::query()->count())->toBe(0);
});

test('company workplace management can create a tenant workplace type from custom input', function () {
    $user = User::factory()->create();

    app(CreateTenantWorkspace::class)->handle($user, 'Studio Test');

    $this->actingAs($user);

    $company = Company::query()->create([
        'tenant_id' => $user->fresh()->current_tenant_id,
        'name' => 'Metalnova S.r.l.',
    ]);

    $site = $company->sites()->create([
        'name' => 'Stabilimento principale',
        'site_code' => 'HQ',
        'is_headquarters' => true,
    ]);

    $storeResponse = $this->post(route('workplaces.store'), [
        'company_site_id' => $site->id,
        'custom_workplace_type_name' => 'Area di carico esterna',
        'code' => 'EST-01',
        'name' => 'Area scarico merci',
        'description' => 'Piazzale esterno',
        'status' => 'active',
        'notes' => 'Uso quotidiano',
        'redirect_to_company_edit' => true,
    ]);

    $storeResponse->assertRedirect(route('companies.edit', $company));

    $tenantWorkplaceType = WorkplaceType::query()
        ->where('tenant_id', $user->fresh()->current_tenant_id)
        ->where('source', WorkplaceType::SOURCE_TENANT)
        ->where('name', 'Area di carico esterna')
        ->first();

    $workplace = Workplace::query()->firstOrFail();

    expect($tenantWorkplaceType)->not->toBeNull();
    expect($workplace->workplace_type_id)->toBe($tenantWorkplaceType->id);
});

test('company worker management supports update and reports linked records on delete', function () {
    $user = User::factory()->create();

    app(CreateTenantWorkspace::class)->handle($user, 'Studio Test');

    $this->actingAs($user);

    $company = Company::query()->create([
        'tenant_id' => $user->fresh()->current_tenant_id,
        'name' => 'Metalnova S.r.l.',
    ]);

    $site = $company->sites()->create([
        'name' => 'Stabilimento principale',
        'site_code' => 'HQ',
        'is_headquarters' => true,
    ]);

    $jobRole = JobRole::query()->create([
        'source' => JobRole::SOURCE_CORE,
        'code' => 'OPERATORE',
        'name' => 'Operatore produzione',
        'is_active' => true,
    ]);

    $storeResponse = $this->post(route('workers.store'), [
        'company_id' => $company->id,
        'primary_site_id' => $site->id,
        'job_role_id' => $jobRole->id,
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'tax_code' => 'RSSMRA80A01F257X',
        'status' => 'active',
        'redirect_to_company_edit' => true,
    ]);

    $worker = Worker::query()->firstOrFail();

    $storeResponse->assertRedirect(route('companies.edit', $company));
    expect($worker->primary_site_id)->toBe($site->id);
    expect($worker->fresh()->jobRoleAssignments()->where('is_primary', true)->first()?->job_role_id)->toBe($jobRole->id);

    $updateResponse = $this->put(route('workers.update', $worker), [
        'company_id' => $company->id,
        'primary_site_id' => $site->id,
        'job_role_id' => $jobRole->id,
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'tax_code' => 'RSSMRA80A01F257X',
        'email' => 'mario.rossi@example.test',
        'phone' => '3490000000',
        'status' => 'inactive',
        'notes' => 'Profilo sospeso.',
        'redirect_to_company_edit' => true,
    ]);

    $updateResponse->assertRedirect(route('companies.edit', $company));
    expect($worker->fresh()->status)->toBe('inactive');
    expect($worker->fresh()->email)->toBe('mario.rossi@example.test');

    $deleteBlocked = $this->delete(route('workers.destroy', $worker), [
        'redirect_to_company_edit' => true,
    ]);

    $deleteBlocked->assertRedirect(route('companies.edit', $company));
    $deleteBlocked->assertSessionHas('error.title', 'Lavoratore ancora in uso');
    $deleteBlocked->assertSessionHas('error.references.0.label', 'Mansioni');

    \App\Models\WorkerJobRoleAssignment::query()->delete();

    $deleteSuccess = $this->delete(route('workers.destroy', $worker), [
        'redirect_to_company_edit' => true,
    ]);

    $deleteSuccess->assertRedirect(route('companies.edit', $company));
    expect(Worker::query()->count())->toBe(0);
});

test('company equipment management supports update and reports linked workers on delete', function () {
    $user = User::factory()->create();

    app(CreateTenantWorkspace::class)->handle($user, 'Studio Test');

    $this->actingAs($user);

    $company = Company::query()->create([
        'tenant_id' => $user->fresh()->current_tenant_id,
        'name' => 'Metalnova S.r.l.',
    ]);

    $site = $company->sites()->create([
        'name' => 'Stabilimento principale',
        'site_code' => 'HQ',
        'is_headquarters' => true,
    ]);

    $equipmentType = EquipmentType::query()->create([
        'source' => EquipmentType::SOURCE_CORE,
        'code' => 'CARRELLO',
        'name' => 'Carrello',
        'is_active' => true,
    ]);

    $storeResponse = $this->post(route('equipment-assets.store'), [
        'company_id' => $company->id,
        'company_site_id' => $site->id,
        'equipment_type_id' => $equipmentType->id,
        'asset_code' => 'CAR-01',
        'name' => 'Carrello elevatore',
        'manufacturer' => 'Linde',
        'model' => 'X35',
        'status' => 'active',
        'notes' => 'Bene operativo.',
        'redirect_to_company_edit' => true,
    ]);

    $asset = EquipmentAsset::query()->firstOrFail();

    $storeResponse->assertRedirect(route('companies.edit', $company));
    expect($asset->company_site_id)->toBe($site->id);

    $updateResponse = $this->put(route('equipment-assets.update', $asset), [
        'company_id' => $company->id,
        'company_site_id' => $site->id,
        'equipment_type_id' => $equipmentType->id,
        'asset_code' => 'CAR-02',
        'name' => 'Carrello elevatore linea A',
        'manufacturer' => 'Linde',
        'model' => 'X36',
        'status' => 'inactive',
        'notes' => 'Fermo temporaneo.',
        'redirect_to_company_edit' => true,
    ]);

    $updateResponse->assertRedirect(route('companies.edit', $company));
    expect($asset->fresh()->name)->toBe('Carrello elevatore linea A');
    expect($asset->fresh()->status)->toBe('inactive');

    $worker = Worker::query()->create([
        'company_id' => $company->id,
        'primary_site_id' => $site->id,
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'status' => 'active',
    ]);

    \App\Models\WorkerEquipmentExposure::query()->create([
        'worker_id' => $worker->id,
        'equipment_asset_id' => $asset->id,
        'is_primary' => true,
    ]);

    $deleteBlocked = $this->delete(route('equipment-assets.destroy', $asset), [
        'redirect_to_company_edit' => true,
    ]);

    $deleteBlocked->assertRedirect(route('companies.edit', $company));
    $deleteBlocked->assertSessionHas('error.title', 'Macchinario ancora in uso');
    $deleteBlocked->assertSessionHas('error.references.0.label', 'Lavoratori');

    $this->get(route('companies.edit', $company))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sicurezzachiara/companies/Edit')
            ->where('company.equipment_assets.0.dependency_alert.references.0.label', 'Lavoratori')
            ->where('company.equipment_assets.0.dependency_alert.references.0.items.0', 'Mario Rossi')
        );

    \App\Models\WorkerEquipmentExposure::query()->delete();

    $deleteSuccess = $this->delete(route('equipment-assets.destroy', $asset), [
        'redirect_to_company_edit' => true,
    ]);

    $deleteSuccess->assertRedirect(route('companies.edit', $company));
    expect(EquipmentAsset::query()->count())->toBe(0);
});

test('authenticated users can search comuni by city name or cap', function () {
    $user = User::factory()->create();

    app(CreateTenantWorkspace::class)->handle($user, 'Studio Test');

    \Illuminate\Support\Facades\DB::table('comuni_elenco')->insert([
        [
            'istat' => 900001,
            'cap' => '84121-84135',
            'comune' => 'Salerno',
            'regione' => 'Campania',
            'provincia' => 'SA',
            'provincia_esteso' => 'Salerno',
            'cod_fisco' => 'H703',
            'comune_provincia' => 'Salerno (SA)',
        ], [
            'istat' => 900003,
            'cap' => '00144',
            'comune' => 'Roma',
            'regione' => 'Lazio',
            'provincia' => 'RM',
            'provincia_esteso' => 'Roma',
            'cod_fisco' => 'H501',
            'comune_provincia' => 'Roma (RM)',
        ],
    ]);

    $this->actingAs($user)
        ->get(route('comuni.search', ['q' => 'Saler']))
        ->assertOk()
        ->assertJsonPath('results.0.city', 'Salerno')
        ->assertJsonPath('results.0.province', 'SA')
        ->assertJsonPath('results.0.capLabel', '84121 - 84135')
        ->assertJsonPath('results.0.caps.0', '84121')
        ->assertJsonPath('results.0.caps.14', '84135');

    $this->actingAs($user)
        ->get(route('comuni.search', ['q' => '84134']))
        ->assertOk()
        ->assertJsonPath('results.0.city', 'Salerno')
        ->assertJsonPath('results.0.province', 'SA');
});

test('company detail surfaces contextual core starter pack signals', function () {
    $this->seed(SicurezzaChiaraShowcaseSeeder::class);

    $user = User::query()->where('email', 'owner.showcase@sicurezzachiara.test')->firstOrFail();
    $company = Company::query()->where('name', 'Metalnova S.r.l.')->firstOrFail();
    app(\App\Support\RiskProfileBuilder::class)->rebuildCompany($company);
    $company = $company->fresh(['riskProfileItems', 'riskMeasures']);
    $engineSummary = app(\App\Support\RiskEngineSnapshotBuilder::class)->buildForProfileable($company)['summary'];
    $reviewItem = $company->riskProfileItems()->orderByDesc('operational_status')->orderByDesc('final_priority')->firstOrFail();
    $overdueMeasures = $company->riskMeasures
        ->filter(fn ($measure) => $measure->due_date !== null
            && $measure->status !== 'implemented'
            && $measure->due_date->isPast())
        ->count();

    $this->actingAs($user)
        ->get(route('companies.show', $company))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sicurezzachiara/companies/Show')
            ->where('contextBridge.focus', 'deadlines')
            ->where('contextBridge.suggestedAction.label', 'Chiudi le scadenze aperte')
            ->where('contextBridge.workQueue.0.key', 'deadlines')
            ->where('contextBridge.workQueue.0.count', $overdueMeasures)
            ->where('contextBridge.workQueue.0.actionRoute', route('measure-registries.index', [
                'company_id' => $company->id,
                'origin' => 'company_show',
                'focus' => 'deadlines',
                'scope' => 'overdue',
            ]))
            ->where('contextBridge.workQueue', fn ($queue) => collect($queue)->contains(
                fn (array $item) => $item['key'] === 'follow_up'
                    && $item['count'] === $engineSummary['followUpsOpen']
            ))
            ->where('contextBridge.actions.reviewRoute', route('companies.risk-profile.review.show', [
                'company' => $company,
                'riskProfileItem' => $reviewItem,
                'origin' => 'company_show',
                'focus' => 'deadlines',
            ]))
            ->where('contextBridge.actions.measuresRoute', route('companies.risk-profile.measures.show', [
                'company' => $company,
                'riskProfileItem' => $reviewItem,
                'origin' => 'company_show',
                'focus' => 'deadlines',
            ]))
            ->where('areaOneJourney.currentStep.number', 5)
            ->where('areaOneJourney.governanceStep.number', 6)
            ->where('areaOneJourney.setupComplete', true)
            ->where('contextBridge.actions.riskProfileRoute', route('companies.risk-profile.show', [
                'company' => $company,
                'origin' => 'company_show',
                'focus' => 'deadlines',
            ]))
            ->where('contextBridge.operationalQueue.0.key', 'reviews')
            ->where('contextBridge.operationalQueue.0.count', $engineSummary['reviewsDue'])
            ->where('contextBridge.operationalQueue.1.key', 'follow_up')
            ->where('contextBridge.operationalQueue.1.count', $engineSummary['followUpsOpen'])
            ->where('contextBridge.operationalQueue.2.key', 'registries')
            ->where('contextBridge.operationalQueue.2.count', $engineSummary['pendingMeasures'])
            ->where('contextBridge.stats.uncoveredRisks', $engineSummary['uncoveredRisks'])
            ->where('contextBridge.stats.reviewsDue', $engineSummary['reviewsDue'])
            ->where('contextBridge.stats.followUpsOpen', $engineSummary['followUpsOpen'])
            ->where('contextBridge.stats.pendingMeasures', $engineSummary['pendingMeasures'])
            ->where('contextBridge.stats.coverageRate', $engineSummary['coverageRate'])
            ->where('contextBridge.stats.risksWithExpectedGaps', $engineSummary['risksWithExpectedGaps'])
            ->where('contextBridge.stats.overdueMeasures', $overdueMeasures)
            ->where('company.workers.0.full_name', 'Marco Rossi')
            ->where('company.equipment_assets.0.name', 'Pressa piegatrice 110T')
        )
        ->assertSee('coreStarterPack', false)
        ->assertSee('contextBridge', false);
});

test('company sections can be opened as dedicated filtered views', function () {
    $user = User::factory()->create();

    app(CreateTenantWorkspace::class)->handle($user, 'Studio Test');

    $this->actingAs($user);

    $company = Company::query()->create([
        'tenant_id' => $user->fresh()->current_tenant_id,
        'name' => 'Metalnova S.r.l.',
    ]);

    $otherCompany = Company::query()->create([
        'tenant_id' => $user->fresh()->current_tenant_id,
        'name' => 'Logiport S.r.l.',
    ]);

    $site = $company->sites()->create([
        'name' => 'Stabilimento principale',
        'site_code' => 'HQ',
        'is_headquarters' => true,
    ]);

    $otherSite = $otherCompany->sites()->create([
        'name' => 'Deposito nord',
        'site_code' => 'DEP',
        'is_headquarters' => true,
    ]);

    $workplaceType = WorkplaceType::query()->create([
        'source' => WorkplaceType::SOURCE_CORE,
        'code' => 'UFF',
        'name' => 'Ufficio',
        'is_active' => true,
    ]);

    $equipmentType = EquipmentType::query()->create([
        'source' => EquipmentType::SOURCE_CORE,
        'code' => 'CARRELLO',
        'name' => 'Carrello',
        'is_active' => true,
    ]);

    Workplace::query()->create([
        'company_site_id' => $site->id,
        'workplace_type_id' => $workplaceType->id,
        'name' => 'Ufficio acquisti',
        'status' => 'active',
    ]);

    Workplace::query()->create([
        'company_site_id' => $otherSite->id,
        'workplace_type_id' => $workplaceType->id,
        'name' => 'Magazzino nord',
        'status' => 'active',
    ]);

    EquipmentAsset::query()->create([
        'company_id' => $company->id,
        'company_site_id' => $site->id,
        'equipment_type_id' => $equipmentType->id,
        'name' => 'Carrello elevatore',
        'status' => 'active',
    ]);

    EquipmentAsset::query()->create([
        'company_id' => $otherCompany->id,
        'company_site_id' => $otherSite->id,
        'equipment_type_id' => $equipmentType->id,
        'name' => 'Transpallet elettrico',
        'status' => 'active',
    ]);

    Worker::query()->create([
        'company_id' => $company->id,
        'primary_site_id' => $site->id,
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'status' => 'active',
    ]);

    Worker::query()->create([
        'company_id' => $otherCompany->id,
        'primary_site_id' => $otherSite->id,
        'first_name' => 'Laura',
        'last_name' => 'Bianchi',
        'status' => 'active',
    ]);

    $this->get(route('workers.index', ['company_id' => $company->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sicurezzachiara/workers/Index')
            ->where('companyContext.name', 'Metalnova S.r.l.')
            ->where('companyContext.riskProfileRoute', route('companies.risk-profile.show', [
                'company' => $company,
                'origin' => 'workers_index',
            ]))
            ->where('companyContext.registryRoute', route('measure-registries.index', [
                'company_id' => $company->id,
                'origin' => 'workers_index',
                'scope' => 'attention',
            ]))
            ->has('workers', 1)
            ->where('workers.0.full_name', 'Mario Rossi')
        );

    $this->get(route('workplaces.index', ['company_id' => $company->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sicurezzachiara/workplaces/Index')
            ->where('companyContext.name', 'Metalnova S.r.l.')
            ->has('workplaces', 1)
            ->where('workplaces.0.name', 'Ufficio acquisti')
        );

    $this->get(route('equipment-assets.index', ['company_id' => $company->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sicurezzachiara/equipment-assets/Index')
            ->where('companyContext.name', 'Metalnova S.r.l.')
            ->has('assets', 1)
            ->where('assets.0.name', 'Carrello elevatore')
        );
});
