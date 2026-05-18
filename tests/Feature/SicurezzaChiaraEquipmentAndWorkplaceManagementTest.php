<?php

use App\Actions\Tenant\CreateTenantWorkspace;
use App\Models\Company;
use App\Models\CompanySite;
use App\Models\EquipmentAsset;
use App\Models\EquipmentType;
use App\Models\RiskCatalogItem;
use App\Models\RiskCategory;
use App\Models\RiskMeasure;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerEquipmentExposure;
use App\Models\WorkerWorkplaceExposure;
use App\Models\Workplace;
use App\Models\WorkplaceType;
use Database\Seeders\SicurezzaChiaraCoreEquipmentTypesSeeder;
use Database\Seeders\SicurezzaChiaraCoreWorkplaceTypesSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated users can create tenant equipment types and operational assets', function () {
    $this->seed(SicurezzaChiaraCoreEquipmentTypesSeeder::class);

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

    $this->actingAs($user);

    $this->post(route('equipment-types.store'), [
        'code' => 'TEN-BANCO',
        'name' => 'Banco assemblaggio attrezzato',
        'description' => 'Tipologia tenant-level.',
        'is_active' => true,
    ])->assertRedirect();

    $equipmentType = EquipmentType::query()->where('name', 'Banco assemblaggio attrezzato')->firstOrFail();

    $response = $this->post(route('equipment-assets.store'), [
        'company_id' => $company->id,
        'company_site_id' => $site->id,
        'equipment_type_id' => $equipmentType->id,
        'asset_code' => 'BNK-01',
        'name' => 'Banco assemblaggio linea A',
        'manufacturer' => 'TecnoBench',
        'model' => 'TB-400',
        'status' => 'active',
        'notes' => 'Prima istanza operativa.',
    ]);

    $asset = EquipmentAsset::query()->where('name', 'Banco assemblaggio linea A')->firstOrFail();

    $response->assertRedirect(route('equipment-assets.show', $asset));
    expect($equipmentType->tenant_id)->toBe($tenant->id)
        ->and($asset->company_id)->toBe($company->id)
        ->and($asset->company_site_id)->toBe($site->id);
});

test('authenticated users can create tenant equipment type from custom input on asset form', function () {
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

    $this->actingAs($user);

    $response = $this->post(route('equipment-assets.store'), [
        'company_id' => $company->id,
        'company_site_id' => $site->id,
        'custom_equipment_type_name' => 'Tavolo rotante di assemblaggio',
        'asset_code' => 'ROT-01',
        'name' => 'Tavolo rotante linea B',
        'manufacturer' => 'TecnoBench',
        'model' => 'TR-500',
        'status' => 'active',
        'notes' => 'Nuova tipologia locale.',
    ]);

    $asset = EquipmentAsset::query()->where('name', 'Tavolo rotante linea B')->firstOrFail();
    $equipmentType = EquipmentType::query()
        ->where('tenant_id', $tenant->id)
        ->where('source', EquipmentType::SOURCE_TENANT)
        ->where('name', 'Tavolo rotante di assemblaggio')
        ->first();

    $response->assertRedirect(route('equipment-assets.show', $asset));
    expect($equipmentType)->not->toBeNull()
        ->and($asset->equipment_type_id)->toBe($equipmentType->id);
});

test('users can create workplaces and associate both workplaces and assets to workers of the same company', function () {
    $this->seed(SicurezzaChiaraCoreEquipmentTypesSeeder::class);
    $this->seed(SicurezzaChiaraCoreWorkplaceTypesSeeder::class);

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

    $equipmentType = EquipmentType::query()->where('source', EquipmentType::SOURCE_CORE)->where('name', 'Carrello elevatore')->firstOrFail();
    $workplaceType = WorkplaceType::query()->where('source', WorkplaceType::SOURCE_CORE)->where('name', 'Linea produttiva')->firstOrFail();

    $asset = EquipmentAsset::query()->create([
        'company_id' => $company->id,
        'company_site_id' => $site->id,
        'equipment_type_id' => $equipmentType->id,
        'name' => 'Muletto frontale 25q',
        'status' => 'active',
    ]);

    $this->actingAs($user);

    $this->post(route('workplaces.store'), [
        'company_site_id' => $site->id,
        'workplace_type_id' => $workplaceType->id,
        'code' => 'LINEA-A',
        'name' => 'Linea assemblaggio A',
        'status' => 'active',
    ])->assertRedirect();

    $workplace = Workplace::query()->where('name', 'Linea assemblaggio A')->firstOrFail();

    $this->post(route('workers.equipment-exposures.store', $worker), [
        'equipment_asset_id' => $asset->id,
        'is_primary' => true,
        'notes' => 'Uso prevalente.',
    ])->assertRedirect(route('workers.show', $worker));

    $this->post(route('workers.workplace-exposures.store', $worker), [
        'workplace_id' => $workplace->id,
        'is_primary' => true,
        'notes' => 'Area prevalente.',
    ])->assertRedirect(route('workers.show', $worker));

    expect(WorkerEquipmentExposure::query()->where('worker_id', $worker->id)->count())->toBe(1)
        ->and(WorkerWorkplaceExposure::query()->where('worker_id', $worker->id)->count())->toBe(1);
});

test('users cannot associate foreign company assets or workplaces to a worker', function () {
    $this->seed(SicurezzaChiaraCoreEquipmentTypesSeeder::class);
    $this->seed(SicurezzaChiaraCoreWorkplaceTypesSeeder::class);

    $user = User::factory()->create();
    $tenant = app(CreateTenantWorkspace::class)->handle($user, 'Studio Test');

    $company = Company::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Metalnova S.r.l.',
    ]);

    $otherCompany = Company::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Logiport Services S.r.l.',
    ]);

    $site = CompanySite::query()->create([
        'company_id' => $company->id,
        'name' => 'Stabilimento principale',
        'is_headquarters' => true,
    ]);

    $otherSite = CompanySite::query()->create([
        'company_id' => $otherCompany->id,
        'name' => 'Hub logistico',
        'is_headquarters' => true,
    ]);

    $worker = Worker::query()->create([
        'company_id' => $company->id,
        'primary_site_id' => $site->id,
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'status' => 'active',
    ]);

    $equipmentType = EquipmentType::query()->where('source', EquipmentType::SOURCE_CORE)->firstOrFail();
    $workplaceType = WorkplaceType::query()->where('source', WorkplaceType::SOURCE_CORE)->firstOrFail();

    $foreignAsset = EquipmentAsset::query()->create([
        'company_id' => $otherCompany->id,
        'company_site_id' => $otherSite->id,
        'equipment_type_id' => $equipmentType->id,
        'name' => 'Pressa remota',
        'status' => 'active',
    ]);

    $foreignWorkplace = Workplace::query()->create([
        'company_site_id' => $otherSite->id,
        'workplace_type_id' => $workplaceType->id,
        'name' => 'Area remota',
        'status' => 'active',
    ]);

    $this->actingAs($user);

    $this->from(route('workers.show', $worker))
        ->post(route('workers.equipment-exposures.store', $worker), [
            'equipment_asset_id' => $foreignAsset->id,
            'is_primary' => true,
        ])
        ->assertRedirect(route('workers.show', $worker))
        ->assertSessionHasErrors(['equipment_asset_id']);

    $this->from(route('workers.show', $worker))
        ->post(route('workers.workplace-exposures.store', $worker), [
            'workplace_id' => $foreignWorkplace->id,
            'is_primary' => true,
        ])
        ->assertRedirect(route('workers.show', $worker))
        ->assertSessionHasErrors(['workplace_id']);
});

test('workplace detail uses tabs and surfaces suggested risks from the workplace type', function () {
    $this->seed(SicurezzaChiaraCoreWorkplaceTypesSeeder::class);

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
        'city' => 'Modena',
        'province' => 'MO',
    ]);

    $workplaceType = WorkplaceType::query()->where('name', 'Linea produttiva')->firstOrFail();
    $category = RiskCategory::query()->create([
        'code' => 'FIS',
        'name' => 'Agenti fisici',
        'is_active' => true,
    ]);
    $risk = RiskCatalogItem::query()->create([
        'risk_category_id' => $category->id,
        'source' => RiskCatalogItem::SOURCE_CORE,
        'code' => 'RUMORE',
        'name' => 'Rumore',
        'default_priority' => 'medium',
        'is_active' => true,
    ]);
    $workplaceType->riskSourceLinks()->create([
        'risk_catalog_item_id' => $risk->id,
        'relevance' => 'primary',
    ]);

    $workplace = Workplace::query()->create([
        'company_site_id' => $site->id,
        'workplace_type_id' => $workplaceType->id,
        'name' => 'Linea assemblaggio A',
        'status' => 'active',
    ]);

    $company->riskMeasures()->create([
        'risk_catalog_item_id' => $risk->id,
        'family' => RiskMeasure::FAMILY_TRAINING,
        'title' => 'Formazione rumore linea A',
        'status' => RiskMeasure::STATUS_TO_VERIFY,
        'due_date' => now()->addDays(10)->toDateString(),
    ]);

    $this->actingAs($user)
        ->get(route('workplaces.show', $workplace))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sicurezzachiara/workplaces/Show')
            ->where('workplace.name', 'Linea assemblaggio A')
            ->where('workplace.workplace_type.risk_source_links.0.risk_catalog_item.name', 'Rumore')
            ->where('contextBridge.sourceLabel', 'Luogo')
            ->where('contextBridge.stats.suggestedRisks', 1)
            ->where('contextBridge.actions.riskProfileRoute', route('companies.risk-profile.show', [
                'company' => $company->id,
                'origin' => 'company_show',
            ]))
            ->where('contextBridge.actions.registryRoute', route('measure-registries.index', [
                'company_id' => $company->id,
                'scope' => 'attention',
                'origin' => 'company_show',
            ]))
            ->where('governanceContext.summary.totalMeasures', 1)
            ->where('governanceContext.previewMeasures.0.title', 'Formazione rumore linea A')
        );
});

test('equipment detail uses tabs and surfaces suggested risks from the equipment type', function () {
    $this->seed(SicurezzaChiaraCoreEquipmentTypesSeeder::class);

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

    $equipmentType = EquipmentType::query()->where('name', 'Carrello elevatore')->firstOrFail();
    $category = RiskCategory::query()->create([
        'code' => 'MEC',
        'name' => 'Attrezzature e mezzi',
        'is_active' => true,
    ]);
    $risk = RiskCatalogItem::query()->create([
        'risk_category_id' => $category->id,
        'source' => RiskCatalogItem::SOURCE_CORE,
        'code' => 'MEZZI',
        'name' => 'Investimento da mezzi',
        'default_priority' => 'medium',
        'is_active' => true,
    ]);
    $equipmentType->riskSourceLinks()->create([
        'risk_catalog_item_id' => $risk->id,
        'relevance' => 'primary',
    ]);

    $asset = EquipmentAsset::query()->create([
        'company_id' => $company->id,
        'company_site_id' => $site->id,
        'equipment_type_id' => $equipmentType->id,
        'name' => 'Muletto frontale 25q',
        'status' => 'active',
    ]);

    $company->riskMeasures()->create([
        'risk_catalog_item_id' => $risk->id,
        'family' => RiskMeasure::FAMILY_TECHNICAL,
        'title' => 'Controllo viabilita mezzi',
        'status' => RiskMeasure::STATUS_NOT_IMPLEMENTED,
        'due_date' => now()->addDays(5)->toDateString(),
    ]);

    $this->actingAs($user)
        ->get(route('equipment-assets.show', $asset))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sicurezzachiara/equipment-assets/Show')
            ->where('asset.name', 'Muletto frontale 25q')
            ->where('asset.equipment_type.risk_source_links.0.risk_catalog_item.name', 'Investimento da mezzi')
            ->where('contextBridge.sourceLabel', 'Macchinario')
            ->where('contextBridge.stats.suggestedRisks', 1)
            ->where('contextBridge.actions.riskProfileRoute', route('companies.risk-profile.show', [
                'company' => $company->id,
                'origin' => 'company_show',
            ]))
            ->where('contextBridge.actions.registryRoute', route('measure-registries.index', [
                'company_id' => $company->id,
                'scope' => 'attention',
                'origin' => 'company_show',
            ]))
            ->where('governanceContext.summary.totalMeasures', 1)
            ->where('governanceContext.previewMeasures.0.title', 'Controllo viabilita mezzi')
        );
});
