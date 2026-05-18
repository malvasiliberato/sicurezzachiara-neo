<?php

use App\Models\Company;
use App\Models\RiskMeasure;
use App\Models\RiskProfileItem;
use App\Models\User;
use App\Models\Worker;
use Database\Seeders\SicurezzaChiaraShowcaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('measure registry groups specialized families inside a single workspace', function () {
    $this->seed(SicurezzaChiaraShowcaseSeeder::class);

    $user = User::query()->where('email', 'owner.showcase@sicurezzachiara.test')->firstOrFail();

    $this->actingAs($user)
        ->get(route('measure-registries.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sicurezzachiara/measure-registries/Index')
            ->where('copy.workspaceTitle', 'Registri famiglia misure')
            ->where('copy.workspaceHelper', 'Workspace unico delle misure collegate ai rischi. Le tab DPI, formazione e visite mediche filtrano la stessa base operativa per famiglia.')
            ->where('copy.familyColumnLabel', 'Famiglia')
        )
        ->assertSee('coreStarterPack', false)
        ->assertSee('Addestramento operativo su aree di schiacciamento')
        ->assertSee('Sorveglianza sanitaria mansione di produzione')
        ->assertSee('Aggancio diretto')
        ->assertSee('Copertura equivalente')
        ->assertSee('Misura libera');
});

test('measure registry can filter by family without leaking other registers', function () {
    $this->seed(SicurezzaChiaraShowcaseSeeder::class);

    $user = User::query()->where('email', 'owner.showcase@sicurezzachiara.test')->firstOrFail();

    $this->actingAs($user)
        ->get(route('measure-registries.index', ['family' => RiskMeasure::FAMILY_TRAINING]))
        ->assertOk()
        ->assertSee('Addestramento operativo su aree di schiacciamento')
        ->assertDontSee('Sorveglianza sanitaria mansione di produzione');

    $this->get(route('measure-registries.index', ['family' => RiskMeasure::FAMILY_MEDICAL]))
        ->assertOk()
        ->assertSee('Sorveglianza sanitaria mansione di produzione')
        ->assertDontSee('Consegna DPI alta visibilita');
});

test('measure registry can focus on measures linked to risks currently in follow-up', function () {
    $this->seed(SicurezzaChiaraShowcaseSeeder::class);

    $user = User::query()->where('email', 'owner.showcase@sicurezzachiara.test')->firstOrFail();

    $this->actingAs($user)
        ->get(route('measure-registries.index', ['family' => 'follow_up']))
        ->assertOk()
        ->assertSee('Verifica schermature e protezioni linea di taglio')
        ->assertSee('Giulia Ferri')
        ->assertSee('Consolidare esito sopralluogo linea e chiudere verifica schermature entro la prossima review.')
        ->assertDontSee('Sorveglianza sanitaria mansione di produzione');
});

test('measure registry can filter measures by company context', function () {
    $this->seed(SicurezzaChiaraShowcaseSeeder::class);

    $user = User::query()->where('email', 'owner.showcase@sicurezzachiara.test')->firstOrFail();
    $company = Company::query()->where('name', 'Metalnova S.r.l.')->firstOrFail();

    $this->actingAs($user)
        ->get(route('measure-registries.index', ['company_id' => $company->id]))
        ->assertOk()
        ->assertSee('Verifica schermature e protezioni linea di taglio')
        ->assertSee('Addestramento operativo su aree di schiacciamento')
        ->assertDontSee('Consegna DPI alta visibilita');
});

test('measure registry can filter measures by operational owner', function () {
    $this->seed(SicurezzaChiaraShowcaseSeeder::class);

    $user = User::query()->where('email', 'owner.showcase@sicurezzachiara.test')->firstOrFail();
    $owner = User::query()->where('email', 'collaboratore.showcase@sicurezzachiara.test')->firstOrFail();

    $this->actingAs($user)
        ->get(route('measure-registries.index', ['owner_user_id' => $owner->id]))
        ->assertOk()
        ->assertSee('Verifica schermature e protezioni linea di taglio')
        ->assertDontSee('Addestramento operativo su aree di schiacciamento');
});

test('measure registry can focus on overdue attention items only', function () {
    $this->seed(SicurezzaChiaraShowcaseSeeder::class);

    $user = User::query()->where('email', 'owner.showcase@sicurezzachiara.test')->firstOrFail();

    $this->actingAs($user)
        ->get(route('measure-registries.index', ['scope' => 'overdue']))
        ->assertOk()
        ->assertSee('Verifica schermature e protezioni linea di taglio')
        ->assertDontSee('Addestramento operativo su aree di schiacciamento')
        ->assertDontSee('Consegna DPI alta visibilita');
});

test('measure registry preserves dashboard workspace context with combined filters', function () {
    $this->seed(SicurezzaChiaraShowcaseSeeder::class);

    $user = User::query()->where('email', 'owner.showcase@sicurezzachiara.test')->firstOrFail();
    $company = Company::query()->where('name', 'Metalnova S.r.l.')->firstOrFail();
    $owner = User::query()->where('email', 'collaboratore.showcase@sicurezzachiara.test')->firstOrFail();
    $measure = RiskMeasure::query()->where('title', 'Verifica schermature e protezioni linea di taglio')->firstOrFail();
    $profileItem = RiskProfileItem::query()
        ->where('profileable_type', $measure->profileable_type)
        ->where('profileable_id', $measure->profileable_id)
        ->where('risk_catalog_item_id', $measure->risk_catalog_item_id)
        ->firstOrFail();
    $measuresRoute = $measure->profileable_type === Worker::class
        ? route('workers.risk-profile.measures.show', [$measure->profileable_id, $profileItem->id])
        : route('companies.risk-profile.measures.show', [$measure->profileable_id, $profileItem->id]);

    $this->actingAs($user)
        ->get(route('measure-registries.index', [
            'family' => 'follow_up',
            'scope' => 'follow_up_open',
            'company_id' => $company->id,
            'owner_user_id' => $owner->id,
            'origin' => 'dashboard',
            'focus' => 'follow_up',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sicurezzachiara/measure-registries/Index')
            ->where('measures.0.next_step.label', 'Segui follow-up in review')
            ->where('measures.0.measures_route', $measuresRoute)
            ->where('measures.0.bridge_summary', fn (string $value) => str_contains($value, 'follow-up operativo aperto'))
        )
        ->assertSee('workspaceContext', false)
        ->assertSee('origin=dashboard', false)
        ->assertSee('focus=follow_up', false)
        ->assertSee('Verifica schermature e protezioni linea di taglio')
        ->assertSee('Segui follow-up in review')
        ->assertSee('measures_route', false)
        ->assertDontSee('Consegna DPI alta visibilita');
});

test('measure registry exposes quick contextual shortcuts for the current workspace', function () {
    $this->seed(SicurezzaChiaraShowcaseSeeder::class);

    $user = User::query()->where('email', 'owner.showcase@sicurezzachiara.test')->firstOrFail();
    $company = Company::query()->where('name', 'Metalnova S.r.l.')->firstOrFail();

    $this->actingAs($user)
        ->get(route('measure-registries.index', [
            'company_id' => $company->id,
            'origin' => 'dashboard',
            'focus' => 'deadlines',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sicurezzachiara/measure-registries/Index')
            ->where('workspaceContext.contextLabel', 'Registro contestuale azienda')
            ->where('workspaceContext.isCompanyScoped', true)
            ->where('workspaceContext.showCompanyFilter', false)
            ->where('workspaceContext.companyName', 'Metalnova S.r.l.')
            ->where('contextBridge.companyName', 'Metalnova S.r.l.')
            ->where('contextBridge.suggestedAction.label', 'Chiudi scaduti dal registro')
            ->where('contextBridge.stats.reviewsDue', 0)
            ->where('contextBridge.operationalQueue.0.label', 'Chiudi scaduti')
            ->where('contextBridge.operationalQueue.1.label', 'Segui follow-up')
            ->where('contextBridge.operationalQueue.2.label', 'Copri rischi scoperti')
            ->where('measures.0.operational_posture.label', 'Presidio da attuare')
            ->where('measures.1.operational_posture.label', 'Scaduto da chiudere')
            ->where('contextBridge.actions.dashboardRoute', route('dashboard', ['focus' => 'deadlines']))
        )
        ->assertSee('contextBridge', false)
        ->assertSee('Registro contestuale azienda')
        ->assertSee('Tutto il contesto')
        ->assertSee('Solo scaduti')
        ->assertSee('Solo follow-up aperti')
        ->assertSee('visibleMeasuresCount', false)
        ->assertSee('contextMeasuresCount', false)
        ->assertSee('activeScopeLabel', false)
        ->assertSee('scope=follow_up_open', false)
        ->assertSee('scope=overdue', false)
        ->assertSee('profile_route', false)
        ->assertSee('review_route', false)
        ->assertSee('profilo-rischio', false);
});

test('measure registry exposes expected coverage semantics for direct equivalent and free measures', function () {
    $this->seed(SicurezzaChiaraShowcaseSeeder::class);

    $user = User::query()->where('email', 'owner.showcase@sicurezzachiara.test')->firstOrFail();

    $this->actingAs($user)
        ->get(route('measure-registries.index'))
        ->assertOk()
        ->assertSee('Aggancio diretto')
        ->assertSee('Copertura equivalente')
        ->assertSee('Misura libera')
        ->assertSee('Copre direttamente')
        ->assertSee('copre alcun presidio atteso');
});
