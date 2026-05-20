<?php

use App\Actions\Tenant\CreateTenantWorkspace;
use App\Models\EquipmentType;
use App\Models\JobRole;
use App\Models\RiskCatalogItem;
use App\Models\RiskCategory;
use App\Models\RiskSourceLink;
use App\Models\User;
use App\Models\WorkplaceType;
use Database\Seeders\SicurezzaChiaraCoreEquipmentTypesSeeder;
use Database\Seeders\SicurezzaChiaraCoreJobRolesSeeder;
use Database\Seeders\SicurezzaChiaraCoreRiskCategoriesSeeder;
use Database\Seeders\SicurezzaChiaraCoreRisksSeeder;
use Database\Seeders\SicurezzaChiaraCoreWorkplaceTypesSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('method workspace surfaces catalog hubs and mapping summary without inventing new modules', function () {
    $this->seed(SicurezzaChiaraCoreJobRolesSeeder::class);
    $this->seed(SicurezzaChiaraCoreEquipmentTypesSeeder::class);
    $this->seed(SicurezzaChiaraCoreWorkplaceTypesSeeder::class);
    $this->seed(SicurezzaChiaraCoreRiskCategoriesSeeder::class);
    $this->seed(SicurezzaChiaraCoreRisksSeeder::class);

    $user = User::factory()->create();
    $tenant = app(CreateTenantWorkspace::class)->handle($user, 'Studio Metodo');

    $jobRole = $tenant->jobRoles()->create([
        'source' => JobRole::SOURCE_TENANT,
        'name' => 'Addetto collaudo interno',
        'is_active' => true,
    ]);

    $equipmentType = $tenant->equipmentTypes()->create([
        'source' => EquipmentType::SOURCE_TENANT,
        'name' => 'Banco test vibrazioni',
        'is_active' => true,
    ]);

    $workplaceType = $tenant->workplaceTypes()->create([
        'source' => WorkplaceType::SOURCE_TENANT,
        'name' => 'Camera prove',
        'is_active' => true,
    ]);

    $risk = $tenant->riskCatalogItems()->create([
        'risk_category_id' => RiskCategory::query()->firstOrFail()->id,
        'source' => RiskCatalogItem::SOURCE_TENANT,
        'name' => 'Test funzionale con vibrazioni',
        'default_priority' => RiskCatalogItem::PRIORITY_MEDIUM,
        'expected_measures' => [
            [
                'family' => 'organizational',
                'title' => 'Procedura di prova standard',
            ],
        ],
        'is_active' => true,
    ]);

    RiskSourceLink::query()->create([
        'risk_catalog_item_id' => $risk->id,
        'sourceable_type' => JobRole::class,
        'sourceable_id' => $jobRole->id,
        'relevance' => RiskSourceLink::RELEVANCE_PRIMARY,
    ]);

    RiskSourceLink::query()->create([
        'risk_catalog_item_id' => $risk->id,
        'sourceable_type' => EquipmentType::class,
        'sourceable_id' => $equipmentType->id,
        'relevance' => RiskSourceLink::RELEVANCE_PRIMARY,
    ]);

    RiskSourceLink::query()->create([
        'risk_catalog_item_id' => $risk->id,
        'sourceable_type' => WorkplaceType::class,
        'sourceable_id' => $workplaceType->id,
        'relevance' => RiskSourceLink::RELEVANCE_SECONDARY,
    ]);

    $this->actingAs($user)
        ->get(route('sicurezzachiara.method'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sicurezzachiara/method/Index')
            ->where('tenant.name', 'Studio Metodo')
            ->where('methodSummary.riskCatalog.route', route('risk-catalog.index'))
            ->where('methodSummary.jobRoles.route', route('job-roles.index'))
            ->where('methodSummary.equipmentTypes.route', route('equipment-types.index'))
            ->where('methodSummary.workplaceTypes.route', route('workplace-types.index'))
            ->where('linksSummary.0.label', 'Mansione -> rischi')
            ->where('linksSummary.0.count', 1)
            ->where('linksSummary.1.count', 1)
            ->where('linksSummary.2.count', 1)
            ->where('linksSummary.3.status', 'Nel rischio')
            ->where('operationalBoundary.title', 'Cosa resta in Aziende')
        );
});
