<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\RiskCatalogItem;
use App\Models\RiskMeasure;
use App\Models\RiskProfileItem;
use App\Models\Tenant;
use App\Models\Worker;
use App\Support\CoreStarterPackContextBuilder;
use App\Support\CurrentTenantResolver;
use App\Support\RiskEngineSnapshotBuilder;
use App\Support\RiskProfileBuilder;
use App\Support\RiskProfileOverrideManager;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RiskProfileController extends Controller
{
    public function showCompany(
        Request $request,
        Company $company,
        CurrentTenantResolver $tenantResolver,
        RiskProfileBuilder $riskProfileBuilder,
        RiskEngineSnapshotBuilder $riskEngineSnapshotBuilder,
        RiskProfileOverrideManager $overrideManager,
        CoreStarterPackContextBuilder $coreStarterPackContextBuilder,
    ): Response {
        $tenant = $tenantResolver->resolve($request->user());
        $company = $this->companyForTenant($tenant, $company);
        $origin = $request->string('origin')->toString() ?: null;
        $focus = $request->string('focus')->toString() ?: null;

        $riskProfileBuilder->rebuildCompany($company);

        $company->load([
            'sites:id,company_id,name',
            'workers:id,company_id',
            'workers.jobRoleAssignments' => fn ($query) => $query
                ->where('is_primary', true)
                ->with([
                    'jobRole' => fn ($jobRoleQuery) => $jobRoleQuery
                        ->with(['riskSourceLinks.riskCatalogItem.category']),
                ]),
            'equipmentAssets' => fn ($query) => $query
                ->with([
                    'equipmentType' => fn ($equipmentTypeQuery) => $equipmentTypeQuery
                        ->with(['riskSourceLinks.riskCatalogItem.category']),
                ]),
            'sites.workplaces' => fn ($query) => $query
                ->with([
                    'workplaceType' => fn ($workplaceTypeQuery) => $workplaceTypeQuery
                        ->with(['riskSourceLinks.riskCatalogItem.category']),
                ]),
            'riskProfileItems' => fn ($query) => $query
                ->with(['riskCatalogItem.category:id,name', 'sources', 'operationalOwner:id,name'])
                ->orderByRaw(
                    'case priority when ? then 0 when ? then 1 else 2 end',
                    [RiskCatalogItem::PRIORITY_HIGH, RiskCatalogItem::PRIORITY_MEDIUM],
                )
                ->orderBy('id'),
            'riskMeasures' => fn ($query) => $query
                ->orderByRaw(
                    'case status when ? then 0 when ? then 1 else 2 end',
                    [RiskMeasure::STATUS_NOT_IMPLEMENTED, RiskMeasure::STATUS_TO_VERIFY],
                )
                ->orderBy('due_date')
                ->orderBy('title'),
        ]);

        $engine = $riskEngineSnapshotBuilder->buildForProfileable($company);
        $engineRiskMap = collect($engine['risks'])->keyBy('id');
        $overdueMeasures = $company->riskMeasures
            ->filter(fn (RiskMeasure $measure) => $measure->due_date !== null
                && $measure->status !== RiskMeasure::STATUS_IMPLEMENTED
                && $measure->due_date->isPast())
            ->count();
        $coreStarterPack = $coreStarterPackContextBuilder->buildForCompanySources(
            $company->workers
                ->flatMap(fn ($worker) => $worker->jobRoleAssignments->pluck('jobRole'))
                ->filter()
                ->values(),
            $company->equipmentAssets
                ->pluck('equipmentType')
                ->filter()
                ->values(),
            $company->sites
                ->flatMap(fn ($site) => $site->workplaces->pluck('workplaceType'))
                ->filter()
                ->values(),
        );
        $this->stripCompanyContextRiskLinks($company);

        return Inertia::render('sicurezzachiara/risk-profiles/CompanyShow', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'company' => [
                ...$company->toArray(),
                'risk_profile_items' => $this->transformProfileItems($company->riskProfileItems, $company->riskMeasures, 'company', $engineRiskMap),
            ],
            'summary' => $engine['summary'],
            'engine' => collect($engine)->except('risks')->all(),
            'coreStarterPack' => $coreStarterPack,
            'workspaceBridge' => $this->buildCompanyWorkspaceBridge($company, $engine['summary'], $overdueMeasures, $origin, $focus),
            'manualRiskOptions' => $overrideManager->availableManualRiskOptions($tenant, $company),
            'formOptions' => $this->formOptions(),
        ]);
    }

    public function showWorker(
        Request $request,
        Worker $worker,
        CurrentTenantResolver $tenantResolver,
        RiskProfileBuilder $riskProfileBuilder,
        RiskEngineSnapshotBuilder $riskEngineSnapshotBuilder,
        RiskProfileOverrideManager $overrideManager,
        CoreStarterPackContextBuilder $coreStarterPackContextBuilder,
    ): Response {
        $tenant = $tenantResolver->resolve($request->user());
        $worker = $this->workerForTenant($tenant, $worker);
        $origin = $request->string('origin')->toString() ?: null;
        $focus = $request->string('focus')->toString() ?: null;

        $riskProfileBuilder->rebuildWorker($worker);

        $worker->load([
            'company:id,name,tenant_id',
            'primarySite:id,name',
            'jobRoleAssignments' => fn ($query) => $query
                ->where('is_primary', true)
                ->with([
                    'jobRole' => fn ($jobRoleQuery) => $jobRoleQuery
                        ->with(['riskSourceLinks.riskCatalogItem.category']),
                ]),
            'equipmentExposures' => fn ($query) => $query
                ->with([
                    'equipmentAsset.equipmentType' => fn ($equipmentTypeQuery) => $equipmentTypeQuery
                        ->with(['riskSourceLinks.riskCatalogItem.category']),
                ]),
            'workplaceExposures' => fn ($query) => $query
                ->with([
                    'workplace.workplaceType' => fn ($workplaceTypeQuery) => $workplaceTypeQuery
                        ->with(['riskSourceLinks.riskCatalogItem.category']),
                ]),
            'riskProfileItems' => fn ($query) => $query
                ->with(['riskCatalogItem.category:id,name', 'sources', 'operationalOwner:id,name'])
                ->orderByRaw(
                    'case priority when ? then 0 when ? then 1 else 2 end',
                    [RiskCatalogItem::PRIORITY_HIGH, RiskCatalogItem::PRIORITY_MEDIUM],
                )
                ->orderBy('id'),
            'riskMeasures' => fn ($query) => $query
                ->orderByRaw(
                    'case status when ? then 0 when ? then 1 else 2 end',
                    [RiskMeasure::STATUS_NOT_IMPLEMENTED, RiskMeasure::STATUS_TO_VERIFY],
                )
                ->orderBy('due_date')
                ->orderBy('title'),
        ]);

        $engine = $riskEngineSnapshotBuilder->buildForProfileable($worker);
        $engineRiskMap = collect($engine['risks'])->keyBy('id');
        $coreStarterPack = $coreStarterPackContextBuilder->buildForWorkerSources(
            $worker->jobRoleAssignments->pluck('jobRole')->filter()->values(),
            $worker->equipmentExposures->pluck('equipmentAsset.equipmentType')->filter()->values(),
            $worker->workplaceExposures->pluck('workplace.workplaceType')->filter()->values(),
        );
        $this->stripWorkerContextRiskLinks($worker);

        return Inertia::render('sicurezzachiara/risk-profiles/WorkerShow', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'worker' => [
                ...$worker->toArray(),
                'risk_profile_items' => $this->transformProfileItems($worker->riskProfileItems, $worker->riskMeasures, 'worker', $engineRiskMap),
            ],
            'summary' => $engine['summary'],
            'engine' => collect($engine)->except('risks')->all(),
            'coreStarterPack' => $coreStarterPack,
            'workspaceBridge' => $this->buildWorkerWorkspaceBridge($worker, $engine['summary'], $origin, $focus),
            'manualRiskOptions' => $overrideManager->availableManualRiskOptions($tenant, $worker),
            'formOptions' => $this->formOptions(),
        ]);
    }

    private function transformProfileItems($profileItems, $measures, string $parentType, $engineRiskMap): array
    {
        $measuresByRisk = $measures
            ->groupBy('risk_catalog_item_id')
            ->map(fn ($items) => $items->values());

        return $profileItems
            ->map(function (RiskProfileItem $profileItem) use ($measuresByRisk, $parentType, $engineRiskMap) {
                $profileMeasures = $measuresByRisk->get($profileItem->risk_catalog_item_id, collect());
                $engineRisk = $engineRiskMap->get($profileItem->id, []);

                return [
                    ...$profileItem->toArray(),
                    'effective_priority' => $profileItem->effectivePriority(),
                    'review_due_at' => $profileItem->review_due_at?->format('Y-m-d'),
                    'is_review_due' => $profileItem->isReviewDue(),
                    'last_reviewed_at' => $profileItem->reviewed_at?->format('Y-m-d H:i'),
                    'operational_owner_name' => $profileItem->operationalOwner?->name,
                    'follow_up_status' => $profileItem->follow_up_status,
                    'follow_up_notes' => $profileItem->follow_up_notes,
                    'follow_up_due_at' => $profileItem->follow_up_due_at?->format('Y-m-d'),
                    'is_follow_up_due' => $profileItem->isFollowUpDue(),
                    'measure_summary' => [
                        'count' => $profileMeasures->count(),
                        'implemented' => $profileMeasures->where('status', RiskMeasure::STATUS_IMPLEMENTED)->count(),
                        'to_verify' => $profileMeasures->where('status', RiskMeasure::STATUS_TO_VERIFY)->count(),
                    ],
                    'engine_source_state' => data_get($engineRisk, 'source_state.label'),
                    'engine_source_helper' => data_get($engineRisk, 'source_state.helper'),
                    'engine_coverage' => data_get($engineRisk, 'coverage'),
                    'engine_final_state' => data_get($engineRisk, 'final_state'),
                    'measures_preview' => $profileMeasures
                        ->take(3)
                        ->map(fn (RiskMeasure $measure) => [
                            'id' => $measure->id,
                            'title' => $measure->title,
                            'family' => $measure->family,
                            'status' => $measure->status,
                        ])
                        ->values()
                        ->all(),
                    'measures_route' => $parentType === 'company'
                        ? route('companies.risk-profile.measures.show', [$profileItem->profileable_id, $profileItem->id])
                        : route('workers.risk-profile.measures.show', [$profileItem->profileable_id, $profileItem->id]),
                    'review_route' => $parentType === 'company'
                        ? route('companies.risk-profile.review.show', [$profileItem->profileable_id, $profileItem->id])
                        : route('workers.risk-profile.review.show', [$profileItem->profileable_id, $profileItem->id]),
                ];
            })
            ->values()
            ->all();
    }

    private function formOptions(): array
    {
        return [
            'priorities' => [
                ['value' => RiskCatalogItem::PRIORITY_HIGH, 'label' => 'Alta'],
                ['value' => RiskCatalogItem::PRIORITY_MEDIUM, 'label' => 'Media'],
                ['value' => RiskCatalogItem::PRIORITY_LOW, 'label' => 'Bassa'],
            ],
        ];
    }

    private function buildCompanyWorkspaceBridge(
        Company $company,
        array $summary,
        int $overdueMeasures,
        ?string $origin,
        ?string $focus,
    ): array {
        $focusLabels = [
            'all' => 'Vista completa',
            'urgent' => 'Urgenti',
            'deadlines' => 'Scadenze',
            'follow_up' => 'Follow-up',
            'reviews' => 'Review',
        ];

        $originLabels = [
            'dashboard' => 'Dashboard operativa',
            'measure_registry' => 'Registri misure',
        ];

        $suggestedFocus = match (true) {
            $overdueMeasures > 0 => 'deadlines',
            ($summary['reviewsDue'] ?? 0) > 0 => 'reviews',
            ($summary['followUpsOpen'] ?? 0) > 0 => 'follow_up',
            default => 'all',
        };

        $suggestedScope = match ($suggestedFocus) {
            'deadlines' => 'overdue',
            'follow_up' => 'follow_up_open',
            default => 'attention',
        };

        $suggestedAction = match ($suggestedFocus) {
            'deadlines' => [
                'label' => 'Chiudi scaduti nei registri',
                'helper' => $overdueMeasures.' misure oltre data chiedono presidio immediato nel contesto aziendale.',
            ],
            'reviews' => [
                'label' => 'Riallinea review aziendali',
                'helper' => ($summary['reviewsDue'] ?? 0).' revisioni consulente chiedono una decisione aggiornata.',
            ],
            'follow_up' => [
                'label' => 'Segui follow-up operativi',
                'helper' => ($summary['followUpsOpen'] ?? 0).' criticita\' sono ancora in presa in carico.',
            ],
            default => [
                'label' => 'Monitora copertura e presidi',
                'helper' => 'Il contesto non mostra urgenze forti, ma richiede monitoraggio coerente del presidio.',
            ],
        };

        return [
            'origin' => $origin,
            'originLabel' => $origin ? ($originLabels[$origin] ?? $origin) : null,
            'focus' => $focus,
            'focusLabel' => $focus ? ($focusLabels[$focus] ?? $focus) : null,
            'suggestedFocus' => $suggestedFocus,
            'suggestedFocusLabel' => $focusLabels[$suggestedFocus] ?? $suggestedFocus,
            'suggestedScope' => $suggestedScope,
            'suggestedAction' => $suggestedAction,
            'stats' => [
                'overdueMeasures' => $overdueMeasures,
                'reviewsDue' => (int) ($summary['reviewsDue'] ?? 0),
                'followUpsOpen' => (int) ($summary['followUpsOpen'] ?? 0),
                'missingExpectedMeasures' => (int) ($summary['missingExpectedMeasures'] ?? 0),
                'uncoveredRisks' => (int) ($summary['uncoveredRisks'] ?? 0),
            ],
            'actions' => [
                'registryRoute' => route('measure-registries.index', [
                    'company_id' => $company->id,
                    'scope' => $suggestedScope,
                    'origin' => 'company_risk_profile',
                    'focus' => $suggestedFocus,
                ]),
                'allMeasuresRoute' => route('measure-registries.index', [
                    'company_id' => $company->id,
                    'scope' => 'attention',
                    'origin' => 'company_risk_profile',
                    'focus' => $suggestedFocus,
                ]),
                'companyRoute' => route('companies.show', $company),
                'dashboardRoute' => $origin === 'dashboard'
                    ? route('dashboard', $focus && $focus !== 'all' ? ['focus' => $focus] : [])
                    : null,
            ],
        ];
    }

    private function buildWorkerWorkspaceBridge(
        Worker $worker,
        array $summary,
        ?string $origin,
        ?string $focus,
    ): array {
        $effectiveFocus = $focus ?: match (true) {
            ($summary['followUpsOpen'] ?? 0) > 0 => 'follow_up',
            ($summary['reviewsDue'] ?? 0) > 0 => 'reviews',
            ($summary['missingExpectedMeasures'] ?? 0) > 0 => 'all',
            default => 'all',
        };

        return [
            'origin' => $origin,
            'originLabel' => match ($origin) {
                'dashboard' => 'Dashboard operativa',
                'worker_show' => 'Dettaglio lavoratore',
                'measure_registry' => 'Registro misure',
                default => null,
            },
            'focus' => $effectiveFocus,
            'focusLabel' => match ($effectiveFocus) {
                'follow_up' => 'Follow-up',
                'reviews' => 'Review',
                'deadlines' => 'Scadenze',
                default => 'Copertura',
            },
            'suggestedAction' => match (true) {
                ($summary['followUpsOpen'] ?? 0) > 0 => [
                    'label' => 'Segui i follow-up aperti',
                    'helper' => ($summary['followUpsOpen'] ?? 0).' rischi del lavoratore restano in carico operativo tra review e registri.',
                ],
                ($summary['reviewsDue'] ?? 0) > 0 => [
                    'label' => 'Riallinea le review dovute',
                    'helper' => ($summary['reviewsDue'] ?? 0).' review consulente richiedono un riallineamento sul profilo del lavoratore.',
                ],
                default => [
                    'label' => 'Monitora copertura e presidi',
                    'helper' => ($summary['missingExpectedMeasures'] ?? 0).' gap attesi e '.($summary['uncoveredRisks'] ?? 0).' rischi da presidiare restano nel profilo attivo.',
                ],
            },
            'suggestedFocusLabel' => match ($effectiveFocus) {
                'follow_up' => 'Follow-up',
                'reviews' => 'Review',
                'deadlines' => 'Scadenze',
                default => 'Copertura',
            },
            'stats' => [
                'reviewsDue' => (int) ($summary['reviewsDue'] ?? 0),
                'followUpsOpen' => (int) ($summary['followUpsOpen'] ?? 0),
                'missingExpectedMeasures' => (int) ($summary['missingExpectedMeasures'] ?? 0),
                'uncoveredRisks' => (int) ($summary['uncoveredRisks'] ?? 0),
            ],
            'actions' => [
                'registryRoute' => route('measure-registries.index', array_filter([
                    'company_id' => $worker->company_id,
                    'origin' => 'worker_risk_profile',
                    'focus' => $effectiveFocus,
                    'scope' => $effectiveFocus === 'follow_up' ? 'follow_up_open' : 'attention',
                    'family' => $effectiveFocus === 'follow_up' ? 'follow_up' : null,
                ], fn ($value) => $value !== null && $value !== '')),
                'workerRoute' => route('workers.show', $worker),
                'companyRoute' => route('companies.show', $worker->company_id),
                'dashboardRoute' => route('dashboard', $effectiveFocus !== 'all' ? ['focus' => $effectiveFocus] : []),
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

    private function stripCompanyContextRiskLinks(Company $company): void
    {
        $company->workers->each(function ($worker) {
            $worker->jobRoleAssignments->each(fn ($assignment) => $assignment->jobRole?->unsetRelation('riskSourceLinks'));
        });

        $company->equipmentAssets->each(fn ($asset) => $asset->equipmentType?->unsetRelation('riskSourceLinks'));

        $company->sites->each(function ($site) {
            $site->workplaces->each(fn ($workplace) => $workplace->workplaceType?->unsetRelation('riskSourceLinks'));
        });
    }

    private function stripWorkerContextRiskLinks(Worker $worker): void
    {
        $worker->jobRoleAssignments->each(fn ($assignment) => $assignment->jobRole?->unsetRelation('riskSourceLinks'));
        $worker->equipmentExposures->each(fn ($exposure) => $exposure->equipmentAsset?->equipmentType?->unsetRelation('riskSourceLinks'));
        $worker->workplaceExposures->each(fn ($exposure) => $exposure->workplace?->workplaceType?->unsetRelation('riskSourceLinks'));
    }
}
