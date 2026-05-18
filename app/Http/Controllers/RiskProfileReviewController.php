<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureTenantCanManageData;
use App\Http\Requests\StoreManualRiskProfileItemRequest;
use App\Http\Requests\UpdateRiskProfileReviewRequest;
use App\Models\Company;
use App\Models\RiskCatalogItem;
use App\Models\RiskProfileItem;
use App\Models\Tenant;
use App\Models\Worker;
use App\Support\AuditLogger;
use App\Support\CoreStarterPackContextBuilder;
use App\Support\CurrentTenantResolver;
use App\Support\RiskEngineSnapshotBuilder;
use App\Support\RiskOperationalTimelineBuilder;
use App\Support\RiskProfileBuilder;
use App\Support\RiskProfileOverrideManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RiskProfileReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware(EnsureTenantCanManageData::class)->only([
            'storeManualCompany',
            'updateCompany',
            'storeManualWorker',
            'updateWorker',
        ]);
    }

    public function showCompany(
        Request $request,
        Company $company,
        RiskProfileItem $riskProfileItem,
        CurrentTenantResolver $tenantResolver,
        RiskProfileBuilder $riskProfileBuilder,
    ): Response {
        $tenant = $tenantResolver->resolve($request->user());
        $company = $this->companyForTenant($tenant, $company);
        $origin = $request->string('origin')->toString() ?: null;
        $focus = $request->string('focus')->toString() ?: null;
        $riskProfileBuilder->rebuildCompany($company);
        $riskProfileItem = $this->profileItemForProfileable($company, $riskProfileItem);

        return $this->renderReviewPage(
            tenant: $tenant,
            profileable: $company,
            parentType: 'company',
            riskProfileItem: $riskProfileItem,
            origin: $origin,
            focus: $focus,
        );
    }

    public function updateCompany(
        UpdateRiskProfileReviewRequest $request,
        Company $company,
        RiskProfileItem $riskProfileItem,
        CurrentTenantResolver $tenantResolver,
        RiskProfileBuilder $riskProfileBuilder,
        RiskProfileOverrideManager $overrideManager,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $company = $this->companyForTenant($tenant, $company);
        $riskProfileBuilder->rebuildCompany($company);
        $riskProfileItem = $this->profileItemForProfileable($company, $riskProfileItem);
        $this->ensureValidOwner($tenant, $request->validated()['operational_owner_user_id'] ?? null, $overrideManager);
        $riskProfileItem = $overrideManager->review($riskProfileItem, $request->validated(), $request->user());

        $auditLogger->log(
            $tenant,
            $request->user(),
            'risk_profile_item.reviewed',
            $riskProfileItem,
            'Aggiornata valutazione consulente sul rischio '.$riskProfileItem->riskCatalogItem?->name,
            [
                'parent_type' => 'company',
                'parent_id' => $company->id,
                'parent_name' => $company->name,
                'risk_catalog_item_id' => $riskProfileItem->risk_catalog_item_id,
                'risk_name' => $riskProfileItem->riskCatalogItem?->name,
                'operational_status' => $riskProfileItem->operational_status,
                'consultant_decision' => $riskProfileItem->consultant_decision,
                'final_priority' => $riskProfileItem->final_priority,
                'review_due_at' => $riskProfileItem->review_due_at?->format('Y-m-d'),
                'follow_up_status' => $riskProfileItem->follow_up_status,
                'follow_up_outcome_status' => $riskProfileItem->follow_up_outcome_status,
            ],
        );

        return redirect()
            ->route('companies.risk-profile.show', $company)
            ->with('success', 'Valutazione consulente aggiornata correttamente.');
    }

    public function storeManualCompany(
        StoreManualRiskProfileItemRequest $request,
        Company $company,
        CurrentTenantResolver $tenantResolver,
        RiskProfileBuilder $riskProfileBuilder,
        RiskProfileOverrideManager $overrideManager,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $company = $this->companyForTenant($tenant, $company);
        $riskProfileBuilder->rebuildCompany($company);
        $riskCatalogItem = $this->riskCatalogItemForTenant(
            $tenant,
            RiskCatalogItem::query()->with('category')->findOrFail($request->validated()['risk_catalog_item_id']),
        );

        $profileItem = $overrideManager->upsertManualRisk(
            $company,
            $riskCatalogItem,
            $request->validated()['final_priority'] ?? null,
            $request->validated()['consultant_notes'] ?? null,
            $request->validated()['review_due_at'] ?? null,
            $request->user(),
        );

        $auditLogger->log(
            $tenant,
            $request->user(),
            'risk_profile_item.manual_added',
            $profileItem,
            'Aggiunto rischio manuale '.$riskCatalogItem->name.' al profilo azienda',
            [
                'parent_type' => 'company',
                'parent_id' => $company->id,
                'parent_name' => $company->name,
                'risk_catalog_item_id' => $riskCatalogItem->id,
                'risk_name' => $riskCatalogItem->name,
                'final_priority' => $profileItem->final_priority,
                'review_due_at' => $profileItem->review_due_at?->format('Y-m-d'),
            ],
        );

        return redirect()
            ->route('companies.risk-profile.show', $company)
            ->with('success', 'Rischio aggiunto manualmente al profilo aziendale.');
    }

    public function showWorker(
        Request $request,
        Worker $worker,
        RiskProfileItem $riskProfileItem,
        CurrentTenantResolver $tenantResolver,
        RiskProfileBuilder $riskProfileBuilder,
    ): Response {
        $tenant = $tenantResolver->resolve($request->user());
        $worker = $this->workerForTenant($tenant, $worker);
        $origin = $request->string('origin')->toString() ?: null;
        $focus = $request->string('focus')->toString() ?: null;
        $riskProfileBuilder->rebuildWorker($worker);
        $riskProfileItem = $this->profileItemForProfileable($worker, $riskProfileItem);

        return $this->renderReviewPage(
            tenant: $tenant,
            profileable: $worker,
            parentType: 'worker',
            riskProfileItem: $riskProfileItem,
            origin: $origin,
            focus: $focus,
        );
    }

    public function updateWorker(
        UpdateRiskProfileReviewRequest $request,
        Worker $worker,
        RiskProfileItem $riskProfileItem,
        CurrentTenantResolver $tenantResolver,
        RiskProfileBuilder $riskProfileBuilder,
        RiskProfileOverrideManager $overrideManager,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $worker = $this->workerForTenant($tenant, $worker);
        $riskProfileBuilder->rebuildWorker($worker);
        $riskProfileItem = $this->profileItemForProfileable($worker, $riskProfileItem);
        $this->ensureValidOwner($tenant, $request->validated()['operational_owner_user_id'] ?? null, $overrideManager);
        $riskProfileItem = $overrideManager->review($riskProfileItem, $request->validated(), $request->user());

        $auditLogger->log(
            $tenant,
            $request->user(),
            'risk_profile_item.reviewed',
            $riskProfileItem,
            'Aggiornata valutazione consulente sul rischio '.$riskProfileItem->riskCatalogItem?->name,
            [
                'parent_type' => 'worker',
                'parent_id' => $worker->id,
                'parent_name' => $worker->full_name,
                'risk_catalog_item_id' => $riskProfileItem->risk_catalog_item_id,
                'risk_name' => $riskProfileItem->riskCatalogItem?->name,
                'operational_status' => $riskProfileItem->operational_status,
                'consultant_decision' => $riskProfileItem->consultant_decision,
                'final_priority' => $riskProfileItem->final_priority,
                'review_due_at' => $riskProfileItem->review_due_at?->format('Y-m-d'),
                'follow_up_status' => $riskProfileItem->follow_up_status,
                'follow_up_outcome_status' => $riskProfileItem->follow_up_outcome_status,
            ],
        );

        return redirect()
            ->route('workers.risk-profile.show', $worker)
            ->with('success', 'Valutazione consulente aggiornata correttamente.');
    }

    public function storeManualWorker(
        StoreManualRiskProfileItemRequest $request,
        Worker $worker,
        CurrentTenantResolver $tenantResolver,
        RiskProfileBuilder $riskProfileBuilder,
        RiskProfileOverrideManager $overrideManager,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $worker = $this->workerForTenant($tenant, $worker);
        $riskProfileBuilder->rebuildWorker($worker);
        $riskCatalogItem = $this->riskCatalogItemForTenant(
            $tenant,
            RiskCatalogItem::query()->with('category')->findOrFail($request->validated()['risk_catalog_item_id']),
        );

        $profileItem = $overrideManager->upsertManualRisk(
            $worker,
            $riskCatalogItem,
            $request->validated()['final_priority'] ?? null,
            $request->validated()['consultant_notes'] ?? null,
            $request->validated()['review_due_at'] ?? null,
            $request->user(),
        );

        $auditLogger->log(
            $tenant,
            $request->user(),
            'risk_profile_item.manual_added',
            $profileItem,
            'Aggiunto rischio manuale '.$riskCatalogItem->name.' al profilo lavoratore',
            [
                'parent_type' => 'worker',
                'parent_id' => $worker->id,
                'parent_name' => $worker->full_name,
                'risk_catalog_item_id' => $riskCatalogItem->id,
                'risk_name' => $riskCatalogItem->name,
                'final_priority' => $profileItem->final_priority,
                'review_due_at' => $profileItem->review_due_at?->format('Y-m-d'),
            ],
        );

        return redirect()
            ->route('workers.risk-profile.show', $worker)
            ->with('success', 'Rischio aggiunto manualmente al profilo del lavoratore.');
    }

    private function renderReviewPage(
        Tenant $tenant,
        Company|Worker $profileable,
        string $parentType,
        RiskProfileItem $riskProfileItem,
        ?string $origin,
        ?string $focus,
    ): Response {
        $riskProfileItem->loadMissing(['riskCatalogItem.category', 'sources', 'operationalOwner:id,name', 'reviews.actor:id,name', 'reviews.operationalOwner:id,name']);

        $measures = $profileable->riskMeasures()
            ->where('risk_catalog_item_id', $riskProfileItem->risk_catalog_item_id)
            ->orderBy('due_date')
            ->orderBy('title')
            ->get(['id', 'family', 'title', 'status', 'expected_measure_code', 'due_date', 'completed_at', 'created_at']);

        $timeline = app(RiskOperationalTimelineBuilder::class)->buildForProfileItem($riskProfileItem, $measures);
        $engineContext = app(RiskEngineSnapshotBuilder::class)->buildForProfileItem($riskProfileItem, $measures);
        $coreStarterPack = app(CoreStarterPackContextBuilder::class)->buildForProfileSources($riskProfileItem->sources);
        $measureBindings = collect($engineContext['measureBindings'] ?? [])->keyBy('measure_id');
        $companyId = $parentType === 'company' ? $profileable->id : $profileable->company?->id;
        $workspaceFocus = $riskProfileItem->follow_up_status ? 'follow_up' : 'reviews';
        $expectedSummary = $engineContext['expectedMeasures']['summary'] ?? [];
        $openMeasuresCount = $measures->whereIn('status', [
            'not_implemented',
            'to_verify',
        ])->count();
        $missingExpectedMeasures = (int) ($expectedSummary['missing_count'] ?? 0);
        $partialExpectedMeasures = (int) ($expectedSummary['partial_count'] ?? 0);
        $profileFocus = $focus ?: ($riskProfileItem->follow_up_status ? 'follow_up' : 'all');
        $profileRoute = $parentType === 'company'
            ? route('companies.risk-profile.show', [
                'company' => $profileable,
                'origin' => $origin === 'measure_registry' ? 'measure_registry' : 'company_risk_profile',
                'focus' => $profileFocus,
                'risk_profile_item_id' => $riskProfileItem->id,
            ])
            : route('workers.risk-profile.show', [
                'worker' => $profileable,
                'origin' => $origin === 'measure_registry' ? 'measure_registry' : 'worker_risk_profile',
                'focus' => $profileFocus,
            ]);

        $reviewBridge = $this->buildReviewBridge(
            riskProfileItem: $riskProfileItem,
            openMeasuresCount: $openMeasuresCount,
            missingExpectedMeasures: $missingExpectedMeasures,
            partialExpectedMeasures: $partialExpectedMeasures,
            profileRoute: $profileRoute,
            measuresRoute: $parentType === 'company'
                ? route('companies.risk-profile.measures.show', [
                    $profileable,
                    $riskProfileItem,
                    'origin' => $origin === 'measure_registry' ? 'measure_registry' : 'company_risk_profile',
                    'focus' => $profileFocus,
                ])
                : route('workers.risk-profile.measures.show', [
                    $profileable,
                    $riskProfileItem,
                    'origin' => $origin === 'measure_registry' ? 'measure_registry' : 'worker_risk_profile',
                    'focus' => $profileFocus,
                ]),
            workspaceRoute: route('measure-registries.index', array_filter([
                'company_id' => $companyId,
                'owner_user_id' => $riskProfileItem->operational_owner_user_id,
                'risk_profile_item_id' => $riskProfileItem->id,
                'origin' => 'risk_review',
                'focus' => $workspaceFocus,
                'family' => $riskProfileItem->follow_up_status ? 'follow_up' : null,
                'scope' => $riskProfileItem->follow_up_status ? 'follow_up_open' : 'attention',
            ], fn ($value) => $value !== null && $value !== '')),
            dashboardRoute: route('dashboard', ['focus' => $workspaceFocus]),
            workerRoute: $parentType === 'worker' ? route('workers.show', $profileable) : null,
            companyRoute: $parentType === 'company'
                ? route('companies.show', $profileable)
                : ($profileable->company ? route('companies.show', $profileable->company) : null),
            origin: $origin,
            focus: $focus,
        );

        return Inertia::render('sicurezzachiara/risk-profiles/Review', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'parentType' => $parentType,
            'parent' => $parentType === 'company'
                ? ['id' => $profileable->id, 'name' => $profileable->name]
                : [
                    'id' => $profileable->id,
                    'name' => $profileable->full_name,
                    'company_id' => $profileable->company?->id,
                    'company_name' => $profileable->company?->name,
                ],
            'profileItem' => [
                ...$riskProfileItem->toArray(),
                'effective_priority' => $riskProfileItem->effectivePriority(),
                'operational_owner_name' => $riskProfileItem->operationalOwner?->name,
            ],
            'reviews' => $riskProfileItem->reviews->take(8)->map(fn ($review) => [
                'id' => $review->id,
                'event_type' => $review->event_type,
                'operational_status' => $review->operational_status,
                'consultant_decision' => $review->consultant_decision,
                'final_priority' => $review->final_priority,
                'consultant_notes' => $review->consultant_notes,
                'review_due_at' => $review->review_due_at?->format('Y-m-d'),
                'operational_owner_name' => $review->operationalOwner?->name,
                'follow_up_status' => $review->follow_up_status,
                'follow_up_notes' => $review->follow_up_notes,
                'follow_up_due_at' => $review->follow_up_due_at?->format('Y-m-d'),
                'follow_up_outcome_status' => $review->follow_up_outcome_status,
                'follow_up_outcome_notes' => $review->follow_up_outcome_notes,
                'follow_up_outcome_recorded_at' => $review->follow_up_outcome_recorded_at?->format('Y-m-d H:i'),
                'reviewed_at' => $review->reviewed_at?->format('Y-m-d H:i'),
                'actor_name' => $review->actor?->name,
            ])->values(),
            'measures' => $measures->map(fn ($measure) => [
                ...$measure->toArray(),
                'due_date' => $measure->due_date?->format('Y-m-d'),
                'expected_binding' => $measureBindings->get($measure->id),
            ])->values(),
            'timeline' => $timeline,
            'engineContext' => $engineContext,
            'coreStarterPack' => $coreStarterPack,
            'reviewBridge' => $reviewBridge,
            'formOptions' => [
                'operationalStatuses' => [
                    ['value' => RiskProfileItem::OPERATIONAL_STATUS_ACTIVE, 'label' => 'Attivo nel profilo finale'],
                    ['value' => RiskProfileItem::OPERATIONAL_STATUS_EXCLUDED, 'label' => 'Escluso dal profilo finale'],
                ],
                'decisions' => [
                    ['value' => RiskProfileItem::DECISION_CONFIRMED, 'label' => 'Confermato'],
                    ['value' => RiskProfileItem::DECISION_CUSTOMIZED, 'label' => 'Personalizzato'],
                    ['value' => RiskProfileItem::DECISION_EXCLUDED, 'label' => 'Escluso'],
                    ['value' => RiskProfileItem::DECISION_MANUAL_ADDITION, 'label' => 'Aggiunta manuale'],
                ],
                'priorities' => [
                    ['value' => RiskCatalogItem::PRIORITY_HIGH, 'label' => 'Alta'],
                    ['value' => RiskCatalogItem::PRIORITY_MEDIUM, 'label' => 'Media'],
                    ['value' => RiskCatalogItem::PRIORITY_LOW, 'label' => 'Bassa'],
                ],
                'followUpStatuses' => [
                    ['value' => RiskProfileItem::FOLLOW_UP_STATUS_OPEN, 'label' => 'Aperto'],
                    ['value' => RiskProfileItem::FOLLOW_UP_STATUS_IN_PROGRESS, 'label' => 'In lavorazione'],
                    ['value' => RiskProfileItem::FOLLOW_UP_STATUS_BLOCKED, 'label' => 'Bloccato'],
                    ['value' => RiskProfileItem::FOLLOW_UP_STATUS_CLOSED, 'label' => 'Chiuso'],
                ],
                'followUpOutcomes' => [
                    ['value' => RiskProfileItem::FOLLOW_UP_OUTCOME_RESOLVED, 'label' => 'Presidio completato'],
                    ['value' => RiskProfileItem::FOLLOW_UP_OUTCOME_MONITORED, 'label' => 'Chiuso con monitoraggio'],
                    ['value' => RiskProfileItem::FOLLOW_UP_OUTCOME_DEFERRED, 'label' => 'Chiuso con rinvio / nuova agenda'],
                ],
                'owners' => $tenant->memberships()
                    ->with('user:id,name')
                    ->get()
                    ->map(fn ($membership) => [
                        'id' => $membership->user_id,
                        'name' => $membership->user?->name,
                        'role' => $membership->role,
                    ])
                    ->filter(fn (array $owner) => $owner['name'] !== null)
                    ->values(),
                'historyEventLabels' => [
                    'manual_added' => 'Rischio aggiunto manualmente',
                    'review_updated' => 'Valutazione aggiornata',
                ],
            ],
            'backRoute' => $profileRoute,
            'saveRoute' => $parentType === 'company'
                ? route('companies.risk-profile.review.update', [$profileable, $riskProfileItem])
                : route('workers.risk-profile.review.update', [$profileable, $riskProfileItem]),
            'contextRoutes' => [
                'measures' => $reviewBridge['actions']['measuresRoute'],
                'workspace' => $reviewBridge['actions']['workspaceRoute'],
                'dashboard' => $reviewBridge['actions']['dashboardRoute'],
                'worker' => $reviewBridge['actions']['workerRoute'],
                'company' => $reviewBridge['actions']['companyRoute'],
            ],
        ]);
    }

    private function buildReviewBridge(
        RiskProfileItem $riskProfileItem,
        int $openMeasuresCount,
        int $missingExpectedMeasures,
        int $partialExpectedMeasures,
        string $profileRoute,
        string $measuresRoute,
        string $workspaceRoute,
        string $dashboardRoute,
        ?string $workerRoute,
        ?string $companyRoute,
        ?string $origin,
        ?string $focus,
    ): array {
        $followUpOpen = $riskProfileItem->hasOpenFollowUp();
        $coverageGapCount = $missingExpectedMeasures + $partialExpectedMeasures;
        $originLabel = match ($origin) {
            'company_risk_profile' => 'Profilo rischio azienda',
            'worker_risk_profile' => 'Profilo rischio lavoratore',
            'measure_registry' => 'Registro contestuale',
            'dashboard' => 'Dashboard operativa',
            default => null,
        };
        $focusLabel = match ($focus) {
            'follow_up' => 'Follow-up',
            'reviews' => 'Review',
            'deadlines' => 'Scadenze',
            'urgent' => 'Urgenti',
            'all' => 'Vista completa',
            default => null,
        };

        $decision = match (true) {
            $followUpOpen && $openMeasuresCount > 0 => [
                'label' => 'Segui il follow-up operativo',
                'helper' => 'La review e\' gia\' tradotta in criticita\' operative: il prossimo passaggio utile e\' lavorare su misure e registri in carico.',
                'tone' => 'warning',
                'laneLabel' => 'Corsia follow-up',
                'actionLabel' => 'Apri corsia follow-up',
                'actionRoute' => $workspaceRoute,
            ],
            $coverageGapCount > 0 => [
                'label' => 'Colma i presidi attesi',
                'helper' => 'Il motore mostra ancora gap o coperture parziali: conviene completare prima i presidi attesi del rischio.',
                'tone' => 'danger',
                'laneLabel' => 'Corsia copertura',
                'actionLabel' => 'Apri misure del rischio',
                'actionRoute' => $measuresRoute,
            ],
            $openMeasuresCount > 0 => [
                'label' => 'Chiudi le misure ancora aperte',
                'helper' => 'I presidi esistono ma non sono ancora tutti consolidati come attuati o verificati.',
                'tone' => 'info',
                'laneLabel' => 'Corsia misure',
                'actionLabel' => 'Apri misure del rischio',
                'actionRoute' => $measuresRoute,
            ],
            default => [
                'label' => 'Review allineata al presidio',
                'helper' => 'Il rischio e\' gia\' leggibile come review coerente: puoi tornare al profilo o monitorarlo dal workspace.',
                'tone' => 'success',
                'laneLabel' => 'Corsia review',
                'actionLabel' => 'Rientra nel profilo rischio',
                'actionRoute' => $profileRoute,
            ],
        };

        $operationalQueue = [
            [
                'key' => 'follow_up',
                'label' => 'Segui follow-up',
                'count' => $followUpOpen ? 1 : 0,
                'status' => $followUpOpen ? 'open' : 'aligned',
                'helper' => $followUpOpen
                    ? 'Il rischio e\' in carico operativo: conviene chiudere il follow-up prima di archiviare la review.'
                    : 'Non risultano follow-up aperti su questo rischio.',
                'actionLabel' => 'Apri corsia follow-up',
                'tone' => $followUpOpen ? 'warning' : 'secondary',
                'laneLabel' => 'Corsia follow-up',
                'actionRoute' => $workspaceRoute,
            ],
            [
                'key' => 'coverage',
                'label' => 'Colma presidi attesi',
                'count' => $coverageGapCount,
                'status' => $coverageGapCount > 0 ? 'open' : 'aligned',
                'helper' => $coverageGapCount > 0
                    ? 'Restano gap o coperture parziali: la gestione misure e\' la corsia principale da chiudere.'
                    : 'I presidi attesi risultano allineati su questo rischio.',
                'actionLabel' => 'Apri corsia copertura',
                'tone' => $coverageGapCount > 0 ? 'danger' : 'secondary',
                'laneLabel' => 'Corsia copertura',
                'actionRoute' => $measuresRoute,
            ],
            [
                'key' => 'review',
                'label' => 'Rientra nella review finale',
                'count' => $openMeasuresCount,
                'status' => $openMeasuresCount > 0 ? 'in_progress' : 'aligned',
                'helper' => $openMeasuresCount > 0
                    ? 'La review resta aperta finche\' ci sono ancora misure pendenti o da verificare.'
                    : 'Il rischio e\' pronto per una rilettura finale o un semplice monitoraggio.',
                'actionLabel' => 'Rileggi review',
                'tone' => $openMeasuresCount > 0 ? 'primary' : 'secondary',
                'laneLabel' => 'Corsia review',
                'actionRoute' => $profileRoute,
            ],
        ];

        return [
            'origin' => $origin,
            'originLabel' => $originLabel,
            'focus' => $focus,
            'focusLabel' => $focusLabel,
            'decision' => $decision,
            'returnContext' => [
                'label' => 'Rientro nel profilo',
                'helper' => 'Chiudi qui la lettura consulenziale e rientra nel profilo con lo stesso focus operativo da cui sei partito.',
                'profileRoute' => $profileRoute,
                'workspaceRoute' => $workspaceRoute,
                'measuresRoute' => $measuresRoute,
            ],
            'operationalQueue' => $operationalQueue,
            'stats' => [
                'openMeasures' => $openMeasuresCount,
                'coverageGapCount' => $coverageGapCount,
                'missingExpectedMeasures' => $missingExpectedMeasures,
                'partialExpectedMeasures' => $partialExpectedMeasures,
                'followUpOpen' => $followUpOpen,
            ],
            'actions' => [
                'profileRoute' => $profileRoute,
                'measuresRoute' => $measuresRoute,
                'workspaceRoute' => $workspaceRoute,
                'dashboardRoute' => $dashboardRoute,
                'workerRoute' => $workerRoute,
                'companyRoute' => $companyRoute,
            ],
        ];
    }

    private function companyForTenant(Tenant $tenant, Company $company): Company
    {
        abort_unless($company->tenant_id === $tenant->id, 404);

        return $company;
    }

    private function workerForTenant(Tenant $tenant, Worker $worker): Worker
    {
        $worker->loadMissing('company');

        abort_unless($worker->company !== null && $worker->company->tenant_id === $tenant->id, 404);

        return $worker;
    }

    private function riskCatalogItemForTenant(Tenant $tenant, RiskCatalogItem $riskCatalogItem): RiskCatalogItem
    {
        abort_unless(app(RiskProfileOverrideManager::class)->riskBelongsToTenant($tenant, $riskCatalogItem), 404);

        return $riskCatalogItem;
    }

    private function profileItemForProfileable(Company|Worker $profileable, RiskProfileItem $riskProfileItem): RiskProfileItem
    {
        abort_unless(app(RiskProfileOverrideManager::class)->profileItemBelongsToProfileable($profileable, $riskProfileItem), 404);

        return $riskProfileItem;
    }

    private function ensureValidOwner(Tenant $tenant, ?int $ownerUserId, RiskProfileOverrideManager $overrideManager): void
    {
        if ($overrideManager->userCanOwnFollowUp($tenant, $ownerUserId)) {
            return;
        }

        throw ValidationException::withMessages([
            'operational_owner_user_id' => 'Il referente operativo selezionato non appartiene al tenant corrente.',
        ]);
    }
}
