<?php

use App\Actions\Tenant\CreateTenantWorkspace;
use App\Models\Company;
use App\Models\RiskCatalogItem;
use App\Models\RiskMeasure;
use App\Models\RiskProfileItem;
use App\Models\User;
use Database\Seeders\SicurezzaChiaraShowcaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('company dvr initial report is derived from real company risk and measure data', function () {
    $this->seed(SicurezzaChiaraShowcaseSeeder::class);

    $user = User::query()->where('email', 'owner.showcase@sicurezzachiara.test')->firstOrFail();
    $company = Company::query()->where('name', 'Metalnova S.r.l.')->firstOrFail();

    $this->actingAs($user)
        ->get(route('companies.dvr.show', $company))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sicurezzachiara/reports/CompanyDvr')
            ->where('documentScope.title', 'Perimetro letto dal DVR light')
            ->where('documentScope.status', 'Da completare')
            ->where('summary.workersWithoutPrimarySite', 0)
            ->where('summary.workersWithoutPrimaryJobRole', 0)
            ->where('contextBridge.actions.companyRoute', route('companies.show', $company))
            ->where('contextBridge.actions.registryRoute', route('measure-registries.index', [
                'company_id' => $company->id,
                'origin' => 'company_dvr',
                'focus' => 'follow_up',
                'scope' => 'follow_up_open',
                'family' => 'follow_up',
            ]))
            ->has('documentScope.items', 4)
            ->etc()
        )
        ->assertSee('engine', false)
        ->assertSee('flow', false)
        ->assertSee('coverageRate', false)
        ->assertSee('coreStarterPack', false)
        ->assertSee('coverageSignals', false)
        ->assertSee('suggestedRisksCount', false)
        ->assertSee('expectedMeasuresCount', false)
        ->assertSee('directExpectedMeasures', false)
        ->assertSee('Verifica schermature e protezioni linea di taglio')
        ->assertSee('Addestramento operativo su aree di schiacciamento')
        ->assertSee('Schiacciamento e cesoiamento')
        ->assertSee('review_due_at')
        ->assertSee('follow_up_status')
        ->assertSee('follow_up_outcome_status')
        ->assertSee('followUpsClosed')
        ->assertSee('timelineEntries')
        ->assertSee('Misura registrata')
        ->assertSee('Giulia Ferri')
        ->assertSee('Marco Rossi');
});

test('company dvr initial report does not allow cross-tenant access', function () {
    $this->seed(SicurezzaChiaraShowcaseSeeder::class);

    $user = User::factory()->create([
        'email' => 'outsider.dvr@sicurezzachiara.test',
    ]);

    $tenant = app(CreateTenantWorkspace::class)->handle($user, 'Tenant Outsider');

    $company = Company::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Societa Outsider',
    ]);

    $risk = RiskCatalogItem::query()->where('name', 'Schiacciamento e cesoiamento')->firstOrFail();

    RiskProfileItem::query()->create([
        'profileable_type' => Company::class,
        'profileable_id' => $company->id,
        'risk_catalog_item_id' => $risk->id,
        'status' => RiskProfileItem::STATUS_UNCOVERED,
        'priority' => RiskCatalogItem::PRIORITY_HIGH,
        'source_count' => 1,
        'last_calculated_at' => now(),
    ]);

    RiskMeasure::query()->create([
        'profileable_type' => Company::class,
        'profileable_id' => $company->id,
        'risk_catalog_item_id' => $risk->id,
        'family' => RiskMeasure::FAMILY_TECHNICAL,
        'title' => 'Misura outsider DVR',
        'status' => RiskMeasure::STATUS_TO_VERIFY,
        'due_date' => now()->addDays(4)->toDateString(),
    ]);

    $showcaseOwner = User::query()->where('email', 'owner.showcase@sicurezzachiara.test')->firstOrFail();

    $this->actingAs($showcaseOwner)
        ->get(route('companies.dvr.show', $company))
        ->assertNotFound();
});
