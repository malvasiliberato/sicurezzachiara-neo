<?php

use App\Actions\Tenant\CreateTenantWorkspace;
use App\Models\EquipmentType;
use App\Models\JobRole;
use App\Models\RiskCatalogItem;
use App\Models\RiskCategory;
use App\Models\RiskMeasure;
use App\Models\RiskSourceLink;
use App\Models\User;
use App\Models\WorkplaceType;
use Database\Seeders\SicurezzaChiaraCoreEquipmentTypesSeeder;
use Database\Seeders\SicurezzaChiaraCoreJobRolesSeeder;
use Database\Seeders\SicurezzaChiaraCoreRiskCategoriesSeeder;
use Database\Seeders\SicurezzaChiaraCoreRisksSeeder;
use Database\Seeders\SicurezzaChiaraCoreWorkplaceTypesSeeder;

test('authenticated users can create tenant risks and map them to domain sources', function () {
    $this->seed(SicurezzaChiaraCoreJobRolesSeeder::class);
    $this->seed(SicurezzaChiaraCoreEquipmentTypesSeeder::class);
    $this->seed(SicurezzaChiaraCoreWorkplaceTypesSeeder::class);
    $this->seed(SicurezzaChiaraCoreRiskCategoriesSeeder::class);
    $this->seed(SicurezzaChiaraCoreRisksSeeder::class);

    $user = User::factory()->create();
    $tenant = app(CreateTenantWorkspace::class)->handle($user, 'Studio Test');

    $jobRole = $tenant->jobRoles()->create([
        'source' => JobRole::SOURCE_TENANT,
        'name' => 'Coordinatore magazzino',
        'is_active' => true,
    ]);

    $this->actingAs($user);

    $response = $this->post(route('risk-catalog.store'), [
        'risk_category_id' => RiskCategory::query()->where('name', 'Rischi organizzativi')->firstOrFail()->id,
        'code' => 'TEN-INT',
        'name' => 'Interferenze di reparto',
        'description' => 'Rischio tenant-level di interferenze interne.',
        'expected_measures' => [
            [
                'code' => 'segnaletica',
                'family' => RiskMeasure::FAMILY_ORGANIZATIONAL,
                'title' => 'Segnaletica e separazione percorsi',
                'description' => 'Presidio atteso minimo per la viabilita\' interna.',
                'is_required' => true,
            ],
        ],
        'default_priority' => RiskCatalogItem::PRIORITY_HIGH,
        'is_active' => true,
    ]);

    $risk = RiskCatalogItem::query()->where('name', 'Interferenze di reparto')->firstOrFail();

    $response->assertRedirect(route('risk-catalog.show', $risk));

    $this->post(route('risk-catalog.source-links.store', $risk), [
        'source_family' => 'job_role',
        'sourceable_id' => $jobRole->id,
        'relevance' => RiskSourceLink::RELEVANCE_PRIMARY,
        'notes' => 'Mapping iniziale.',
    ])->assertRedirect(route('risk-catalog.show', $risk));

    expect($risk->tenant_id)->toBe($tenant->id)
        ->and($risk->sourceLinks()->count())->toBe(1)
        ->and($risk->expected_measures)->toHaveCount(1)
        ->and($risk->expected_measures[0]['code'])->toBe('segnaletica');
});

test('users can map core risks to core equipment and workplace source catalogs visible to their tenant', function () {
    $this->seed(SicurezzaChiaraCoreEquipmentTypesSeeder::class);
    $this->seed(SicurezzaChiaraCoreWorkplaceTypesSeeder::class);
    $this->seed(SicurezzaChiaraCoreRiskCategoriesSeeder::class);
    $this->seed(SicurezzaChiaraCoreRisksSeeder::class);

    $user = User::factory()->create();
    app(CreateTenantWorkspace::class)->handle($user, 'Studio Test');

    $risk = RiskCatalogItem::query()->where('name', 'Circolazione mezzi e interferenze')->firstOrFail();
    $equipmentType = EquipmentType::query()->where('name', 'Carrello elevatore')->firstOrFail();
    $workplaceType = WorkplaceType::query()->where('name', 'Area di magazzino')->firstOrFail();

    $this->actingAs($user)
        ->post(route('risk-catalog.source-links.store', $risk), [
            'source_family' => 'equipment_type',
            'sourceable_id' => $equipmentType->id,
            'relevance' => RiskSourceLink::RELEVANCE_PRIMARY,
        ])
        ->assertRedirect(route('risk-catalog.show', $risk));

    $this->post(route('risk-catalog.source-links.store', $risk), [
        'source_family' => 'workplace_type',
        'sourceable_id' => $workplaceType->id,
        'relevance' => RiskSourceLink::RELEVANCE_SECONDARY,
    ])->assertRedirect(route('risk-catalog.show', $risk));

    expect($risk->fresh()->sourceLinks)->toHaveCount(2);
});

test('users cannot map tenant risks to foreign tenant sources', function () {
    $this->seed(SicurezzaChiaraCoreRiskCategoriesSeeder::class);

    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $tenant = app(CreateTenantWorkspace::class)->handle($user, 'Studio Uno');
    $otherTenant = app(CreateTenantWorkspace::class)->handle($otherUser, 'Studio Due');

    $risk = $tenant->riskCatalogItems()->create([
        'risk_category_id' => RiskCategory::query()->where('name', 'Rischi organizzativi')->firstOrFail()->id,
        'source' => RiskCatalogItem::SOURCE_TENANT,
        'name' => 'Interferenze reparto',
        'default_priority' => RiskCatalogItem::PRIORITY_MEDIUM,
        'is_active' => true,
    ]);

    $foreignJobRole = $otherTenant->jobRoles()->create([
        'source' => JobRole::SOURCE_TENANT,
        'name' => 'Responsabile area',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post(route('risk-catalog.source-links.store', $risk), [
            'source_family' => 'job_role',
            'sourceable_id' => $foreignJobRole->id,
            'relevance' => RiskSourceLink::RELEVANCE_PRIMARY,
        ])
        ->assertNotFound();
});
