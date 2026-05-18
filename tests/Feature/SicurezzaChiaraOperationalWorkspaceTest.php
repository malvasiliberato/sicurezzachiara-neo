<?php

use App\Actions\Tenant\CreateTenantWorkspace;
use App\Models\Company;
use App\Models\RiskCatalogItem;
use App\Models\RiskMeasure;
use App\Models\RiskProfileItem;
use App\Models\User;
use Database\Seeders\SicurezzaChiaraShowcaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('dashboard exposes an operational workspace derived from risks and measures', function () {
    $this->seed(SicurezzaChiaraShowcaseSeeder::class);

    $user = User::query()->where('email', 'owner.showcase@sicurezzachiara.test')->firstOrFail();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/index')
            ->where('upcomingDeadlines.0.next_step.label', 'Verifica presidio in review')
            ->where('attentionMeasures.0.next_step.label', 'Verifica presidio in review')
            ->where('companySnapshots.0.decision.label', 'Chiudere scaduti')
        )
        ->assertSee('engineSummary', false)
        ->assertSee('engineFlow', false)
        ->assertSee('coreStarterPack', false)
        ->assertSee('coverageSignals', false)
        ->assertSee('decisionBoard', false)
        ->assertSee('prioritySignals', false)
        ->assertSee('portfolioHotspots', false)
        ->assertSee('pressureCategories', false)
        ->assertSee('suggestedCoreRisks', false)
        ->assertSee('expectedCoreMeasures', false)
        ->assertSee('coverage_rate', false)
        ->assertSee('missing_expected_measures', false)
        ->assertSee('companiesMonitored')
        ->assertSee('agendaItems')
        ->assertSee('ownersInAgenda')
        ->assertSee('companiesInAgenda')
        ->assertSee('timelineEvents')
        ->assertSee('reviewsDue')
        ->assertSee('followUpsOpen')
        ->assertSee('Verifica schermature e protezioni linea di taglio')
        ->assertSee('Uso prolungato di videoterminale')
        ->assertSee('Addestramento operativo su aree di schiacciamento')
        ->assertSee('Consolidare esito sopralluogo linea e chiudere verifica schermature entro la prossima review.')
        ->assertSee('Misura scaduta')
        ->assertSee('Giulia Ferri')
        ->assertSee('Da assegnare')
        ->assertSee('Metalnova S.r.l.')
        ->assertSee('Rischi meccanici')
        ->assertSee('Agire oggi')
        ->assertSee('Riallineare categorie')
        ->assertSee('Chiudere scaduti')
        ->assertSee('Apri registri in carico')
        ->assertSee('Chiudi scaduti nei registri')
        ->assertSee('Completa presidio in misure')
        ->assertSee('Scoperture attive da riallineare')
        ->assertSee('coreSourceInputs', false)
        ->assertSee('Aggancio diretto')
        ->assertSee('Copertura equivalente')
        ->assertSee('recordedOutcomes')
        ->assertSee('Valutazione consulente aggiornata')
        ->assertSee('Misura registrata')
        ->assertSee('Mantenere solo monitoraggio periodico su micro-pause e assetto postazioni.');
});

test('dashboard does not leak operational signals from foreign tenants', function () {
    $this->seed(SicurezzaChiaraShowcaseSeeder::class);

    $user = User::query()->where('email', 'owner.showcase@sicurezzachiara.test')->firstOrFail();

    $foreignUser = User::factory()->create([
        'email' => 'foreign.workspace@sicurezzachiara.test',
    ]);

    $foreignTenant = app(CreateTenantWorkspace::class)->handle($foreignUser, 'Tenant Esterno');

    $foreignCompany = Company::query()->create([
        'tenant_id' => $foreignTenant->id,
        'name' => 'Azienda Aliena S.p.A.',
    ]);

    $risk = RiskCatalogItem::query()->where('name', 'Schiacciamento e cesoiamento')->firstOrFail();

    RiskProfileItem::query()->create([
        'profileable_type' => Company::class,
        'profileable_id' => $foreignCompany->id,
        'risk_catalog_item_id' => $risk->id,
        'status' => RiskProfileItem::STATUS_UNCOVERED,
        'priority' => RiskCatalogItem::PRIORITY_HIGH,
        'source_count' => 1,
        'last_calculated_at' => now(),
    ]);

    RiskMeasure::query()->create([
        'profileable_type' => Company::class,
        'profileable_id' => $foreignCompany->id,
        'risk_catalog_item_id' => $risk->id,
        'family' => RiskMeasure::FAMILY_TECHNICAL,
        'title' => 'Misura aliena dashboard',
        'status' => RiskMeasure::STATUS_NOT_IMPLEMENTED,
        'due_date' => now()->addDays(2)->toDateString(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Misura aliena dashboard')
        ->assertDontSee('Azienda Aliena S.p.A.');
});

test('dashboard can focus on urgent operational items', function () {
    $this->seed(SicurezzaChiaraShowcaseSeeder::class);

    $user = User::query()->where('email', 'owner.showcase@sicurezzachiara.test')->firstOrFail();

    $this->actingAs($user)
        ->get(route('dashboard', ['focus' => 'urgent']))
        ->assertOk()
        ->assertSee('Urgenti')
        ->assertSee('Verifica schermature e protezioni linea di taglio')
        ->assertSee('Misura scaduta')
        ->assertDontSee('Mantenere solo monitoraggio periodico su micro-pause e assetto postazioni.');
});

test('dashboard can focus on follow-up signals and outcomes', function () {
    $this->seed(SicurezzaChiaraShowcaseSeeder::class);

    $user = User::query()->where('email', 'owner.showcase@sicurezzachiara.test')->firstOrFail();

    $this->actingAs($user)
        ->get(route('dashboard', ['focus' => 'follow_up']))
        ->assertOk()
        ->assertSee('Follow-up')
        ->assertSee('Apri follow-up in carico')
        ->assertSee('Follow-up aperti', false)
        ->assertSee('workspace_route', false)
        ->assertSee('origin=dashboard', false)
        ->assertSee('company_id=', false)
        ->assertSee('Consolidare esito sopralluogo linea e chiudere verifica schermature entro la prossima review.')
        ->assertSee('Mantenere solo monitoraggio periodico su micro-pause e assetto postazioni.')
        ->assertDontSee('Review scaduta');
});

test('dashboard exposes direct operational lanes for deadlines follow-ups and reviews', function () {
    $this->seed(SicurezzaChiaraShowcaseSeeder::class);

    $user = User::query()->where('email', 'owner.showcase@sicurezzachiara.test')->firstOrFail();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Scaduti da chiudere')
        ->assertSee('Follow-up in carico')
        ->assertSee('Review da riallineare')
        ->assertSee('scope=overdue', false)
        ->assertSee('family=follow_up', false)
        ->assertSee('focus=reviews', false)
        ->assertSee('review_route', false)
        ->assertSee('profile_route', false)
        ->assertSee('company_route', false);
});
