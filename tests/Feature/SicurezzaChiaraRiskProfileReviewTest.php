<?php

use App\Actions\Tenant\CreateTenantWorkspace;
use App\Models\Company;
use App\Models\CompanySite;
use App\Models\JobRole;
use App\Models\RiskCatalogItem;
use App\Models\RiskMeasure;
use App\Models\RiskProfileItem;
use App\Models\RiskProfileItemReview;
use App\Models\RiskSourceLink;
use App\Models\TenantMembership;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerJobRoleAssignment;
use App\Support\RiskProfileBuilder;
use Database\Seeders\SicurezzaChiaraCoreJobRolesSeeder;
use Database\Seeders\SicurezzaChiaraCoreRiskCategoriesSeeder;
use Database\Seeders\SicurezzaChiaraCoreRisksSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function createRiskReviewFixture(): array
{
    test()->seed([
        SicurezzaChiaraCoreJobRolesSeeder::class,
        SicurezzaChiaraCoreRiskCategoriesSeeder::class,
        SicurezzaChiaraCoreRisksSeeder::class,
    ]);

    $owner = User::factory()->create();
    $tenant = app(CreateTenantWorkspace::class)->handle($owner, 'Studio Review');

    $member = User::factory()->create();
    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $member->id,
        'role' => TenantMembership::ROLE_MEMBER,
        'joined_at' => now(),
    ]);
    $member->forceFill(['current_tenant_id' => $tenant->id])->save();

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

    return compact('owner', 'tenant', 'member', 'company', 'worker', 'risk', 'profileItem');
}

test('consultant can exclude a deduced risk and the review persists after rebuild', function () {
    ['owner' => $owner, 'worker' => $worker, 'profileItem' => $profileItem] = createRiskReviewFixture();

    $this->actingAs($owner)
        ->put(route('workers.risk-profile.review.update', [$worker, $profileItem]), [
            'operational_status' => RiskProfileItem::OPERATIONAL_STATUS_EXCLUDED,
            'consultant_decision' => RiskProfileItem::DECISION_EXCLUDED,
            'final_priority' => RiskCatalogItem::PRIORITY_LOW,
            'consultant_notes' => 'Escluso dopo valutazione sul contesto effettivo.',
            'review_due_at' => now()->addDays(10)->toDateString(),
        ])
        ->assertRedirect(route('workers.risk-profile.show', $worker));

    app(RiskProfileBuilder::class)->rebuildWorker($worker->fresh());

    expect($profileItem->fresh()->operational_status)->toBe(RiskProfileItem::OPERATIONAL_STATUS_EXCLUDED)
        ->and($profileItem->fresh()->consultant_decision)->toBe(RiskProfileItem::DECISION_EXCLUDED)
        ->and($profileItem->fresh()->final_priority)->toBe(RiskCatalogItem::PRIORITY_LOW)
        ->and($profileItem->fresh()->review_due_at?->toDateString())->toBe(now()->addDays(10)->toDateString())
        ->and($profileItem->fresh()->is_currently_derived)->toBeTrue();
});

test('consultant can add a manual risk to the final profile', function () {
    ['owner' => $owner, 'worker' => $worker] = createRiskReviewFixture();

    $risk = RiskCatalogItem::query()->where('name', 'Movimentazione manuale dei carichi')->firstOrFail();

    $this->actingAs($owner)
        ->post(route('workers.risk-profile.manual.store', $worker), [
            'risk_catalog_item_id' => $risk->id,
            'final_priority' => RiskCatalogItem::PRIORITY_HIGH,
            'consultant_notes' => 'Aggiunta manuale per lavorazioni accessorie non ancora modellate.',
            'review_due_at' => now()->addDays(30)->toDateString(),
        ])
        ->assertRedirect(route('workers.risk-profile.show', $worker));

    $manualItem = RiskProfileItem::query()
        ->where('profileable_type', Worker::class)
        ->where('profileable_id', $worker->id)
        ->where('risk_catalog_item_id', $risk->id)
        ->firstOrFail();

    expect($manualItem->is_manual)->toBeTrue()
        ->and($manualItem->is_currently_derived)->toBeFalse()
        ->and($manualItem->consultant_decision)->toBe(RiskProfileItem::DECISION_MANUAL_ADDITION)
        ->and($manualItem->review_due_at?->toDateString())->toBe(now()->addDays(30)->toDateString())
        ->and($manualItem->final_priority)->toBe(RiskCatalogItem::PRIORITY_HIGH);
});

test('consultive members can read review pages but cannot mutate risk reviews', function () {
    ['member' => $member, 'worker' => $worker, 'profileItem' => $profileItem] = createRiskReviewFixture();

    $this->withoutVite();

    $this->actingAs($member)
        ->get(route('workers.risk-profile.review.show', [$worker, $profileItem]))
        ->assertOk();

    $this->put(route('workers.risk-profile.review.update', [$worker, $profileItem]), [
        'operational_status' => RiskProfileItem::OPERATIONAL_STATUS_ACTIVE,
        'consultant_decision' => RiskProfileItem::DECISION_CONFIRMED,
        'final_priority' => '',
        'consultant_notes' => 'Tentativo non consentito.',
        'review_due_at' => now()->addDays(15)->toDateString(),
    ])->assertForbidden();
});

test('review page exposes a light operational timeline for the risk', function () {
    ['owner' => $owner, 'company' => $company, 'worker' => $worker, 'profileItem' => $profileItem] = createRiskReviewFixture();

    RiskMeasure::query()->create([
        'profileable_type' => Worker::class,
        'profileable_id' => $worker->id,
        'risk_catalog_item_id' => $profileItem->risk_catalog_item_id,
        'family' => RiskMeasure::FAMILY_TECHNICAL,
        'title' => 'Ripristino protezioni bordo pressa',
        'status' => RiskMeasure::STATUS_TO_VERIFY,
        'due_date' => now()->addDays(5)->toDateString(),
        'notes' => 'Timeline test',
    ]);

    $this->actingAs($owner)
        ->put(route('workers.risk-profile.review.update', [$worker, $profileItem]), [
            'operational_status' => RiskProfileItem::OPERATIONAL_STATUS_ACTIVE,
            'consultant_decision' => RiskProfileItem::DECISION_CUSTOMIZED,
            'final_priority' => RiskCatalogItem::PRIORITY_HIGH,
            'consultant_notes' => 'Riallineamento timeline operativa sul rischio.',
            'review_due_at' => now()->addDays(14)->toDateString(),
            'follow_up_status' => RiskProfileItem::FOLLOW_UP_STATUS_IN_PROGRESS,
            'follow_up_notes' => 'Presidio in corso con misura aperta.',
            'follow_up_due_at' => now()->addDays(5)->toDateString(),
        ]);

    $this->withoutVite();

    $this->actingAs($owner)
        ->get(route('workers.risk-profile.review.show', [$worker, $profileItem]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sicurezzachiara/risk-profiles/Review')
            ->where('reviewBridge.actions.measuresRoute', route('workers.risk-profile.measures.show', [$worker, $profileItem]))
            ->where('reviewBridge.actions.workerRoute', route('workers.show', $worker))
            ->where('reviewBridge.actions.companyRoute', route('companies.show', $company))
            ->where('reviewBridge.actions.workspaceRoute', route('measure-registries.index', [
                'company_id' => $company->id,
                'origin' => 'risk_review',
                'focus' => 'follow_up',
                'family' => 'follow_up',
                'scope' => 'follow_up_open',
            ]))
            ->where('reviewBridge.stats.openMeasures', 1)
            ->where('reviewBridge.stats.followUpOpen', true)
            ->where('reviewBridge.decision.laneLabel', 'Corsia follow-up')
            ->where('reviewBridge.decision.tone', 'warning')
            ->where('reviewBridge.operationalQueue.0.laneLabel', 'Corsia follow-up')
            ->where('reviewBridge.operationalQueue.1.laneLabel', 'Corsia copertura')
            ->where('reviewBridge.operationalQueue.2.laneLabel', 'Corsia review')
            ->where('contextRoutes.worker', route('workers.show', $worker))
            ->where('contextRoutes.company', route('companies.show', $company))
        )
        ->assertSee('engineContext', false)
        ->assertSee('coreStarterPack', false)
        ->assertSee('reviewBridge', false)
        ->assertSee('sourceState', false)
        ->assertSee('finalState', false)
        ->assertSee('timeline')
        ->assertSee('Metalnova S.r.l.')
        ->assertSee('Misura registrata')
        ->assertSee('Valutazione consulente aggiornata')
        ->assertSee('Ripristino protezioni bordo pressa')
        ->assertSee('contextRoutes', false)
        ->assertSee('origin=risk_review', false)
        ->assertSee('focus=follow_up', false)
        ->assertSee('Operatore di produzione');
});

test('risk review history stores consultant snapshots over time', function () {
    ['owner' => $owner, 'worker' => $worker, 'profileItem' => $profileItem] = createRiskReviewFixture();

    $this->actingAs($owner)
        ->put(route('workers.risk-profile.review.update', [$worker, $profileItem]), [
            'operational_status' => RiskProfileItem::OPERATIONAL_STATUS_ACTIVE,
            'consultant_decision' => RiskProfileItem::DECISION_CUSTOMIZED,
            'final_priority' => RiskCatalogItem::PRIORITY_HIGH,
            'consultant_notes' => 'Rischio confermato ma rafforzato per esposizione effettiva di reparto.',
            'review_due_at' => now()->addDays(21)->toDateString(),
        ])
        ->assertRedirect(route('workers.risk-profile.show', $worker));

    $profileItem->refresh();

    expect($profileItem->reviews()->count())->toBe(1)
        ->and($profileItem->reviews()->first()->event_type)->toBe('review_updated')
        ->and($profileItem->reviews()->first()->actor?->is($owner))->toBeTrue()
        ->and($profileItem->reviews()->first()->review_due_at?->toDateString())->toBe(now()->addDays(21)->toDateString());
});

test('consultant can assign operational owner and follow-up data to a risk review', function () {
    ['owner' => $owner, 'member' => $member, 'worker' => $worker, 'profileItem' => $profileItem] = createRiskReviewFixture();

    $this->actingAs($owner)
        ->put(route('workers.risk-profile.review.update', [$worker, $profileItem]), [
            'operational_status' => RiskProfileItem::OPERATIONAL_STATUS_ACTIVE,
            'consultant_decision' => RiskProfileItem::DECISION_CUSTOMIZED,
            'final_priority' => RiskCatalogItem::PRIORITY_HIGH,
            'consultant_notes' => 'Presidio da seguire con presa in carico operativa esplicita.',
            'review_due_at' => now()->addDays(18)->toDateString(),
            'operational_owner_user_id' => $member->id,
            'follow_up_status' => RiskProfileItem::FOLLOW_UP_STATUS_IN_PROGRESS,
            'follow_up_notes' => 'Verificare chiusura barriera e conferma addestramento.',
            'follow_up_due_at' => now()->addDays(6)->toDateString(),
        ])
        ->assertRedirect(route('workers.risk-profile.show', $worker));

    $profileItem->refresh();
    $review = RiskProfileItemReview::query()->latest('id')->firstOrFail();

    expect($profileItem->operational_owner_user_id)->toBe($member->id)
        ->and($profileItem->follow_up_status)->toBe(RiskProfileItem::FOLLOW_UP_STATUS_IN_PROGRESS)
        ->and($profileItem->follow_up_notes)->toBe('Verificare chiusura barriera e conferma addestramento.')
        ->and($profileItem->follow_up_due_at?->toDateString())->toBe(now()->addDays(6)->toDateString())
        ->and($profileItem->follow_up_outcome_status)->toBeNull()
        ->and($profileItem->taken_in_charge_at)->not->toBeNull()
        ->and($review->operational_owner_user_id)->toBe($member->id)
        ->and($review->follow_up_status)->toBe(RiskProfileItem::FOLLOW_UP_STATUS_IN_PROGRESS)
        ->and($review->follow_up_due_at?->toDateString())->toBe(now()->addDays(6)->toDateString());
});

test('consultant can close follow-up with a minimal operational outcome', function () {
    ['owner' => $owner, 'member' => $member, 'worker' => $worker, 'profileItem' => $profileItem] = createRiskReviewFixture();

    $this->actingAs($owner)
        ->put(route('workers.risk-profile.review.update', [$worker, $profileItem]), [
            'operational_status' => RiskProfileItem::OPERATIONAL_STATUS_ACTIVE,
            'consultant_decision' => RiskProfileItem::DECISION_CUSTOMIZED,
            'final_priority' => RiskCatalogItem::PRIORITY_MEDIUM,
            'consultant_notes' => 'Criticita\' chiusa dopo verifica finale sul presidio installato.',
            'review_due_at' => now()->addDays(30)->toDateString(),
            'operational_owner_user_id' => $member->id,
            'follow_up_status' => RiskProfileItem::FOLLOW_UP_STATUS_CLOSED,
            'follow_up_notes' => 'Follow-up chiuso dopo sopralluogo conclusivo.',
            'follow_up_outcome_status' => RiskProfileItem::FOLLOW_UP_OUTCOME_RESOLVED,
            'follow_up_outcome_notes' => 'Protezione installata e addestramento completato.',
        ])
        ->assertRedirect(route('workers.risk-profile.show', $worker));

    $profileItem->refresh();
    $review = RiskProfileItemReview::query()->latest('id')->firstOrFail();

    expect($profileItem->follow_up_status)->toBe(RiskProfileItem::FOLLOW_UP_STATUS_CLOSED)
        ->and($profileItem->follow_up_due_at)->toBeNull()
        ->and($profileItem->follow_up_outcome_status)->toBe(RiskProfileItem::FOLLOW_UP_OUTCOME_RESOLVED)
        ->and($profileItem->follow_up_outcome_notes)->toBe('Protezione installata e addestramento completato.')
        ->and($profileItem->follow_up_outcome_recorded_at)->not->toBeNull()
        ->and($review->follow_up_outcome_status)->toBe(RiskProfileItem::FOLLOW_UP_OUTCOME_RESOLVED)
        ->and($review->follow_up_outcome_notes)->toBe('Protezione installata e addestramento completato.')
        ->and($review->follow_up_outcome_recorded_at)->not->toBeNull();
});

test('risk review requires an operational outcome when closing follow-up', function () {
    ['owner' => $owner, 'worker' => $worker, 'profileItem' => $profileItem] = createRiskReviewFixture();

    $this->actingAs($owner)
        ->put(route('workers.risk-profile.review.update', [$worker, $profileItem]), [
            'operational_status' => RiskProfileItem::OPERATIONAL_STATUS_ACTIVE,
            'consultant_decision' => RiskProfileItem::DECISION_CONFIRMED,
            'final_priority' => RiskCatalogItem::PRIORITY_MEDIUM,
            'consultant_notes' => 'Chiusura senza esito non valida.',
            'review_due_at' => now()->addDays(10)->toDateString(),
            'follow_up_status' => RiskProfileItem::FOLLOW_UP_STATUS_CLOSED,
            'follow_up_notes' => 'Tentativo incompleto.',
        ])
        ->assertSessionHasErrors('follow_up_outcome_status');
});

test('risk review rejects operational owner outside current tenant', function () {
    ['owner' => $owner, 'worker' => $worker, 'profileItem' => $profileItem] = createRiskReviewFixture();

    $outsider = User::factory()->create();

    $this->actingAs($owner)
        ->put(route('workers.risk-profile.review.update', [$worker, $profileItem]), [
            'operational_status' => RiskProfileItem::OPERATIONAL_STATUS_ACTIVE,
            'consultant_decision' => RiskProfileItem::DECISION_CONFIRMED,
            'final_priority' => RiskCatalogItem::PRIORITY_MEDIUM,
            'consultant_notes' => 'Tentativo con referente esterno.',
            'review_due_at' => now()->addDays(10)->toDateString(),
            'operational_owner_user_id' => $outsider->id,
            'follow_up_status' => RiskProfileItem::FOLLOW_UP_STATUS_OPEN,
            'follow_up_notes' => 'Nota non valida.',
            'follow_up_due_at' => now()->addDays(4)->toDateString(),
        ])
        ->assertSessionHasErrors('operational_owner_user_id');
});
