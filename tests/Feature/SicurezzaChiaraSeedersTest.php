<?php

use App\Models\Company;
use App\Models\EquipmentAsset;
use App\Models\EquipmentType;
use App\Models\JobRole;
use App\Models\RiskCatalogItem;
use App\Models\RiskCategory;
use App\Models\RiskMeasure;
use App\Models\RiskProfileItem;
use App\Models\RiskSourceLink;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerEquipmentExposure;
use App\Models\WorkerWorkplaceExposure;
use App\Models\Workplace;
use App\Models\WorkplaceType;
use Database\Seeders\SicurezzaChiaraBaselineSeeder;
use Database\Seeders\SicurezzaChiaraCoreEquipmentTypesSeeder;
use Database\Seeders\SicurezzaChiaraCoreJobRolesSeeder;
use Database\Seeders\SicurezzaChiaraCoreRiskCategoriesSeeder;
use Database\Seeders\SicurezzaChiaraCoreRisksSeeder;
use Database\Seeders\SicurezzaChiaraCoreSourceRiskMappingsSeeder;
use Database\Seeders\SicurezzaChiaraCoreWorkplaceTypesSeeder;
use Database\Seeders\SicurezzaChiaraShowcaseSeeder;

test('baseline seeder creates a minimal readable tenant scenario', function () {
    $this->seed(SicurezzaChiaraCoreJobRolesSeeder::class);
    $this->seed(SicurezzaChiaraCoreEquipmentTypesSeeder::class);
    $this->seed(SicurezzaChiaraCoreWorkplaceTypesSeeder::class);
    $this->seed(SicurezzaChiaraCoreRiskCategoriesSeeder::class);
    $this->seed(SicurezzaChiaraCoreRisksSeeder::class);
    $this->seed(SicurezzaChiaraCoreSourceRiskMappingsSeeder::class);
    $this->seed(SicurezzaChiaraBaselineSeeder::class);

    $tenant = Tenant::query()->where('slug', 'studio-sicurezza-chiara-baseline')->firstOrFail();
    $owner = User::query()->where('email', 'owner.baseline@sicurezzachiara.test')->firstOrFail();

    expect($tenant->owner_user_id)->toBe($owner->id)
        ->and($owner->current_tenant_id)->toBe($tenant->id)
        ->and($tenant->companies()->count())->toBe(3)
        ->and($tenant->memberships()->count())->toBe(1)
        ->and($tenant->jobRoles()->count())->toBe(2)
        ->and($tenant->workers()->count())->toBe(2)
        ->and($tenant->equipmentTypes()->count())->toBe(1)
        ->and($tenant->workplaceTypes()->count())->toBe(1)
        ->and($tenant->riskCatalogItems()->count())->toBe(1);

    $companies = Company::query()
        ->where('tenant_id', $tenant->id)
        ->withCount('sites')
        ->orderBy('name')
        ->get();

    expect($companies->sum('sites_count'))->toBe(4)
        ->and($companies->firstWhere('name', 'Metalnova S.r.l.')?->sites_count)->toBe(2);

    expect(JobRole::query()->where('source', JobRole::SOURCE_CORE)->count())->toBe(12)
        ->and(EquipmentType::query()->where('source', EquipmentType::SOURCE_CORE)->count())->toBe(10)
        ->and(WorkplaceType::query()->where('source', WorkplaceType::SOURCE_CORE)->count())->toBe(10)
        ->and(RiskCategory::query()->count())->toBe(8)
        ->and(RiskCatalogItem::query()->where('source', RiskCatalogItem::SOURCE_CORE)->count())->toBe(15)
        ->and(EquipmentAsset::query()->count())->toBe(2)
        ->and(Workplace::query()->count())->toBe(2)
        ->and(WorkerEquipmentExposure::query()->count())->toBe(1)
        ->and(WorkerWorkplaceExposure::query()->count())->toBe(1)
        ->and(RiskSourceLink::query()->count())->toBeGreaterThanOrEqual(35)
        ->and(RiskMeasure::query()->count())->toBe(2)
        ->and(RiskProfileItem::query()->whereNotNull('follow_up_status')->count())->toBeGreaterThan(0);
});

test('showcase seeder creates a richer multi-company scenario with an additional membership', function () {
    $this->seed(SicurezzaChiaraCoreJobRolesSeeder::class);
    $this->seed(SicurezzaChiaraCoreEquipmentTypesSeeder::class);
    $this->seed(SicurezzaChiaraCoreWorkplaceTypesSeeder::class);
    $this->seed(SicurezzaChiaraCoreRiskCategoriesSeeder::class);
    $this->seed(SicurezzaChiaraCoreRisksSeeder::class);
    $this->seed(SicurezzaChiaraCoreSourceRiskMappingsSeeder::class);
    $this->seed(SicurezzaChiaraShowcaseSeeder::class);

    $tenant = Tenant::query()->where('slug', 'sicurezzachiara-showcase')->firstOrFail();

    expect($tenant->companies()->count())->toBe(4)
        ->and($tenant->memberships()->count())->toBe(2)
        ->and($tenant->jobRoles()->count())->toBe(3)
        ->and($tenant->workers()->count())->toBe(4)
        ->and($tenant->equipmentTypes()->count())->toBe(2)
        ->and($tenant->workplaceTypes()->count())->toBe(2)
        ->and($tenant->riskCatalogItems()->count())->toBe(2);

    $roles = TenantMembership::query()
        ->where('tenant_id', $tenant->id)
        ->pluck('role')
        ->sort()
        ->values()
        ->all();

    expect($roles)->toBe([
        TenantMembership::ROLE_ADMIN,
        TenantMembership::ROLE_OWNER,
    ]);

    $companies = Company::query()
        ->where('tenant_id', $tenant->id)
        ->withCount('sites')
        ->get();

    expect($companies->firstWhere('name', 'Verde Pulito Coop.')?->sites_count)->toBe(0)
        ->and($companies->firstWhere('name', 'Logiport Services S.r.l.')?->sites_count)->toBe(3);

    expect(Worker::query()->whereHas('jobRoleAssignments', fn ($query) => $query->where('is_primary', true))->count())->toBe(4)
        ->and(EquipmentAsset::query()->count())->toBe(3)
        ->and(Workplace::query()->count())->toBe(3)
        ->and(WorkerEquipmentExposure::query()->count())->toBe(2)
        ->and(WorkerWorkplaceExposure::query()->count())->toBe(3)
        ->and(RiskSourceLink::query()->count())->toBeGreaterThanOrEqual(37)
        ->and(RiskMeasure::query()->count())->toBe(5)
        ->and(RiskProfileItem::query()->whereNotNull('follow_up_status')->count())->toBeGreaterThanOrEqual(2)
        ->and(RiskProfileItem::query()->whereNotNull('follow_up_outcome_status')->count())->toBeGreaterThanOrEqual(1);
});

test('core starter pack exposes prudential source mappings and expected measure families', function () {
    $this->seed([
        SicurezzaChiaraCoreJobRolesSeeder::class,
        SicurezzaChiaraCoreEquipmentTypesSeeder::class,
        SicurezzaChiaraCoreWorkplaceTypesSeeder::class,
        SicurezzaChiaraCoreRiskCategoriesSeeder::class,
        SicurezzaChiaraCoreRisksSeeder::class,
        SicurezzaChiaraCoreSourceRiskMappingsSeeder::class,
    ]);

    $noiseRisk = RiskCatalogItem::query()->where('name', 'Esposizione a rumore')->firstOrFail();
    $chemicalRisk = RiskCatalogItem::query()->where('name', 'Esposizione ad agenti chimici')->firstOrFail();
    $officeRole = JobRole::query()->where('name', 'Impiegato amministrativo')->firstOrFail();
    $warehouseArea = WorkplaceType::query()->where('name', 'Area di magazzino')->firstOrFail();
    $forklift = EquipmentType::query()->where('name', 'Carrello elevatore')->firstOrFail();

    expect($noiseRisk->expected_measures)->toHaveCount(3)
        ->and(collect($noiseRisk->expected_measures)->pluck('family')->all())->toContain(
            RiskMeasure::FAMILY_DPI,
            RiskMeasure::FAMILY_MEDICAL,
            RiskMeasure::FAMILY_TRAINING,
        )
        ->and($chemicalRisk->expected_measures)->toHaveCount(4)
        ->and(collect($chemicalRisk->expected_measures)->pluck('family')->all())->toContain(
            RiskMeasure::FAMILY_DPI,
            RiskMeasure::FAMILY_TRAINING,
            RiskMeasure::FAMILY_ORGANIZATIONAL,
        );

    expect(RiskSourceLink::query()
        ->where('sourceable_type', JobRole::class)
        ->where('sourceable_id', $officeRole->id)
        ->whereHas('riskCatalogItem', fn ($query) => $query->where('name', 'Uso prolungato di videoterminale'))
        ->exists())->toBeTrue()
        ->and(RiskSourceLink::query()
            ->where('sourceable_type', WorkplaceType::class)
            ->where('sourceable_id', $warehouseArea->id)
            ->whereHas('riskCatalogItem', fn ($query) => $query->where('name', 'Circolazione mezzi e interferenze'))
            ->exists())->toBeTrue()
        ->and(RiskSourceLink::query()
            ->where('sourceable_type', EquipmentType::class)
            ->where('sourceable_id', $forklift->id)
            ->whereHas('riskCatalogItem', fn ($query) => $query->where('name', 'Vibrazioni meccaniche'))
            ->exists())->toBeTrue();
});
