<?php

namespace App\Support;

use App\Models\Company;
use App\Models\RiskMeasure;
use App\Models\RiskProfileItem;
use App\Models\Tenant;
use App\Models\Worker;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class OperationalWorkspaceBuilder
{
    public function __construct(
        private readonly RiskEngineSnapshotBuilder $riskEngineSnapshotBuilder,
        private readonly RiskExpectedMeasureResolver $riskExpectedMeasureResolver,
    ) {}

    public function buildForTenant(Tenant $tenant, string $focus = 'all'): array
    {
        $today = CarbonImmutable::today();
        $soonThreshold = $today->addDays(30);

        $companies = $tenant->companies()
            ->with([
                'sites:id,company_id,name',
                'workers:id,company_id,first_name,last_name',
            ])
            ->orderBy('name')
            ->get(['id', 'tenant_id', 'name', 'industry', 'city', 'province']);

        $companyIds = $companies->pluck('id');
        $workerIds = $companies->flatMap(fn (Company $company) => $company->workers->pluck('id'))->unique()->values();

        $profileItems = RiskProfileItem::query()
            ->with(['riskCatalogItem.category', 'sources', 'operationalOwner:id,name'])
            ->where(function ($query) use ($companyIds, $workerIds) {
                $query
                    ->where(function ($companyQuery) use ($companyIds) {
                        $companyQuery->where('profileable_type', Company::class)
                            ->whereIn('profileable_id', $companyIds);
                    })
                    ->orWhere(function ($workerQuery) use ($workerIds) {
                        $workerQuery->where('profileable_type', Worker::class)
                            ->whereIn('profileable_id', $workerIds);
                    });
            })
            ->get();

        $measures = RiskMeasure::query()
            ->with('riskCatalogItem.category')
            ->where(function ($query) use ($companyIds, $workerIds) {
                $query
                    ->where(function ($companyQuery) use ($companyIds) {
                        $companyQuery->where('profileable_type', Company::class)
                            ->whereIn('profileable_id', $companyIds);
                    })
                    ->orWhere(function ($workerQuery) use ($workerIds) {
                        $workerQuery->where('profileable_type', Worker::class)
                            ->whereIn('profileable_id', $workerIds);
                    });
            })
            ->orderBy('due_date')
            ->orderBy('title')
            ->get();

        $companyLabels = $companies->mapWithKeys(fn (Company $company) => [$company->id => $company->name]);
        $workerLabels = $companies
            ->flatMap(fn (Company $company) => $company->workers)
            ->mapWithKeys(fn (Worker $worker) => [$worker->id => $worker->full_name]);
        $workerCompanyLabels = $companies
            ->flatMap(fn (Company $company) => $company->workers->map(fn (Worker $worker) => [
                'worker_id' => $worker->id,
                'company_name' => $company->name,
            ]))
            ->mapWithKeys(fn (array $item) => [$item['worker_id'] => $item['company_name']]);
        $workerCompanyIds = $companies
            ->flatMap(fn (Company $company) => $company->workers->map(fn (Worker $worker) => [
                'worker_id' => $worker->id,
                'company_id' => $company->id,
            ]))
            ->mapWithKeys(fn (array $item) => [$item['worker_id'] => $item['company_id']]);

        $upcomingMeasures = $measures
            ->filter(fn (RiskMeasure $measure) => $measure->due_date !== null
                && $measure->status !== RiskMeasure::STATUS_IMPLEMENTED
                && $measure->due_date->betweenIncluded($today, $soonThreshold))
            ->sortBy('due_date')
            ->values();

        $overdueMeasures = $measures
            ->filter(fn (RiskMeasure $measure) => $measure->due_date !== null
                && $measure->status !== RiskMeasure::STATUS_IMPLEMENTED
                && $measure->due_date->lt($today))
            ->sortBy('due_date')
            ->values();

        $attentionMeasures = $measures
            ->filter(fn (RiskMeasure $measure) => in_array($measure->status, [
                RiskMeasure::STATUS_NOT_IMPLEMENTED,
                RiskMeasure::STATUS_TO_VERIFY,
            ], true))
            ->sortBy([
                fn (RiskMeasure $measure) => $measure->due_date?->timestamp ?? PHP_INT_MAX,
                fn (RiskMeasure $measure) => $measure->title,
            ])
            ->values();

        $openMeasuresByRiskContext = $attentionMeasures
            ->groupBy(fn (RiskMeasure $measure) => $this->riskScopeKey(
                $measure->profileable_type,
                (int) $measure->profileable_id,
                (int) $measure->risk_catalog_item_id,
            ));

        $riskProfileItemsByKey = $profileItems->keyBy(
            fn (RiskProfileItem $item) => $this->riskScopeKey(
                $item->profileable_type,
                (int) $item->profileable_id,
                (int) $item->risk_catalog_item_id,
            )
        );
        $measuresByRiskContext = $measures
            ->groupBy(fn (RiskMeasure $measure) => $this->riskScopeKey(
                $measure->profileable_type,
                (int) $measure->profileable_id,
                (int) $measure->risk_catalog_item_id,
            ))
            ->map(fn ($groupedMeasures) => collect($groupedMeasures)->values());
        $expectedSnapshotsByRiskContext = $riskProfileItemsByKey
            ->map(fn (RiskProfileItem $item, string $key) => $this->riskExpectedMeasureResolver->snapshotForRisk(
                $item->riskCatalogItem,
                $measuresByRiskContext->get($key, collect()),
            ));
        $measureBindingsById = $expectedSnapshotsByRiskContext
            ->flatMap(fn (array $snapshot) => collect($snapshot['measure_bindings'] ?? []))
            ->keyBy('measure_id');

        $uncoveredItems = $profileItems
            ->filter(fn (RiskProfileItem $item) => $item->isOperationallyActive() && $item->status === RiskProfileItem::STATUS_UNCOVERED)
            ->sortByDesc(fn (RiskProfileItem $item) => $this->priorityWeight($item->effectivePriority()))
            ->values();

        $reviewDueItems = $profileItems
            ->filter(fn (RiskProfileItem $item) => $item->isOperationallyActive() && $item->review_due_at !== null)
            ->sortBy('review_due_at')
            ->values();

        $overdueReviews = $reviewDueItems
            ->filter(fn (RiskProfileItem $item) => $item->review_due_at !== null && $item->review_due_at->lt($today))
            ->values();

        $followUpItems = $profileItems
            ->filter(fn (RiskProfileItem $item) => $item->isOperationallyActive() && $item->hasOpenFollowUp())
            ->sortBy([
                fn (RiskProfileItem $item) => $item->follow_up_due_at?->timestamp ?? PHP_INT_MAX,
                fn (RiskProfileItem $item) => $this->priorityWeight($item->effectivePriority()) * -1,
            ])
            ->values();

        $agendaQueue = collect()
            ->merge($overdueMeasures->map(fn (RiskMeasure $measure) => [
                'kind' => 'measure_overdue',
                'title' => $measure->title,
                'context' => $this->profileLabel($measure, $companyLabels, $workerLabels),
                'company_id' => $this->companyIdForItem($measure, $workerCompanyIds),
                'company_name' => $this->companyNameForItem($measure, $companyLabels, $workerCompanyLabels),
                'owner_user_id' => null,
                'owner_name' => data_get($measure->details, 'owner'),
                'due_date' => $measure->due_date?->format('Y-m-d'),
                'status_label' => 'Misura scaduta',
                'detail' => $this->familyLabel($measure->family).' da chiudere o verificare',
                'route' => $this->measureRoute($measure),
                'urgency_score' => 400 + max(0, $today->diffInDays($measure->due_date, false) * -1),
            ]))
            ->merge($followUpItems
                ->filter(fn (RiskProfileItem $item) => $item->isFollowUpDue($today))
                ->map(fn (RiskProfileItem $item) => [
                    'kind' => 'follow_up_due',
                    'title' => $item->riskCatalogItem?->name ?? 'Rischio non disponibile',
                    'context' => $this->profileLabel($item, $companyLabels, $workerLabels),
                    'company_id' => $this->companyIdForItem($item, $workerCompanyIds),
                    'company_name' => $this->companyNameForItem($item, $companyLabels, $workerCompanyLabels),
                    'owner_user_id' => $item->operational_owner_user_id,
                    'owner_name' => $item->operationalOwner?->name,
                    'due_date' => $item->follow_up_due_at?->format('Y-m-d'),
                    'status_label' => 'Follow-up in scadenza',
                    'detail' => $item->follow_up_notes ?: 'Criticita\' in carico operativo',
                    'route' => $this->reviewRoute($item),
                    'urgency_score' => 300 + max(0, $today->diffInDays($item->follow_up_due_at, false) * -1),
                ]))
            ->merge($overdueReviews->map(fn (RiskProfileItem $item) => [
                'kind' => 'review_overdue',
                'title' => $item->riskCatalogItem?->name ?? 'Rischio non disponibile',
                'context' => $this->profileLabel($item, $companyLabels, $workerLabels),
                'company_id' => $this->companyIdForItem($item, $workerCompanyIds),
                'company_name' => $this->companyNameForItem($item, $companyLabels, $workerCompanyLabels),
                'owner_user_id' => $item->operational_owner_user_id,
                'owner_name' => $item->operationalOwner?->name,
                'due_date' => $item->review_due_at?->format('Y-m-d'),
                'status_label' => 'Review scaduta',
                'detail' => 'Richiede riallineamento della valutazione consulenziale',
                'route' => $this->reviewRoute($item),
                'urgency_score' => 250 + max(0, $today->diffInDays($item->review_due_at, false) * -1),
            ]))
            ->merge($upcomingMeasures
                ->filter(fn (RiskMeasure $measure) => $measure->due_date !== null)
                ->take(8)
                ->map(fn (RiskMeasure $measure) => [
                    'kind' => 'measure_due',
                    'title' => $measure->title,
                    'context' => $this->profileLabel($measure, $companyLabels, $workerLabels),
                    'company_id' => $this->companyIdForItem($measure, $workerCompanyIds),
                    'company_name' => $this->companyNameForItem($measure, $companyLabels, $workerCompanyLabels),
                    'owner_user_id' => null,
                    'owner_name' => data_get($measure->details, 'owner'),
                    'due_date' => $measure->due_date?->format('Y-m-d'),
                    'status_label' => 'Misura in agenda',
                    'detail' => $this->familyLabel($measure->family).' da presidiare entro breve',
                    'route' => $this->measureRoute($measure),
                    'urgency_score' => 150 - ($today->diffInDays($measure->due_date, false)),
                ]))
            ->sortByDesc('urgency_score')
            ->take(10)
            ->values();

        $ownerAgenda = $agendaQueue
            ->groupBy(fn (array $item) => $item['owner_name'] ?: 'Da assegnare')
            ->map(function (Collection $items, string $ownerName) use ($focus) {
                return [
                    'owner_name' => $ownerName,
                    'owner_user_id' => $items->pluck('owner_user_id')->filter()->first(),
                    'items_count' => $items->count(),
                    'overdue_count' => $items->filter(
                        fn (array $item) => in_array($item['kind'], ['measure_overdue', 'follow_up_due', 'review_overdue'], true)
                    )->count(),
                    'top_item' => $items->sortByDesc('urgency_score')->first()['title'] ?? null,
                    'workspace_route' => $items->pluck('owner_user_id')->filter()->first()
                        ? route('measure-registries.index', [
                            'scope' => 'follow_up_open',
                            'owner_user_id' => $items->pluck('owner_user_id')->filter()->first(),
                            'origin' => 'dashboard',
                            'focus' => $focus,
                        ])
                        : null,
                ];
            })
            ->sortByDesc(fn (array $group) => ($group['overdue_count'] * 100) + $group['items_count'])
            ->values();

        $companyAgenda = $companies
            ->map(function (Company $company) use ($agendaQueue, $focus) {
                $items = $agendaQueue->filter(
                    fn (array $item) => ($item['company_name'] ?? null) === $company->name
                );

                $nextStep = $this->buildCompanyAgendaBridge($company, $items, $focus);

                return [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'items_count' => $items->count(),
                    'overdue_count' => $items->filter(
                        fn (array $item) => in_array($item['kind'], ['measure_overdue', 'follow_up_due', 'review_overdue'], true)
                    )->count(),
                    'top_item' => $items->sortByDesc('urgency_score')->first()['title'] ?? null,
                    'route' => route('companies.risk-profile.show', $company),
                    'workspace_route' => route('measure-registries.index', [
                        'scope' => 'attention',
                        'company_id' => $company->id,
                        'origin' => 'dashboard',
                        'focus' => $focus,
                    ]),
                    'next_step' => $nextStep,
                ];
            })
            ->filter(fn (array $group) => $group['items_count'] > 0)
            ->sortByDesc(fn (array $group) => ($group['overdue_count'] * 100) + $group['items_count'])
            ->values();

        $recentOutcomes = $profileItems
            ->filter(fn (RiskProfileItem $item) => $item->hasRecordedOutcome())
            ->sortByDesc(fn (RiskProfileItem $item) => $item->follow_up_outcome_recorded_at?->timestamp ?? 0)
            ->take(6)
            ->map(fn (RiskProfileItem $item) => [
                'id' => $item->id,
                'risk_name' => $item->riskCatalogItem?->name ?? 'Rischio non disponibile',
                'context' => $this->profileLabel($item, $companyLabels, $workerLabels),
                'company_name' => $this->companyNameForItem($item, $companyLabels, $workerCompanyLabels),
                'owner_name' => $item->operationalOwner?->name,
                'outcome_status' => $item->follow_up_outcome_status,
                'outcome_notes' => $item->follow_up_outcome_notes,
                'recorded_at' => $item->follow_up_outcome_recorded_at?->format('Y-m-d H:i'),
                'route' => $this->reviewRoute($item),
            ])
            ->values();

        $recentTimeline = app(RiskOperationalTimelineBuilder::class)->buildForTenant(
            $tenant,
            $profileItems,
            $measures,
            $companyLabels,
            $workerLabels,
            $workerCompanyLabels,
        );

        $criticalQueue = collect()
            ->merge($overdueMeasures->map(fn (RiskMeasure $measure) => [
                'kind' => 'measure_overdue',
                'tone' => 'danger',
                'title' => $measure->title,
                'context' => $this->profileLabel($measure, $companyLabels, $workerLabels),
                'detail' => 'Misura scaduta - '.$this->familyLabel($measure->family),
                'meta' => $measure->due_date?->format('Y-m-d'),
                'route' => $this->measureRoute($measure),
            ]))
            ->merge($uncoveredItems->map(fn (RiskProfileItem $item) => [
                'kind' => 'risk_uncovered',
                'tone' => 'warning',
                'title' => $item->riskCatalogItem?->name ?? 'Rischio non disponibile',
                'context' => $this->profileLabel($item, $companyLabels, $workerLabels),
                'detail' => 'Rischio non coperto - priorita\' '.$this->priorityLabel($item->effectivePriority()),
                'meta' => $item->riskCatalogItem?->category?->name ?? 'Categoria non disponibile',
                'route' => $this->profileRoute($item),
            ]))
            ->merge($overdueReviews->map(fn (RiskProfileItem $item) => [
                'kind' => 'risk_review_overdue',
                'tone' => 'info',
                'title' => $item->riskCatalogItem?->name ?? 'Rischio non disponibile',
                'context' => $this->profileLabel($item, $companyLabels, $workerLabels),
                'detail' => 'Revisione consulente scaduta',
                'meta' => $item->review_due_at?->format('Y-m-d'),
                'route' => $this->reviewRoute($item),
            ]))
            ->merge($followUpItems
                ->filter(fn (RiskProfileItem $item) => $item->isFollowUpDue($today))
                ->map(fn (RiskProfileItem $item) => [
                    'kind' => 'risk_follow_up_due',
                    'tone' => 'primary',
                    'title' => $item->riskCatalogItem?->name ?? 'Rischio non disponibile',
                    'context' => $this->profileLabel($item, $companyLabels, $workerLabels),
                    'detail' => 'Follow-up operativo in carico a '.($item->operationalOwner?->name ?? 'da assegnare'),
                    'meta' => $item->follow_up_due_at?->format('Y-m-d'),
                    'route' => $this->reviewRoute($item),
                ]))
            ->take(8)
            ->values();

        $companySnapshots = $companies
            ->map(function (Company $company) use ($profileItems, $measures, $today, $focus) {
                $companyWorkerIds = $company->workers->pluck('id');

                $companyProfileItems = $profileItems->filter(function (RiskProfileItem $item) use ($company, $companyWorkerIds) {
                    if ($item->profileable_type === Company::class && (int) $item->profileable_id === (int) $company->id) {
                        return true;
                    }

                    return $item->profileable_type === Worker::class && $companyWorkerIds->contains((int) $item->profileable_id);
                });

                $companyMeasures = $measures->filter(function (RiskMeasure $measure) use ($company, $companyWorkerIds) {
                    if ($measure->profileable_type === Company::class && (int) $measure->profileable_id === (int) $company->id) {
                        return true;
                    }

                    return $measure->profileable_type === Worker::class && $companyWorkerIds->contains((int) $measure->profileable_id);
                });

                $nextDeadline = $companyMeasures
                    ->filter(fn (RiskMeasure $measure) => $measure->due_date !== null && $measure->status !== RiskMeasure::STATUS_IMPLEMENTED)
                    ->sortBy('due_date')
                    ->first();
                $companyEngine = $this->riskEngineSnapshotBuilder->buildFromCollections($companyProfileItems->values(), $companyMeasures->values());

                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'industry' => $company->industry,
                    'city' => trim(collect([$company->city, $company->province])->filter()->implode(' - ')),
                    'workers_count' => $company->workers->count(),
                    'sites_count' => $company->sites->count(),
                    'active_risks' => $companyProfileItems->filter(fn (RiskProfileItem $item) => $item->isOperationallyActive())->count(),
                    'uncovered_risks' => $companyProfileItems->filter(fn (RiskProfileItem $item) => $item->isOperationallyActive())
                        ->where('status', RiskProfileItem::STATUS_UNCOVERED)->count(),
                    'measures_attention' => $companyMeasures->filter(fn (RiskMeasure $measure) => in_array($measure->status, [
                        RiskMeasure::STATUS_NOT_IMPLEMENTED,
                        RiskMeasure::STATUS_TO_VERIFY,
                    ], true))->count(),
                    'overdue_measures' => $companyMeasures->filter(fn (RiskMeasure $measure) => $measure->due_date !== null
                        && $measure->status !== RiskMeasure::STATUS_IMPLEMENTED
                        && $measure->due_date->lt($today))->count(),
                    'reviews_due' => $companyProfileItems->filter(
                        fn (RiskProfileItem $item) => $item->isOperationallyActive() && $item->isReviewDue($today)
                    )->count(),
                    'follow_ups_open' => $companyProfileItems->filter(
                        fn (RiskProfileItem $item) => $item->isOperationallyActive() && $item->hasOpenFollowUp()
                    )->count(),
                    'coverage_rate' => $companyEngine['summary']['coverageRate'],
                    'suggested_core_risks' => $companyEngine['summary']['suggestedCoreRisks'],
                    'expected_core_measures' => $companyEngine['summary']['expectedCoreMeasures'],
                    'missing_expected_measures' => $companyEngine['summary']['missingExpectedMeasures'],
                    'next_deadline' => $nextDeadline?->due_date?->format('Y-m-d'),
                    'next_deadline_label' => $nextDeadline?->title,
                    'route' => route('companies.show', $company),
                    'company_route' => route('companies.show', $company),
                    'risk_profile_route' => route('companies.risk-profile.show', $company),
                    'workspace_route' => route('measure-registries.index', [
                        'scope' => 'attention',
                        'company_id' => $company->id,
                        'origin' => 'dashboard',
                        'focus' => $focus,
                    ]),
                    'review_route' => route('companies.risk-profile.show', [
                        'company' => $company->id,
                        'origin' => 'dashboard',
                        'focus' => 'reviews',
                    ]),
                    'follow_up_route' => route('measure-registries.index', [
                        'family' => 'follow_up',
                        'scope' => 'follow_up_open',
                        'company_id' => $company->id,
                        'origin' => 'dashboard',
                        'focus' => 'follow_up',
                    ]),
                ];
            })
            ->map(function (array $snapshot) {
                $urgentItems = $snapshot['overdue_measures'] + $snapshot['reviews_due'] + $snapshot['follow_ups_open'];
                $coverageGaps = $snapshot['uncovered_risks'] + $snapshot['missing_expected_measures'];
                $fragilityScore = ($snapshot['uncovered_risks'] * 100)
                    + ($snapshot['missing_expected_measures'] * 20)
                    + ((100 - $snapshot['coverage_rate']) * 2)
                    + ($snapshot['overdue_measures'] * 10);

                $decision = match (true) {
                    $snapshot['overdue_measures'] > 0 => [
                        'label' => 'Chiudere scaduti',
                        'tone' => 'danger',
                        'helper' => $snapshot['overdue_measures'].' misure oltre data richiedono presidio immediato.',
                        'route' => $snapshot['workspace_route'],
                        'focus' => 'deadlines',
                    ],
                    $snapshot['reviews_due'] > 0 => [
                        'label' => 'Riallineare review',
                        'tone' => 'info',
                        'helper' => $snapshot['reviews_due'].' review richiedono una decisione consulente aggiornata.',
                        'route' => route('companies.risk-profile.show', [
                            'company' => $snapshot['id'],
                            'origin' => 'dashboard',
                            'focus' => 'reviews',
                        ]),
                        'focus' => 'reviews',
                    ],
                    $snapshot['follow_ups_open'] > 0 => [
                        'label' => 'Seguire follow-up',
                        'tone' => 'primary',
                        'helper' => $snapshot['follow_ups_open'].' criticita\' operative sono ancora in carico.',
                        'route' => $snapshot['workspace_route'],
                        'focus' => 'follow_up',
                    ],
                    $coverageGaps > 0 => [
                        'label' => 'Colmare gap',
                        'tone' => 'warning',
                        'helper' => $coverageGaps.' scoperture o presidi attesi mancanti chiedono riallineamento.',
                        'route' => route('companies.risk-profile.show', [
                            'company' => $snapshot['id'],
                            'origin' => 'dashboard',
                            'focus' => 'all',
                        ]),
                        'focus' => 'all',
                    ],
                    default => [
                        'label' => 'Monitorare presidio',
                        'tone' => 'success',
                        'helper' => 'Nessuna urgenza aperta: resta monitoraggio periodico del contesto.',
                        'route' => $snapshot['company_route'],
                        'focus' => 'all',
                    ],
                };

                return [
                    ...$snapshot,
                    'urgent_items' => $urgentItems,
                    'coverage_gaps' => $coverageGaps,
                    'fragility_score' => $fragilityScore,
                    'decision' => $decision,
                    'bridge_summary' => collect([
                        $snapshot['missing_expected_measures'] > 0 ? $snapshot['missing_expected_measures'].' gap attesi' : null,
                        $snapshot['measures_attention'] > 0 ? $snapshot['measures_attention'].' misure pendenti' : null,
                        $snapshot['follow_ups_open'] > 0 ? $snapshot['follow_ups_open'].' follow-up aperti' : null,
                    ])->filter()->implode(' | '),
                ];
            })
            ->sortByDesc(fn (array $snapshot) => ($snapshot['uncovered_risks'] * 100) + ($snapshot['overdue_measures'] * 10) + $snapshot['measures_attention'])
            ->take(6)
            ->values();

        $operationalItems = $profileItems->filter(fn (RiskProfileItem $item) => $item->isOperationallyActive());

        $coverageRate = $operationalItems->count() > 0
            ? (int) round(($operationalItems->where('status', RiskProfileItem::STATUS_COVERED)->count() / $operationalItems->count()) * 100)
            : 0;
        $engine = $this->riskEngineSnapshotBuilder->buildFromCollections($profileItems, $measures);
        $coverageSignals = [
            [
                'label' => 'Agganci diretti',
                'value' => $engine['summary']['directExpectedMeasures'],
                'helper' => 'Misure collegate direttamente ai presidi attesi letti dal motore.',
                'tone' => 'success',
            ],
            [
                'label' => 'Coperture equivalenti',
                'value' => $engine['summary']['substitutedExpectedMeasures'],
                'helper' => 'Presidi attesi coperti per equivalenza della stessa famiglia.',
                'tone' => 'info',
            ],
            [
                'label' => 'Misure libere',
                'value' => $engine['summary']['freeMeasures'],
                'helper' => 'Misure registrate sul rischio ma non allineate a un presidio atteso.',
                'tone' => 'secondary',
            ],
            [
                'label' => 'Gap attesi',
                'value' => $engine['summary']['risksWithExpectedGaps'],
                'helper' => 'Rischi attivi con almeno un presidio atteso ancora mancante o parziale.',
                'tone' => 'danger',
            ],
        ];
        $portfolioHotspots = $companySnapshots
            ->filter(fn (array $snapshot) => $snapshot['active_risks'] > 0)
            ->sortByDesc('fragility_score')
            ->take(5)
            ->map(fn (array $snapshot) => [
                'company_id' => $snapshot['id'],
                'company_name' => $snapshot['name'],
                'industry' => $snapshot['industry'],
                'coverage_rate' => $snapshot['coverage_rate'],
                'active_risks' => $snapshot['active_risks'],
                'uncovered_risks' => $snapshot['uncovered_risks'],
                'missing_expected_measures' => $snapshot['missing_expected_measures'],
                'suggested_core_risks' => $snapshot['suggested_core_risks'],
                'urgent_items' => $snapshot['urgent_items'],
                'decision' => $snapshot['decision'],
                'workspace_route' => $snapshot['workspace_route'],
                'risk_profile_route' => $snapshot['risk_profile_route'],
            ])
            ->values();
        $pressureCategories = $operationalItems
            ->groupBy(fn (RiskProfileItem $item) => $item->riskCatalogItem?->category?->name ?? 'Categoria non disponibile')
            ->map(function (Collection $items, string $category) use ($workerCompanyIds) {
                $companyIds = $items
                    ->map(function (RiskProfileItem $item) use ($workerCompanyIds) {
                        return $item->profileable_type === Worker::class
                            ? (int) ($workerCompanyIds->get($item->profileable_id) ?? 0)
                            : (int) $item->profileable_id;
                    })
                    ->filter()
                    ->unique();

                return [
                    'category' => $category,
                    'active_risks' => $items->count(),
                    'uncovered_risks' => $items->where('status', RiskProfileItem::STATUS_UNCOVERED)->count(),
                    'high_priority_risks' => $items->filter(fn (RiskProfileItem $item) => $item->effectivePriority() === 'high')->count(),
                    'companies_count' => $companyIds->count(),
                    'dominant_signal' => $items->where('status', RiskProfileItem::STATUS_UNCOVERED)->count() > 0
                        ? 'Scoperture attive da riallineare'
                        : ($items->filter(fn (RiskProfileItem $item) => $item->effectivePriority() === 'high')->count() > 0
                            ? 'Alta priorita\' diffusa nel tenant'
                            : 'Pressione diffusa da monitorare'),
                    'recommended_focus' => $items->where('status', RiskProfileItem::STATUS_UNCOVERED)->count() > 0
                        ? 'Aprire profili e registri sulle aziende piu\' esposte.'
                        : ($items->filter(fn (RiskProfileItem $item) => $item->effectivePriority() === 'high')->count() > 0
                            ? 'Verificare review consulente e priorita\' operative.'
                            : 'Mantenere monitoraggio e copertura dei presidi.'),
                ];
            })
            ->sortByDesc(fn (array $entry) => ($entry['uncovered_risks'] * 100) + ($entry['high_priority_risks'] * 10) + $entry['active_risks'])
            ->take(6)
            ->values();
        $topCoverageGapCompany = $companySnapshots->sortByDesc('coverage_gaps')->first();
        $prioritySignals = [
            [
                'label' => 'Urgenze oggi',
                'value' => $companySnapshots->filter(
                    fn (array $snapshot) => $snapshot['urgent_items'] > 0
                )->count(),
                'helper' => 'Aziende con scaduti, review in ritardo o follow-up gia\' da presidiare.',
                'tone' => 'danger',
            ],
            [
                'label' => 'Fragilita\' copertura',
                'value' => $companySnapshots->filter(
                    fn (array $snapshot) => $snapshot['uncovered_risks'] > 0 || $snapshot['missing_expected_measures'] > 0
                )->count(),
                'helper' => 'Aziende con rischio scoperto o presidi attesi ancora mancanti.',
                'tone' => 'warning',
            ],
            [
                'label' => 'Alta priorita\' scoperta',
                'value' => $operationalItems->filter(
                    fn (RiskProfileItem $item) => $item->status === RiskProfileItem::STATUS_UNCOVERED
                        && $item->effectivePriority() === 'high'
                )->count(),
                'helper' => 'Rischi attivi ad alta priorita\' ancora senza presidio effettivo.',
                'tone' => 'primary',
            ],
            [
                'label' => 'Categorie in pressione',
                'value' => $pressureCategories->filter(
                    fn (array $entry) => $entry['uncovered_risks'] > 0 || $entry['high_priority_risks'] > 0
                )->count(),
                'helper' => 'Famiglie di rischio che concentrano scoperture o priorita\' alte sul portafoglio.',
                'tone' => 'info',
            ],
        ];
        $decisionBoard = [
            [
                'title' => 'Agire oggi',
                'count' => $companySnapshots->filter(fn (array $snapshot) => $snapshot['urgent_items'] > 0)->count(),
                'helper' => 'Priorita\' guidata da scaduti, review in ritardo e follow-up gia\' da presidiare.',
                'top_label' => $portfolioHotspots->first()['company_name'] ?? null,
                'top_helper' => $portfolioHotspots->first()['decision']['helper'] ?? null,
                'cta_label' => 'Apri urgenze',
                'cta_route' => route('dashboard', ['focus' => 'urgent']),
                'tone' => 'danger',
            ],
            [
                'title' => 'Colmare copertura',
                'count' => $companySnapshots->filter(fn (array $snapshot) => $snapshot['coverage_gaps'] > 0)->count(),
                'helper' => 'Contesti con rischi scoperti o presidi attesi ancora mancanti nel profilo operativo.',
                'top_label' => $topCoverageGapCompany['name'] ?? null,
                'top_helper' => isset($topCoverageGapCompany['coverage_gaps'])
                    ? $topCoverageGapCompany['coverage_gaps'].' gap da riallineare.'
                    : null,
                'cta_label' => 'Apri aziende',
                'cta_route' => route('companies.index'),
                'tone' => 'warning',
            ],
            [
                'title' => 'Riallineare categorie',
                'count' => $pressureCategories->filter(
                    fn (array $entry) => $entry['uncovered_risks'] > 0 || $entry['high_priority_risks'] > 0
                )->count(),
                'helper' => 'Categorie che oggi concentrano la pressione piu\' forte sul portafoglio consulenziale.',
                'top_label' => $pressureCategories->first()['category'] ?? null,
                'top_helper' => $pressureCategories->first()['dominant_signal'] ?? null,
                'cta_label' => 'Apri review',
                'cta_route' => route('dashboard', ['focus' => 'reviews']),
                'tone' => 'info',
            ],
        ];

        $focusCounts = [
            'all' => $agendaQueue->count(),
            'urgent' => $this->filterAgendaByFocus($agendaQueue, 'urgent')->count(),
            'deadlines' => $this->filterAgendaByFocus($agendaQueue, 'deadlines')->count(),
            'follow_up' => $this->filterAgendaByFocus($agendaQueue, 'follow_up')->count(),
            'reviews' => $this->filterAgendaByFocus($agendaQueue, 'reviews')->count(),
        ];

        $focusMeta = [
            'all' => [
                'label' => 'Vista completa',
                'description' => 'Lettura completa del workspace operativo tra agenda, review, follow-up, timeline e presidi.',
                'highlights' => [
                    ['label' => 'Scaduti da chiudere', 'value' => $overdueMeasures->count()],
                    ['label' => 'Review da riallineare', 'value' => $overdueReviews->count()],
                    ['label' => 'Follow-up aperti', 'value' => $followUpItems->count()],
                ],
                'primaryAction' => [
                    'label' => 'Apri corsia scaduti',
                    'href' => route('measure-registries.index', [
                        'scope' => 'overdue',
                        'origin' => 'dashboard',
                        'focus' => 'all',
                    ]),
                ],
            ],
            'urgent' => [
                'label' => 'Urgenti',
                'description' => 'Scaduti, follow-up dovuti e review in ritardo che chiedono presidio rapido.',
                'highlights' => [
                    ['label' => 'Misure scadute', 'value' => $overdueMeasures->count()],
                    ['label' => 'Review scadute', 'value' => $overdueReviews->count()],
                    ['label' => 'Rischi scoperti', 'value' => $uncoveredItems->count()],
                ],
                'primaryAction' => [
                    'label' => 'Apri corsia urgente',
                    'href' => route('measure-registries.index', [
                        'scope' => 'overdue',
                        'origin' => 'dashboard',
                        'focus' => 'urgent',
                    ]),
                ],
            ],
            'deadlines' => [
                'label' => 'Scadenze',
                'description' => 'Vista centrata su misure in agenda o scadute e sui relativi segnali operativi.',
                'highlights' => [
                    ['label' => 'Scadenze imminenti', 'value' => $upcomingMeasures->count()],
                    ['label' => 'Misure da verificare', 'value' => $measures->where('status', RiskMeasure::STATUS_TO_VERIFY)->count()],
                    ['label' => 'Misure scadute', 'value' => $overdueMeasures->count()],
                ],
                'primaryAction' => [
                    'label' => 'Apri registri scadenze',
                    'href' => route('measure-registries.index', [
                        'scope' => 'attention',
                        'origin' => 'dashboard',
                        'focus' => 'deadlines',
                    ]),
                ],
            ],
            'follow_up' => [
                'label' => 'Follow-up',
                'description' => 'Criticita\' in carico, presa in carico operativa ed esiti recenti del presidio.',
                'highlights' => [
                    ['label' => 'Follow-up aperti', 'value' => $followUpItems->count()],
                    ['label' => 'Esiti registrati', 'value' => $recentOutcomes->count()],
                    ['label' => 'Referenti attivi', 'value' => $ownerAgenda->count()],
                ],
                'primaryAction' => [
                    'label' => 'Apri follow-up in carico',
                    'href' => route('measure-registries.index', [
                        'family' => 'follow_up',
                        'scope' => 'follow_up_open',
                        'origin' => 'dashboard',
                        'focus' => 'follow_up',
                    ]),
                ],
            ],
            'reviews' => [
                'label' => 'Review',
                'description' => 'Riallineamento consulenziale su rischi con revisione pianificata o gia\' scaduta.',
                'highlights' => [
                    ['label' => 'Revisioni in agenda', 'value' => $reviewDueItems->count()],
                    ['label' => 'Review scadute', 'value' => $overdueReviews->count()],
                    ['label' => 'Eventi timeline', 'value' => $recentTimeline->count()],
                ],
                'primaryAction' => [
                    'label' => 'Apri review in agenda',
                    'href' => route('dashboard', ['focus' => 'reviews']),
                ],
            ],
        ];

        $filteredUpcomingMeasures = $this->filterDeadlinesByFocus($upcomingMeasures, $focus, $today);
        $filteredAttentionMeasures = $this->filterAttentionMeasuresByFocus($attentionMeasures, $focus, $today);
        $filteredReviewQueue = $this->filterReviewQueueByFocus($reviewDueItems, $focus, $today);
        $filteredFollowUpQueue = $this->filterFollowUpQueueByFocus($followUpItems, $focus, $today);
        $filteredAgendaQueue = $this->filterAgendaByFocus($agendaQueue, $focus);
        $filteredRecentOutcomes = $this->filterRecentOutcomesByFocus($recentOutcomes, $focus);
        $filteredRecentTimeline = $this->filterTimelineByFocus($recentTimeline, $focus);

        return [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'activeFocus' => $focus,
            'focusOptions' => [
                ['value' => 'all', 'label' => 'Completa', 'count' => $focusCounts['all']],
                ['value' => 'urgent', 'label' => 'Urgenti', 'count' => $focusCounts['urgent']],
                ['value' => 'deadlines', 'label' => 'Scadenze', 'count' => $focusCounts['deadlines']],
                ['value' => 'follow_up', 'label' => 'Follow-up', 'count' => $focusCounts['follow_up']],
                ['value' => 'reviews', 'label' => 'Review', 'count' => $focusCounts['reviews']],
            ],
            'focusMeta' => $focusMeta[$focus],
            'engineSummary' => $engine['summary'],
            'engineFlow' => $engine['flow'],
            'coreStarterPack' => $engine['coreStarterPack'],
            'coverageSignals' => $coverageSignals,
            'decisionBoard' => $decisionBoard,
            'prioritySignals' => $prioritySignals,
            'portfolioHotspots' => $portfolioHotspots,
            'pressureCategories' => $pressureCategories,
            'workspaceLanes' => [
                [
                    'title' => 'Scaduti da chiudere',
                    'helper' => 'Porta direttamente alle misure oltre data nel workspace registri.',
                    'count' => $overdueMeasures->count(),
                    'icon' => 'ri-alarm-warning-line',
                    'tone' => 'danger',
                    'href' => route('measure-registries.index', [
                        'scope' => 'overdue',
                        'origin' => 'dashboard',
                        'focus' => 'deadlines',
                    ]),
                ],
                [
                    'title' => 'Follow-up in carico',
                    'helper' => 'Apre la corsia dei rischi con presa in carico operativa attiva.',
                    'count' => $followUpItems->count(),
                    'icon' => 'ri-user-follow-line',
                    'tone' => 'primary',
                    'href' => route('measure-registries.index', [
                        'family' => 'follow_up',
                        'scope' => 'follow_up_open',
                        'origin' => 'dashboard',
                        'focus' => 'follow_up',
                    ]),
                ],
                [
                    'title' => 'Review da riallineare',
                    'helper' => 'Riapre la dashboard gia\' filtrata sulle revisioni consulente.',
                    'count' => $reviewDueItems->count(),
                    'icon' => 'ri-calendar-check-line',
                    'tone' => 'info',
                    'href' => route('dashboard', ['focus' => 'reviews']),
                ],
            ],
            'summary' => [
                'companiesMonitored' => $companies->count(),
                'workersMonitored' => $workerIds->count(),
                'activeRisks' => $operationalItems->count(),
                'imminentDeadlines' => $upcomingMeasures->count(),
                'measuresToVerify' => $measures->where('status', RiskMeasure::STATUS_TO_VERIFY)->count(),
                'openCriticalities' => $overdueMeasures->count() + $uncoveredItems->count(),
                'overdueMeasures' => $overdueMeasures->count(),
                'reviewsDue' => $reviewDueItems->count(),
                'overdueReviews' => $overdueReviews->count(),
                'followUpsOpen' => $followUpItems->count(),
                'agendaItems' => $agendaQueue->count(),
                'ownersInAgenda' => $ownerAgenda->count(),
                'companiesInAgenda' => $companyAgenda->count(),
                'recordedOutcomes' => $recentOutcomes->count(),
                'timelineEvents' => $recentTimeline->count(),
                'uncoveredRisks' => $uncoveredItems->count(),
                'coveredRisks' => $operationalItems->where('status', RiskProfileItem::STATUS_COVERED)->count(),
                'coverageRate' => $coverageRate,
            ],
            'alerts' => [
                [
                    'tone' => $overdueMeasures->isEmpty() ? 'success' : 'danger',
                    'title' => $overdueMeasures->isEmpty() ? 'Nessuna misura scaduta' : 'Misure scadute da presidiare',
                    'text' => $overdueMeasures->isEmpty()
                        ? 'Al momento non risultano misure scadute nel tenant corrente.'
                        : $overdueMeasures->count().' misure sono gia\' oltre la data prevista e richiedono presidio operativo.',
                ],
                [
                    'tone' => $uncoveredItems->isEmpty() ? 'success' : 'warning',
                    'title' => $uncoveredItems->isEmpty() ? 'Nessun rischio scoperto' : 'Rischi ancora non coperti',
                    'text' => $uncoveredItems->isEmpty()
                        ? 'Tutti i rischi dedotti risultano al momento coperti da almeno una misura.'
                        : $uncoveredItems->count().' rischi risultano ancora senza copertura operativa effettiva.',
                ],
                [
                    'tone' => $overdueReviews->isEmpty() ? 'success' : 'info',
                    'title' => $overdueReviews->isEmpty() ? 'Revisioni consulente allineate' : 'Revisioni consulente in ritardo',
                    'text' => $overdueReviews->isEmpty()
                        ? 'Non risultano revisioni rischio scadute nel tenant corrente.'
                        : $overdueReviews->count().' rischi richiedono una revisione consulente oltre la data pianificata.',
                ],
                [
                    'tone' => $followUpItems->isEmpty() ? 'success' : 'primary',
                    'title' => $followUpItems->isEmpty() ? 'Nessun follow-up aperto' : 'Follow-up operativi da presidiare',
                    'text' => $followUpItems->isEmpty()
                        ? 'Non risultano criticita\' in presa in carico operativa.'
                        : $followUpItems->count().' rischi hanno un follow-up aperto da monitorare nel workspace.',
                ],
            ],
            'upcomingDeadlines' => $filteredUpcomingMeasures
                ->take(8)
                ->map(function (RiskMeasure $measure) use ($riskProfileItemsByKey, $workerCompanyIds, $companyLabels, $workerCompanyLabels, $workerLabels, $measureBindingsById) {
                    $profileItem = $riskProfileItemsByKey->get(
                        $this->riskScopeKey($measure->profileable_type, (int) $measure->profileable_id, (int) $measure->risk_catalog_item_id)
                    );

                    return [
                        'id' => $measure->id,
                        'title' => $measure->title,
                        'family' => $this->familyLabel($measure->family),
                        'status' => $this->statusLabel($measure->status),
                        'due_date' => $measure->due_date?->format('Y-m-d'),
                        'context' => $this->profileLabel($measure, $companyLabels, $workerLabels),
                        'risk_name' => $measure->riskCatalogItem?->name,
                        'expected_binding' => $measureBindingsById->get($measure->id),
                        'route' => $this->measureRoute($measure),
                        'profile_route' => $profileItem ? $this->profileRoute($profileItem) : null,
                        'review_route' => $profileItem ? $this->reviewRoute($profileItem) : null,
                        'measures_route' => $profileItem ? $this->measureManageRoute($profileItem) : null,
                        'next_step' => $this->buildMeasureItemBridge($measure, $profileItem, $measureBindingsById->get($measure->id), 'deadlines'),
                        'registry_route' => route('measure-registries.index', [
                            'scope' => 'attention',
                            'company_id' => $this->companyIdForItem($measure, $workerCompanyIds),
                            'origin' => 'dashboard',
                            'focus' => 'deadlines',
                        ]),
                        'company_name' => $this->companyNameForItem($measure, $companyLabels, $workerCompanyLabels),
                    ];
                })
                ->values(),
            'attentionMeasures' => $filteredAttentionMeasures
                ->take(8)
                ->map(function (RiskMeasure $measure) use ($riskProfileItemsByKey, $companyLabels, $workerLabels, $measureBindingsById) {
                    $profileItem = $riskProfileItemsByKey->get(
                        $this->riskScopeKey($measure->profileable_type, (int) $measure->profileable_id, (int) $measure->risk_catalog_item_id)
                    );

                    return [
                        'id' => $measure->id,
                        'title' => $measure->title,
                        'family' => $this->familyLabel($measure->family),
                        'status' => $this->statusLabel($measure->status),
                        'context' => $this->profileLabel($measure, $companyLabels, $workerLabels),
                        'due_date' => $measure->due_date?->format('Y-m-d') ?? 'Non definita',
                        'expected_binding' => $measureBindingsById->get($measure->id),
                        'route' => $this->measureRoute($measure),
                        'review_route' => $profileItem ? $this->reviewRoute($profileItem) : null,
                        'measures_route' => $profileItem ? $this->measureManageRoute($profileItem) : null,
                        'next_step' => $this->buildMeasureItemBridge($measure, $profileItem, $measureBindingsById->get($measure->id), 'attention'),
                    ];
                })
                ->values(),
            'criticalQueue' => $criticalQueue,
            'reviewQueue' => $filteredReviewQueue
                ->take(8)
                ->map(function (RiskProfileItem $item) use ($today, $companyLabels, $workerLabels) {
                    $status = $item->review_due_at !== null && $item->review_due_at->lt($today) ? 'Scaduta' : 'Pianificata';

                    return [
                        'id' => $item->id,
                        'risk_name' => $item->riskCatalogItem?->name ?? 'Rischio non disponibile',
                        'context' => $this->profileLabel($item, $companyLabels, $workerLabels),
                        'due_date' => $item->review_due_at?->format('Y-m-d'),
                        'status' => $status,
                        'decision' => $item->consultant_decision,
                        'route' => $this->reviewRoute($item),
                        'next_step' => $this->buildReviewQueueBridge($item, $status),
                    ];
                })
                ->values(),
            'followUpQueue' => $filteredFollowUpQueue
                ->take(8)
                ->map(function (RiskProfileItem $item) use ($openMeasuresByRiskContext, $companyLabels, $workerLabels, $workerCompanyIds) {
                    $openMeasures = $openMeasuresByRiskContext->get(
                        $this->riskScopeKey($item->profileable_type, (int) $item->profileable_id, (int) $item->risk_catalog_item_id),
                        collect(),
                    );
                    $nextMeasure = $openMeasures
                        ->sortBy(fn (RiskMeasure $measure) => $measure->due_date?->timestamp ?? PHP_INT_MAX)
                        ->first();
                    $registryRoute = route('measure-registries.index', array_filter([
                        'family' => 'follow_up',
                        'scope' => 'follow_up_open',
                        'company_id' => $this->companyIdForItem($item, $workerCompanyIds),
                        'owner_user_id' => $item->operational_owner_user_id,
                        'origin' => 'dashboard',
                        'focus' => 'follow_up',
                    ], fn ($value) => $value !== null && $value !== ''));

                    return [
                        'id' => $item->id,
                        'risk_name' => $item->riskCatalogItem?->name ?? 'Rischio non disponibile',
                        'context' => $this->profileLabel($item, $companyLabels, $workerLabels),
                        'status' => $item->follow_up_status,
                        'owner_name' => $item->operationalOwner?->name,
                        'due_date' => $item->follow_up_due_at?->format('Y-m-d'),
                        'notes' => $item->follow_up_notes,
                        'open_measure_count' => $openMeasures->count(),
                        'next_measure_title' => $nextMeasure?->title,
                        'next_measure_due_date' => $nextMeasure?->due_date?->format('Y-m-d'),
                        'route' => $this->reviewRoute($item),
                        'profile_route' => $this->profileRoute($item),
                        'registry_route' => $registryRoute,
                        'next_step' => $this->buildFollowUpQueueBridge($item, $openMeasures->count(), $nextMeasure?->due_date?->format('Y-m-d'), $registryRoute),
                    ];
                })
                ->values(),
            'agendaQueue' => $filteredAgendaQueue
                ->map(fn (array $item) => collect($item)->except('urgency_score')->all())
                ->values(),
            'ownerAgenda' => $ownerAgenda,
            'companyAgenda' => $companyAgenda,
            'recentOutcomes' => $filteredRecentOutcomes,
            'recentTimeline' => $filteredRecentTimeline,
            'companySnapshots' => $companySnapshots,
        ];
    }

    private function profileLabel(RiskProfileItem|RiskMeasure $item, Collection $companyLabels, Collection $workerLabels): string
    {
        return $item->profileable_type === Worker::class
            ? ($workerLabels->get($item->profileable_id) ?? 'Lavoratore')
            : ($companyLabels->get($item->profileable_id) ?? 'Azienda');
    }

    private function companyNameForItem(
        RiskProfileItem|RiskMeasure $item,
        Collection $companyLabels,
        Collection $workerCompanyLabels,
    ): string {
        return $item->profileable_type === Worker::class
            ? ($workerCompanyLabels->get($item->profileable_id) ?? 'Azienda non disponibile')
            : ($companyLabels->get($item->profileable_id) ?? 'Azienda non disponibile');
    }

    private function companyIdForItem(RiskProfileItem|RiskMeasure $item, Collection $workerCompanyIds): int
    {
        return $item->profileable_type === Worker::class
            ? (int) ($workerCompanyIds->get($item->profileable_id) ?? 0)
            : (int) $item->profileable_id;
    }

    private function measureRoute(RiskMeasure $measure): string
    {
        return $measure->profileable_type === Worker::class
            ? route('workers.risk-profile.show', $measure->profileable_id)
            : route('companies.risk-profile.show', $measure->profileable_id);
    }

    private function profileRoute(RiskProfileItem $item): string
    {
        return $item->profileable_type === Worker::class
            ? route('workers.risk-profile.show', $item->profileable_id)
            : route('companies.risk-profile.show', $item->profileable_id);
    }

    private function reviewRoute(RiskProfileItem $item): string
    {
        return $item->profileable_type === Worker::class
            ? route('workers.risk-profile.review.show', [$item->profileable_id, $item->id])
            : route('companies.risk-profile.review.show', [$item->profileable_id, $item->id]);
    }

    private function measureManageRoute(RiskProfileItem $item): string
    {
        return $item->profileable_type === Worker::class
            ? route('workers.risk-profile.measures.show', [$item->profileable_id, $item->id])
            : route('companies.risk-profile.measures.show', [$item->profileable_id, $item->id]);
    }

    private function riskScopeKey(string $profileableType, int $profileableId, int $riskCatalogItemId): string
    {
        return implode(':', [$profileableType, $profileableId, $riskCatalogItemId]);
    }

    private function familyLabel(string $family): string
    {
        return match ($family) {
            RiskMeasure::FAMILY_TRAINING => 'Formazione',
            RiskMeasure::FAMILY_MEDICAL => 'Visita medica',
            RiskMeasure::FAMILY_DPI => 'DPI',
            RiskMeasure::FAMILY_TECHNICAL => 'Tecnica',
            default => 'Organizzativa',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            RiskMeasure::STATUS_IMPLEMENTED => 'Attuata',
            RiskMeasure::STATUS_TO_VERIFY => 'Da verificare',
            default => 'Non attuata',
        };
    }

    private function priorityWeight(?string $priority): int
    {
        return match ($priority) {
            'high' => 3,
            'medium' => 2,
            default => 1,
        };
    }

    private function priorityLabel(?string $priority): string
    {
        return match ($priority) {
            'high' => 'alta',
            'medium' => 'media',
            default => 'bassa',
        };
    }

    private function buildMeasureItemBridge(
        RiskMeasure $measure,
        ?RiskProfileItem $profileItem,
        ?array $expectedBinding,
        string $context,
    ): array {
        if ($profileItem === null) {
            return [
                'label' => 'Apri profilo',
                'helper' => 'Il contesto misura non ha ancora un bridge esplicito di review disponibile.',
                'route' => $this->measureRoute($measure),
            ];
        }

        $binding = $expectedBinding['binding'] ?? null;

        return match (true) {
            $measure->status === RiskMeasure::STATUS_NOT_IMPLEMENTED => [
                'label' => 'Completa presidio in misure',
                'helper' => $binding === 'direct_expected' || $binding === 'family_substitution'
                    ? 'La misura e\' ancora non attuata su un presidio atteso del rischio.'
                    : 'La misura e\' ancora non attuata e va consolidata prima della review.',
                'route' => $this->measureManageRoute($profileItem),
            ],
            $measure->status === RiskMeasure::STATUS_TO_VERIFY => [
                'label' => 'Verifica presidio in review',
                'helper' => $context === 'deadlines'
                    ? 'Dopo la scadenza conviene chiudere la verifica operativa del rischio.'
                    : 'La misura e\' aperta ma gia\' vicina a una validazione del rischio in review.',
                'route' => $this->reviewRoute($profileItem),
            ],
            default => [
                'label' => 'Apri contesto rischio',
                'helper' => 'Il presidio e\' gia\' registrato: usa review e misure per leggerne il ruolo nel rischio.',
                'route' => $this->reviewRoute($profileItem),
            ],
        };
    }

    private function buildReviewQueueBridge(RiskProfileItem $item, string $status): array
    {
        return $status === 'Scaduta'
            ? [
                'label' => 'Riallinea review adesso',
                'helper' => 'La data review e\' superata: conviene aggiornare subito la valutazione consulente sul rischio.',
                'route' => $this->reviewRoute($item),
            ]
            : [
                'label' => 'Apri review pianificata',
                'helper' => 'La review e\' gia\' in agenda: puoi verificare priorita\', follow-up e stato finale prima della scadenza.',
                'route' => $this->reviewRoute($item),
            ];
    }

    private function buildFollowUpQueueBridge(
        RiskProfileItem $item,
        int $openMeasureCount,
        ?string $nextMeasureDueDate,
        string $registryRoute,
    ): array {
        if ($item->follow_up_status === RiskProfileItem::FOLLOW_UP_STATUS_BLOCKED) {
            return [
                'label' => 'Sblocca follow-up in review',
                'helper' => 'Il follow-up e\' bloccato: torna alla review per chiarire blocchi, ownership o prossimo presidio.',
                'route' => $this->reviewRoute($item),
            ];
        }

        if ($openMeasureCount > 0) {
            return [
                'label' => 'Apri registri in carico',
                'helper' => $nextMeasureDueDate
                    ? 'Ci sono '.$openMeasureCount.' misure aperte; la prossima scade il '.$nextMeasureDueDate.'.'
                    : 'Ci sono '.$openMeasureCount.' misure aperte da riallineare nel registro contestuale.',
                'route' => $registryRoute,
            ];
        }

        return [
            'label' => 'Rientra in review',
            'helper' => 'Il follow-up e\' aperto ma non ha misure pendenti: il prossimo passo utile e\' chiarire l\'esito direttamente in review.',
            'route' => $this->reviewRoute($item),
        ];
    }

    private function buildCompanyAgendaBridge(Company $company, Collection $items, string $focus): array
    {
        $hasOverdueMeasure = $items->contains(fn (array $item) => $item['kind'] === 'measure_overdue');
        $hasFollowUpDue = $items->contains(fn (array $item) => $item['kind'] === 'follow_up_due');
        $hasReviewDue = $items->contains(fn (array $item) => $item['kind'] === 'review_overdue');

        if ($hasOverdueMeasure) {
            return [
                'label' => 'Chiudi scaduti nei registri',
                'helper' => 'Il contesto aziendale ha gia\' almeno una misura scaduta: conviene aprire il registro filtrato sui ritardi.',
                'route' => route('measure-registries.index', [
                    'scope' => 'overdue',
                    'company_id' => $company->id,
                    'origin' => 'dashboard',
                    'focus' => 'deadlines',
                ]),
            ];
        }

        if ($hasFollowUpDue) {
            return [
                'label' => 'Apri follow-up azienda',
                'helper' => 'Le criticita\' aperte richiedono un riallineamento tra profilo rischio e registri in carico.',
                'route' => route('companies.risk-profile.show', [
                    'company' => $company,
                    'origin' => 'dashboard',
                    'focus' => 'follow_up',
                ]),
            ];
        }

        if ($hasReviewDue) {
            return [
                'label' => 'Riallinea review azienda',
                'helper' => 'Il prossimo passo utile e\' rileggere il profilo rischio aziendale prima che le review restino solo in agenda.',
                'route' => route('companies.risk-profile.show', [
                    'company' => $company,
                    'origin' => 'dashboard',
                    'focus' => 'reviews',
                ]),
            ];
        }

        return [
            'label' => 'Apri registri azienda',
            'helper' => 'Il contesto e\' gia\' filtrato per azienda: puoi stringere il lavoro operativo senza cambiare workspace.',
            'route' => route('measure-registries.index', [
                'scope' => 'attention',
                'company_id' => $company->id,
                'origin' => 'dashboard',
                'focus' => $focus,
            ]),
        ];
    }

    private function filterAgendaByFocus(Collection $agendaQueue, string $focus): Collection
    {
        return match ($focus) {
            'urgent' => $agendaQueue
                ->filter(fn (array $item) => in_array($item['kind'], ['measure_overdue', 'follow_up_due', 'review_overdue'], true))
                ->values(),
            'deadlines' => $agendaQueue
                ->filter(fn (array $item) => in_array($item['kind'], ['measure_overdue', 'measure_due'], true))
                ->values(),
            'follow_up' => $agendaQueue
                ->filter(fn (array $item) => $item['kind'] === 'follow_up_due')
                ->values(),
            'reviews' => $agendaQueue
                ->filter(fn (array $item) => $item['kind'] === 'review_overdue')
                ->values(),
            default => $agendaQueue,
        };
    }

    private function filterTimelineByFocus(Collection $timeline, string $focus): Collection
    {
        return match ($focus) {
            'urgent' => $timeline
                ->filter(fn (array $event) => in_array($event['type'], ['follow_up', 'review', 'measure_due'], true))
                ->values(),
            'deadlines' => $timeline
                ->filter(fn (array $event) => in_array($event['type'], ['measure_due', 'measure_created', 'measure_completed'], true))
                ->values(),
            'follow_up' => $timeline
                ->filter(fn (array $event) => in_array($event['type'], ['follow_up', 'outcome'], true))
                ->values(),
            'reviews' => $timeline
                ->filter(fn (array $event) => $event['type'] === 'review')
                ->values(),
            default => $timeline,
        };
    }

    private function filterDeadlinesByFocus(Collection $deadlines, string $focus, CarbonImmutable $today): Collection
    {
        return match ($focus) {
            'urgent' => $deadlines
                ->filter(fn (RiskMeasure $measure) => $measure->due_date !== null && $measure->due_date->lte($today->addDays(7)))
                ->values(),
            'deadlines' => $deadlines->values(),
            'follow_up', 'reviews' => collect(),
            default => $deadlines,
        };
    }

    private function filterAttentionMeasuresByFocus(Collection $attentionMeasures, string $focus, CarbonImmutable $today): Collection
    {
        return match ($focus) {
            'urgent' => $attentionMeasures
                ->filter(fn (RiskMeasure $measure) => $measure->due_date !== null && $measure->due_date->lt($today))
                ->values(),
            'deadlines' => $attentionMeasures
                ->filter(fn (RiskMeasure $measure) => $measure->due_date !== null)
                ->values(),
            'follow_up', 'reviews' => collect(),
            default => $attentionMeasures,
        };
    }

    private function filterReviewQueueByFocus(Collection $reviewQueue, string $focus, CarbonImmutable $today): Collection
    {
        return match ($focus) {
            'urgent' => $reviewQueue
                ->filter(fn (RiskProfileItem $item) => $item->review_due_at !== null && $item->review_due_at->lt($today))
                ->values(),
            'reviews' => $reviewQueue->values(),
            'follow_up', 'deadlines' => collect(),
            default => $reviewQueue,
        };
    }

    private function filterFollowUpQueueByFocus(Collection $followUpQueue, string $focus, CarbonImmutable $today): Collection
    {
        return match ($focus) {
            'urgent' => $followUpQueue
                ->filter(fn (RiskProfileItem $item) => $item->follow_up_due_at !== null && $item->follow_up_due_at->lte($today))
                ->values(),
            'follow_up' => $followUpQueue->values(),
            'reviews', 'deadlines' => collect(),
            default => $followUpQueue,
        };
    }

    private function filterRecentOutcomesByFocus(Collection $recentOutcomes, string $focus): Collection
    {
        return match ($focus) {
            'follow_up' => $recentOutcomes->values(),
            'urgent', 'deadlines', 'reviews' => collect(),
            default => $recentOutcomes,
        };
    }
}
