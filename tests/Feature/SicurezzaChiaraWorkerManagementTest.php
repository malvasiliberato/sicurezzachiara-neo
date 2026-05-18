<?php

use App\Actions\Tenant\CreateTenantWorkspace;
use App\Models\Company;
use App\Models\CompanySite;
use App\Models\User;
use App\Models\Worker;
use Database\Seeders\SicurezzaChiaraShowcaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated users can create and update workers inside their current tenant', function () {
    $user = User::factory()->create();

    app(CreateTenantWorkspace::class)->handle($user, 'Studio Test');

    $this->actingAs($user);

    $company = Company::query()->create([
        'tenant_id' => $user->fresh()->current_tenant_id,
        'name' => 'Metalnova S.r.l.',
    ]);

    $site = CompanySite::query()->create([
        'company_id' => $company->id,
        'name' => 'Stabilimento principale',
        'site_code' => 'HQ',
        'is_headquarters' => true,
    ]);

    $createResponse = $this->post(route('workers.store'), [
        'company_id' => $company->id,
        'primary_site_id' => $site->id,
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'tax_code' => 'RSSMRA80A01F257X',
        'email' => 'mario.rossi@example.test',
        'phone' => '3490000000',
        'birth_date' => '1980-01-01',
        'hire_date' => '2024-01-15',
        'status' => 'active',
        'notes' => 'Addetto area produttiva.',
    ]);

    $worker = Worker::query()->firstOrFail();

    $createResponse->assertRedirect(route('workers.show', $worker));
    expect($worker->company_id)->toBe($company->id)
        ->and($worker->primary_site_id)->toBe($site->id);

    $updateResponse = $this->put(route('workers.update', $worker), [
        'company_id' => $company->id,
        'primary_site_id' => $site->id,
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'tax_code' => 'RSSMRA80A01F257X',
        'email' => 'mario.rossi@example.test',
        'phone' => '3490000000',
        'birth_date' => '1980-01-01',
        'hire_date' => '2024-01-15',
        'status' => 'inactive',
        'notes' => 'Profilo sospeso.',
    ]);

    $updateResponse->assertRedirect(route('workers.show', $worker));
    expect($worker->fresh()->status)->toBe('inactive');
});

test('worker primary site must belong to the selected company', function () {
    $user = User::factory()->create();

    app(CreateTenantWorkspace::class)->handle($user, 'Studio Test');

    $this->actingAs($user);

    $tenantId = $user->fresh()->current_tenant_id;

    $company = Company::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Metalnova S.r.l.',
    ]);

    $otherCompany = Company::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Logiport S.r.l.',
    ]);

    $wrongSite = CompanySite::query()->create([
        'company_id' => $otherCompany->id,
        'name' => 'Magazzino Nord',
        'is_headquarters' => false,
    ]);

    $response = $this->from(route('workers.create'))->post(route('workers.store'), [
        'company_id' => $company->id,
        'primary_site_id' => $wrongSite->id,
        'first_name' => 'Laura',
        'last_name' => 'Bianchi',
        'status' => 'active',
    ]);

    $response->assertRedirect(route('workers.create'));
    $response->assertSessionHasErrors(['primary_site_id']);
    expect(Worker::query()->count())->toBe(0);
});

test('users cannot open workers belonging to another tenant', function () {
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
        ->get(route('workers.show', $worker))
        ->assertNotFound();
});

test('worker detail surfaces contextual core starter pack signals', function () {
    $this->seed(SicurezzaChiaraShowcaseSeeder::class);

    $user = User::query()->where('email', 'owner.showcase@sicurezzachiara.test')->firstOrFail();
    $worker = Worker::query()->where('tax_code', 'RSSMRC82B10F257K')->firstOrFail();

    $this->actingAs($user)
        ->get(route('workers.show', $worker))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sicurezzachiara/workers/Show')
            ->where('contextBridge.focus', 'all')
            ->where('contextBridge.suggestedAction.label', 'Verifica la copertura del profilo')
            ->has('governanceContext.summary')
            ->has('governanceContext.previewMeasures')
            ->has('governanceContext.reviewAlerts')
            ->where('contextBridge.actions.registryRoute', route('measure-registries.index', [
                'company_id' => $worker->company_id,
                'origin' => 'worker_show',
                'focus' => 'all',
                'scope' => 'attention',
            ]))
        )
        ->assertSee('coreStarterPack', false)
        ->assertSee('contextBridge', false)
        ->assertSee('suggestedRisksCount', false)
        ->assertSee('expectedMeasuresCount', false)
        ->assertSee('Operatore di produzione')
        ->assertSee('Schiacciamento e cesoiamento');
});

test('worker create and edit preserve company workflow context when opened from an azienda', function () {
    $user = User::factory()->create();

    app(CreateTenantWorkspace::class)->handle($user, 'Studio Test');

    $this->actingAs($user);

    $company = Company::query()->create([
        'tenant_id' => $user->fresh()->current_tenant_id,
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

    $this->get(route('workers.create', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sicurezzachiara/workers/Create')
            ->where('defaults.company_id', $company->id)
            ->where('companyContext.name', 'Metalnova S.r.l.')
            ->where('companyContext.workersRoute', route('workers.index', ['company_id' => $company->id]))
            ->where('companyContext.showRoute', route('companies.show', $company))
        );

    $this->get(route('workers.edit', $worker))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sicurezzachiara/workers/Edit')
            ->where('companyContext.name', 'Metalnova S.r.l.')
            ->where('companyContext.workersRoute', route('workers.index', ['company_id' => $company->id]))
            ->where('companyContext.showRoute', route('companies.show', $company))
        );

    $this->get(route('workers.show', $worker))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sicurezzachiara/workers/Show')
            ->where('contextBridge.actions.workersRoute', route('workers.index', ['company_id' => $company->id]))
        );
});
