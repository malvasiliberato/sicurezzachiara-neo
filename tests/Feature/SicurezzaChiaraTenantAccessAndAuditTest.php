<?php

use App\Actions\Tenant\CreateTenantWorkspace;
use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\CompanySite;
use App\Models\JobRole;
use App\Models\RiskCatalogItem;
use App\Models\RiskMeasure;
use App\Models\RiskProfileItem;
use App\Models\RiskSourceLink;
use App\Models\TenantMembership;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerJobRoleAssignment;
use App\Support\RiskProfileBuilder;
use Database\Seeders\SicurezzaChiaraCoreEquipmentTypesSeeder;
use Database\Seeders\SicurezzaChiaraCoreJobRolesSeeder;
use Database\Seeders\SicurezzaChiaraCoreRiskCategoriesSeeder;
use Database\Seeders\SicurezzaChiaraCoreRisksSeeder;
use Database\Seeders\SicurezzaChiaraCoreWorkplaceTypesSeeder;

function createConsultiveMembershipFixture(): array
{
    $owner = User::factory()->create();
    $tenant = app(CreateTenantWorkspace::class)->handle($owner, 'Studio Test');

    $member = User::factory()->create();

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $member->id,
        'role' => TenantMembership::ROLE_MEMBER,
        'joined_at' => now(),
    ]);

    $member->forceFill([
        'current_tenant_id' => $tenant->id,
    ])->save();

    $company = Company::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Metalnova S.r.l.',
    ]);

    return compact('owner', 'tenant', 'member', 'company');
}

function createRiskMeasureAuditFixture(): array
{
    test()->seed([
        SicurezzaChiaraCoreJobRolesSeeder::class,
        SicurezzaChiaraCoreEquipmentTypesSeeder::class,
        SicurezzaChiaraCoreWorkplaceTypesSeeder::class,
        SicurezzaChiaraCoreRiskCategoriesSeeder::class,
        SicurezzaChiaraCoreRisksSeeder::class,
    ]);

    $user = User::factory()->create();
    $tenant = app(CreateTenantWorkspace::class)->handle($user, 'Studio Audit');

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

    return compact('user', 'tenant', 'company', 'worker', 'profileItem');
}

test('tenant members with consultive role can read but cannot mutate tenant data', function () {
    ['member' => $member, 'company' => $company] = createConsultiveMembershipFixture();

    $this->actingAs($member)
        ->get(route('companies.index'))
        ->assertOk();

    $this->get(route('companies.show', $company))
        ->assertOk();

    $this->get(route('companies.create'))
        ->assertForbidden();

    $this->post(route('companies.store'), [
        'name' => 'Cliente non consentito',
    ])->assertForbidden();
});

test('company mutations produce audit events inside the current tenant', function () {
    $user = User::factory()->create();
    $tenant = app(CreateTenantWorkspace::class)->handle($user, 'Studio Audit');

    $this->actingAs($user)
        ->post(route('companies.store'), [
            'name' => 'Metalnova S.r.l.',
            'legal_name' => 'Metalnova S.r.l.',
            'industry' => 'Metalmeccanica',
        ])
        ->assertRedirect();

    $company = Company::query()->firstOrFail();

    expect(AuditEvent::query()
        ->where('tenant_id', $tenant->id)
        ->where('action', 'company.created')
        ->where('auditable_type', $company->getMorphClass())
        ->where('auditable_id', $company->id)
        ->exists())->toBeTrue();
});

test('risk measure mutations produce audit events', function () {
    ['user' => $user, 'tenant' => $tenant, 'worker' => $worker, 'profileItem' => $profileItem] = createRiskMeasureAuditFixture();

    $this->actingAs($user)
        ->post(route('workers.risk-profile.measures.store', [$worker, $profileItem]), [
            'family' => RiskMeasure::FAMILY_TRAINING,
            'title' => 'Addestramento supplementare',
            'description' => 'Presidio formativo da registrare.',
            'status' => RiskMeasure::STATUS_TO_VERIFY,
            'details' => [
                'provider' => 'Studio Audit',
                'delivery_mode' => 'Aula',
                'valid_until' => '2027-06-30',
            ],
            'due_date' => '2026-06-30',
        ])
        ->assertRedirect();

    $measure = RiskMeasure::query()->where('title', 'Addestramento supplementare')->firstOrFail();

    expect(AuditEvent::query()
        ->where('tenant_id', $tenant->id)
        ->where('action', 'risk_measure.created')
        ->where('auditable_type', $measure->getMorphClass())
        ->where('auditable_id', $measure->id)
        ->exists())->toBeTrue();
});
