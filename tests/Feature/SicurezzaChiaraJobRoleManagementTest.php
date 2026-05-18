<?php

use App\Actions\Tenant\CreateTenantWorkspace;
use App\Models\Company;
use App\Models\JobRole;
use App\Models\User;
use App\Models\Worker;
use Database\Seeders\SicurezzaChiaraCoreJobRolesSeeder;

test('authenticated users can create tenant job roles', function () {
    $user = User::factory()->create();
    $tenant = app(CreateTenantWorkspace::class)->handle($user, 'Studio Test');

    $this->actingAs($user);

    $response = $this->post(route('job-roles.store'), [
        'code' => 'TEN-QA',
        'name' => 'Responsabile qualita\'',
        'description' => 'Controllo processo.',
        'is_active' => true,
    ]);

    $jobRole = JobRole::query()->where('name', 'Responsabile qualita\'')->firstOrFail();

    $response->assertRedirect(route('job-roles.show', $jobRole));
    expect($jobRole->tenant_id)->toBe($tenant->id)
        ->and($jobRole->source)->toBe(JobRole::SOURCE_TENANT);
});

test('users can assign both core and tenant job roles to workers inside their tenant', function () {
    $this->seed(SicurezzaChiaraCoreJobRolesSeeder::class);

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

    $tenantRole = $tenant->jobRoles()->create([
        'source' => JobRole::SOURCE_TENANT,
        'code' => 'TEN-ASSY',
        'name' => 'Addetto assemblaggio',
        'is_active' => true,
    ]);

    $coreRole = JobRole::query()->where('source', JobRole::SOURCE_CORE)->where('name', 'Operatore di produzione')->firstOrFail();

    $this->actingAs($user)
        ->post(route('workers.job-role-assignments.store', $worker), [
            'job_role_id' => $tenantRole->id,
            'is_primary' => true,
            'assigned_on' => '2026-04-20',
            'notes' => 'Ruolo prevalente.',
        ])
        ->assertRedirect(route('workers.show', $worker));

    $this->post(route('workers.job-role-assignments.store', $worker), [
        'job_role_id' => $coreRole->id,
        'is_primary' => false,
        'assigned_on' => '2026-04-20',
        'notes' => 'Copertura secondaria.',
    ])->assertRedirect(route('workers.show', $worker));

    expect($worker->fresh()->jobRoleAssignments)->toHaveCount(2)
        ->and($worker->fresh()->jobRoleAssignments()->where('is_primary', true)->count())->toBe(1);
});

test('users cannot assign a tenant job role belonging to another tenant', function () {
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
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'status' => 'active',
    ]);

    $foreignRole = $otherTenant->jobRoles()->create([
        'source' => JobRole::SOURCE_TENANT,
        'name' => 'Coordinatore reparto',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post(route('workers.job-role-assignments.store', $worker), [
            'job_role_id' => $foreignRole->id,
            'is_primary' => true,
        ])
        ->assertNotFound();
});
