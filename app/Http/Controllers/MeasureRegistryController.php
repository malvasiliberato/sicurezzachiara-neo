<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\RiskMeasure;
use App\Models\RiskProfileItem;
use App\Models\TenantMembership;
use App\Models\Worker;
use App\Support\CoreStarterPackContextBuilder;
use App\Support\CurrentTenantResolver;
use App\Support\RiskExpectedMeasureResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MeasureRegistryController extends Controller
{
    public function index(
        Request $request,
        CurrentTenantResolver $tenantResolver,
        RiskExpectedMeasureResolver $riskExpectedMeasureResolver,
        CoreStarterPackContextBuilder $coreStarterPackContextBuilder,
    ): Response {
        $tenant = $tenantResolver->resolve($request->user());
        $today = CarbonImmutable::today();
        $family = $request->string('family')->toString() ?: 'all';
        $scope = $request->string('scope')->toString() ?: 'all';
        $companyId = $request->integer('company_id') ?: null;
        $ownerUserId = $request->integer('owner_user_id') ?: null;
        $origin = $request->string('origin')->toString() ?: null;
        $focus = $request->string('focus')->toString() ?: null;

        $allowedFamilies = [
            'all',
            RiskMeasure::FAMILY_TRAINING,
            RiskMeasure::FAMILY_MEDICAL,
            RiskMeasure::FAMILY_DPI,
            'operational',
            'follow_up',
        ];

        $allowedScopes = [
            'all',
            'attention',
            'overdue',
            'follow_up_open',
        ];

        $allowedOrigins = [
            null,
            'dashboard',
            'company_show',
            'worker_show',
            'company_dvr',
            'company_risk_profile',
            'worker_risk_profile',
            'risk_review',
            'risk_measures',
        ];

        $allowedFocuses = [
            null,
            'all',
            'urgent',
            'deadlines',
            'follow_up',
            'reviews',
        ];

        abort_unless(in_array($family, $allowedFamilies, true), 404);
        abort_unless(in_array($scope, $allowedScopes, true), 404);
        abort_unless(in_array($origin, $allowedOrigins, true), 404);
        abort_unless(in_array($focus, $allowedFocuses, true), 404);

        $companyIds = $tenant->companies()->pluck('companies.id');
        $workerIds = $tenant->workers()->select('workers.id')->pluck('workers.id');
        $memberUserIds = TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->pluck('user_id');

        abort_if($companyId !== null && ! $companyIds->contains($companyId), 404);
        abort_if($ownerUserId !== null && ! $memberUserIds->contains($ownerUserId), 404);

        $measuresQuery = RiskMeasure::query()
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
            });

        if ($family === 'operational') {
            $measuresQuery->whereIn('family', [
                RiskMeasure::FAMILY_ORGANIZATIONAL,
                RiskMeasure::FAMILY_TECHNICAL,
            ]);
        } elseif ($family !== 'all' && $family !== 'follow_up') {
            $measuresQuery->where('family', $family);
        }

        $measures = $measuresQuery
            ->orderByRaw(
                'case status when ? then 0 when ? then 1 else 2 end',
                [RiskMeasure::STATUS_NOT_IMPLEMENTED, RiskMeasure::STATUS_TO_VERIFY],
            )
            ->orderBy('due_date')
            ->orderBy('title')
            ->get();

        $riskProfileItems = RiskProfileItem::query()
            ->with(['operationalOwner:id,name', 'sources'])
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
        $riskProfileItemsByKey = $riskProfileItems->keyBy(
            fn (RiskProfileItem $item) => $this->riskLinkKey($item->profileable_type, (int) $item->profileable_id, (int) $item->risk_catalog_item_id),
        );
        $measuresByRiskContext = $measures
            ->groupBy(fn (RiskMeasure $measure) => $this->riskLinkKey(
                $measure->profileable_type,
                (int) $measure->profileable_id,
                (int) $measure->risk_catalog_item_id,
            ))
            ->map(fn ($groupedMeasures) => collect($groupedMeasures)->values());
        $expectedSnapshotsByRiskContext = $riskProfileItemsByKey
            ->map(fn (RiskProfileItem $item, string $key) => $riskExpectedMeasureResolver->snapshotForRisk(
                $item->riskCatalogItem,
                $measuresByRiskContext->get($key, collect()),
            ));
        $measureBindingsById = $expectedSnapshotsByRiskContext
            ->flatMap(fn (array $snapshot) => collect($snapshot['measure_bindings'] ?? []))
            ->keyBy('measure_id');

        $workers = Worker::query()
            ->whereIn('id', $measures->where('profileable_type', Worker::class)->pluck('profileable_id')->unique())
            ->get(['id', 'company_id', 'first_name', 'last_name']);

        $workerLabels = $workers
            ->mapWithKeys(fn (Worker $worker) => [$worker->id => trim("{$worker->first_name} {$worker->last_name}")]);

        $workerCompanyIds = $workers
            ->mapWithKeys(fn (Worker $worker) => [$worker->id => $worker->company_id]);

        $companyLabels = Company::query()
            ->whereIn('id', $measures->where('profileable_type', Company::class)->pluck('profileable_id')
                ->merge($workerCompanyIds->values())
                ->unique()
                ->values())
            ->pluck('name', 'id');

        $measures = $measures->map(function (RiskMeasure $measure) use ($companyLabels, $workerLabels, $workerCompanyIds, $riskProfileItemsByKey, $measureBindingsById) {
            $label = $measure->profileable_type === Worker::class
                ? $workerLabels->get($measure->profileable_id)
                : $companyLabels->get($measure->profileable_id);
            $profileItem = $riskProfileItemsByKey->get(
                $this->riskLinkKey($measure->profileable_type, (int) $measure->profileable_id, (int) $measure->risk_catalog_item_id)
            );
            $expectedBinding = $measureBindingsById->get($measure->id);
            $profileRoute = $measure->profileable_type === Worker::class
                ? route('workers.risk-profile.show', $measure->profileable_id)
                : route('companies.risk-profile.show', $measure->profileable_id);
            $measuresRoute = $profileItem ? $this->measureManageRoute($profileItem) : null;
            $nextStep = $this->buildRegistryMeasureBridge(
                $measure,
                $profileItem,
                $expectedBinding,
                $profileRoute,
                $measuresRoute,
            );

            return [
                ...$measure->toArray(),
                'profile_label' => $label,
                'profile_type_label' => $measure->profileable_type === Worker::class ? 'Lavoratore' : 'Azienda',
                'company_id' => $measure->profileable_type === Worker::class
                    ? $workerCompanyIds->get($measure->profileable_id)
                    : $measure->profileable_id,
                'company_name' => $measure->profileable_type === Worker::class
                    ? $companyLabels->get($workerCompanyIds->get($measure->profileable_id))
                    : $companyLabels->get($measure->profileable_id),
                'risk_name' => $measure->riskCatalogItem?->name,
                'risk_category' => $measure->riskCatalogItem?->category?->name,
                'details_summary' => $this->detailsSummary($measure),
                'has_open_follow_up' => $profileItem?->hasOpenFollowUp() ?? false,
                'operational_owner_user_id' => $profileItem?->operational_owner_user_id,
                'follow_up_status' => $profileItem?->follow_up_status,
                'follow_up_due_at' => $profileItem?->follow_up_due_at?->format('Y-m-d'),
                'follow_up_owner_name' => $profileItem?->operationalOwner?->name,
                'follow_up_notes' => $profileItem?->follow_up_notes,
                'expected_binding' => $expectedBinding,
                'review_route' => $profileItem ? $this->reviewRoute($profileItem) : null,
                'measures_route' => $measuresRoute,
                'profile_route' => $profileRoute,
                'bridge_summary' => $this->registryMeasureBridgeSummary($measure, $profileItem, $expectedBinding),
                'next_step' => $nextStep,
            ];
        })->values();

        $followUpMeasureCount = $measures->where('has_open_follow_up', true)->count();

        if ($companyId !== null) {
            $measures = $measures
                ->where('company_id', $companyId)
                ->values();
        }

        if ($ownerUserId !== null) {
            $measures = $measures
                ->where('operational_owner_user_id', $ownerUserId)
                ->values();
        }

        if ($family === 'follow_up') {
            $measures = $measures
                ->filter(fn (array $measure) => $measure['has_open_follow_up'] === true)
                ->values();
        }

        $contextMeasures = $measures->values();
        $visibleProfileItems = $riskProfileItemsByKey
            ->only(
                $contextMeasures
                    ->map(fn (array $measure) => $this->riskLinkKey(
                        $measure['profileable_type'],
                        (int) $measure['profileable_id'],
                        (int) $measure['risk_catalog_item_id'],
                    ))
                    ->unique()
                    ->values()
                    ->all()
            )
            ->values();
        $coreStarterPack = $coreStarterPackContextBuilder->buildForProfileSources(
            $visibleProfileItems->flatMap->sources->values()
        );
        $contextMeasureBindings = $contextMeasures
            ->pluck('expected_binding')
            ->filter()
            ->values();

        $scopeCounts = [
            'all' => $contextMeasures->count(),
            'attention' => $contextMeasures
                ->filter(fn (array $measure) => in_array($measure['status'], [
                    RiskMeasure::STATUS_NOT_IMPLEMENTED,
                    RiskMeasure::STATUS_TO_VERIFY,
                ], true))
                ->count(),
            'overdue' => $contextMeasures
                ->filter(fn (array $measure) => $measure['due_date'] !== null
                    && $measure['status'] !== RiskMeasure::STATUS_IMPLEMENTED
                    && $measure['due_date'] < $today->format('Y-m-d'))
                ->count(),
            'follow_up_open' => $contextMeasures
                ->filter(fn (array $measure) => $measure['has_open_follow_up'] === true)
                ->count(),
        ];

        $measures = match ($scope) {
            'attention' => $measures
                ->filter(fn (array $measure) => in_array($measure['status'], [
                    RiskMeasure::STATUS_NOT_IMPLEMENTED,
                    RiskMeasure::STATUS_TO_VERIFY,
                ], true))
                ->values(),
            'overdue' => $measures
                ->filter(fn (array $measure) => $measure['due_date'] !== null
                    && $measure['status'] !== RiskMeasure::STATUS_IMPLEMENTED
                    && $measure['due_date'] < $today->format('Y-m-d'))
                ->values(),
            'follow_up_open' => $measures
                ->filter(fn (array $measure) => $measure['has_open_follow_up'] === true)
                ->values(),
            default => $measures,
        };

        $allMeasures = RiskMeasure::query()
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
            ->get(['family', 'status']);

        $companyOptions = $tenant->companies()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Company $company) => [
                'value' => $company->id,
                'label' => $company->name,
            ])
            ->values();

        $ownerOptions = $riskProfileItems
            ->filter(fn (RiskProfileItem $item) => $item->operationalOwner !== null)
            ->map(fn (RiskProfileItem $item) => [
                'value' => $item->operationalOwner->id,
                'label' => $item->operationalOwner->name,
            ])
            ->unique('value')
            ->sortBy('label')
            ->values();

        $focusLabels = [
            'all' => 'Vista completa',
            'urgent' => 'Urgenti',
            'deadlines' => 'Scadenze',
            'follow_up' => 'Follow-up',
            'reviews' => 'Review',
        ];

        $originLabels = [
            'dashboard' => 'Dashboard operativa',
            'company_show' => 'Dettaglio azienda',
            'worker_show' => 'Dettaglio lavoratore',
            'company_dvr' => 'DVR iniziale',
            'company_risk_profile' => 'Profilo rischio azienda',
            'worker_risk_profile' => 'Profilo rischio lavoratore',
            'risk_review' => 'Review rischio',
            'risk_measures' => 'Gestione misure rischio',
        ];

        $workspaceContext = [
            'origin' => $origin,
            'originLabel' => $origin ? ($originLabels[$origin] ?? $origin) : null,
            'focus' => $focus,
            'focusLabel' => $focus ? ($focusLabels[$focus] ?? $focus) : null,
            'companyName' => $companyId ? $companyLabels->get($companyId) : null,
            'ownerName' => $ownerUserId
                ? $ownerOptions->firstWhere('value', $ownerUserId)['label'] ?? null
                : null,
            'backRoute' => $origin === 'dashboard'
                ? route('dashboard', $focus && $focus !== 'all' ? ['focus' => $focus] : [])
                : null,
            'narrative' => collect([
                $origin ? 'Origine: '.($originLabels[$origin] ?? $origin) : null,
                $focus ? 'Focus: '.($focusLabels[$focus] ?? $focus) : null,
                $companyId ? 'Azienda: '.$companyLabels->get($companyId) : null,
                $ownerUserId ? 'Referente: '.($ownerOptions->firstWhere('value', $ownerUserId)['label'] ?? 'N/D') : null,
            ])->filter()->isEmpty()
                ? 'Usa i filtri per aprire una corsia di lavoro piu\' stretta per azienda, referente o stato operativo.'
                : 'Stai leggendo il registro in un contesto operativo gia\' definito. Puoi stringere subito la vista sulle corsie piu\' utili.',
            'activeScopeLabel' => match ($scope) {
                'attention' => 'In attenzione',
                'overdue' => 'Scaduti',
                'follow_up_open' => 'Follow-up aperti',
                default => 'Vista completa',
            },
            'visibleMeasuresCount' => $measures->count(),
            'contextMeasuresCount' => $contextMeasures->count(),
            'shortcuts' => [
                [
                    'label' => 'Tutto il contesto',
                    'helper' => 'Mantiene il perimetro corrente e riapre il workspace completo.',
                    'route' => route('measure-registries.index', array_filter([
                        'family' => $family === 'follow_up' ? null : ($family !== 'all' ? $family : null),
                        'company_id' => $companyId,
                        'owner_user_id' => $ownerUserId,
                        'origin' => $origin,
                        'focus' => $focus,
                    ], fn ($value) => $value !== null && $value !== '')),
                ],
                [
                    'label' => 'Solo scaduti',
                    'helper' => 'Mostra solo le misure oltre data nel contesto corrente.',
                    'route' => route('measure-registries.index', array_filter([
                        'family' => $family === 'follow_up' ? null : ($family !== 'all' ? $family : null),
                        'scope' => 'overdue',
                        'company_id' => $companyId,
                        'owner_user_id' => $ownerUserId,
                        'origin' => $origin,
                        'focus' => $focus,
                    ], fn ($value) => $value !== null && $value !== '')),
                ],
                [
                    'label' => 'Solo follow-up aperti',
                    'helper' => 'Isola i presidi legati a rischi ancora in carico operativo.',
                    'route' => route('measure-registries.index', array_filter([
                        'family' => 'follow_up',
                        'scope' => 'follow_up_open',
                        'company_id' => $companyId,
                        'owner_user_id' => $ownerUserId,
                        'origin' => $origin,
                        'focus' => $focus,
                    ], fn ($value) => $value !== null && $value !== '')),
                ],
            ],
        ];

        $contextBridge = null;

        if ($companyId !== null) {
            $companyWorkerIdsForBridge = Worker::query()
                ->where('company_id', $companyId)
                ->pluck('id');

            $companyProfileItems = $riskProfileItems
                ->filter(function (RiskProfileItem $item) use ($companyId, $companyWorkerIdsForBridge) {
                    if ($item->profileable_type === Company::class && (int) $item->profileable_id === (int) $companyId) {
                        return true;
                    }

                    return $item->profileable_type === Worker::class
                        && $companyWorkerIdsForBridge->contains((int) $item->profileable_id);
                })
                ->values();

            $visibleCompanyMeasures = $measures->values();
            $overdueVisibleMeasures = $visibleCompanyMeasures
                ->filter(fn (array $measure) => $measure['due_date'] !== null
                    && $measure['status'] !== RiskMeasure::STATUS_IMPLEMENTED
                    && $measure['due_date'] < $today->format('Y-m-d'))
                ->count();
            $reviewsDue = $companyProfileItems
                ->filter(fn (RiskProfileItem $item) => $item->isOperationallyActive() && $item->isReviewDue($today))
                ->count();
            $followUpsOpen = $companyProfileItems
                ->filter(fn (RiskProfileItem $item) => $item->isOperationallyActive() && $item->hasOpenFollowUp())
                ->count();
            $uncoveredRisks = $companyProfileItems
                ->filter(fn (RiskProfileItem $item) => $item->isOperationallyActive() && $item->status === RiskProfileItem::STATUS_UNCOVERED)
                ->count();

            $suggestedFocus = match (true) {
                $scope === 'overdue' || $overdueVisibleMeasures > 0 => 'deadlines',
                $scope === 'follow_up_open' || $followUpsOpen > 0 => 'follow_up',
                $focus === 'reviews' || $reviewsDue > 0 => 'reviews',
                default => 'all',
            };

            $contextBridge = [
                'companyName' => $companyLabels->get($companyId),
                'suggestedAction' => match ($suggestedFocus) {
                    'deadlines' => [
                        'label' => 'Chiudi scaduti dal registro',
                        'helper' => $overdueVisibleMeasures.' misure oltre data sono gia\' visibili nel contesto corrente.',
                    ],
                    'follow_up' => [
                        'label' => 'Segui follow-up aperti',
                        'helper' => $followUpsOpen.' criticita\' restano in carico operativo sul contesto aziendale.',
                    ],
                    'reviews' => [
                        'label' => 'Riallinea review nel profilo',
                        'helper' => $reviewsDue.' review consulente richiedono un riallineamento sul profilo aziendale.',
                    ],
                    default => [
                        'label' => 'Verifica copertura nel profilo',
                        'helper' => $uncoveredRisks.' rischi scoperti restano da presidiare nel contesto aziendale.',
                    ],
                },
                'stats' => [
                    'visibleMeasures' => $visibleCompanyMeasures->count(),
                    'overdueMeasures' => $overdueVisibleMeasures,
                    'followUpsOpen' => $followUpsOpen,
                    'uncoveredRisks' => $uncoveredRisks,
                ],
                'actions' => [
                    'companyRoute' => route('companies.show', $companyId),
                    'riskProfileRoute' => route('companies.risk-profile.show', [
                        'company' => $companyId,
                        'origin' => 'measure_registry',
                        'focus' => $suggestedFocus,
                    ]),
                    'dashboardRoute' => $workspaceContext['backRoute'],
                ],
            ];
        }

        return Inertia::render('sicurezzachiara/measure-registries/Index', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'activeFamily' => $family,
            'activeScope' => $scope,
            'activeCompanyId' => $companyId,
            'activeOwnerUserId' => $ownerUserId,
            'copy' => [
                'workspaceTitle' => 'Registri famiglia misure',
                'workspaceHelper' => 'Workspace unico delle misure collegate ai rischi. Le tab DPI, formazione e visite mediche filtrano la stessa base operativa per famiglia.',
                'familyColumnLabel' => 'Famiglia',
            ],
            'workspaceContext' => $workspaceContext,
            'contextBridge' => $contextBridge,
            'coreStarterPack' => $coreStarterPack,
            'measures' => $measures,
            'filters' => [
                'companies' => $companyOptions,
                'owners' => $ownerOptions,
                'scopes' => [
                    ['value' => 'all', 'label' => 'Tutti i record', 'count' => $scopeCounts['all']],
                    ['value' => 'attention', 'label' => 'Solo in attenzione', 'count' => $scopeCounts['attention']],
                    ['value' => 'overdue', 'label' => 'Solo scaduti', 'count' => $scopeCounts['overdue']],
                    ['value' => 'follow_up_open', 'label' => 'Solo con follow-up aperto', 'count' => $scopeCounts['follow_up_open']],
                ],
            ],
            'summary' => [
                'totalMeasures' => $allMeasures->count(),
                'implementedMeasures' => $allMeasures->where('status', RiskMeasure::STATUS_IMPLEMENTED)->count(),
                'toVerifyMeasures' => $allMeasures->where('status', RiskMeasure::STATUS_TO_VERIFY)->count(),
                'trainingMeasures' => $allMeasures->where('family', RiskMeasure::FAMILY_TRAINING)->count(),
                'medicalMeasures' => $allMeasures->where('family', RiskMeasure::FAMILY_MEDICAL)->count(),
                'dpiMeasures' => $allMeasures->where('family', RiskMeasure::FAMILY_DPI)->count(),
                'operationalMeasures' => $allMeasures->whereIn('family', [
                    RiskMeasure::FAMILY_ORGANIZATIONAL,
                    RiskMeasure::FAMILY_TECHNICAL,
                ])->count(),
                'followUpMeasures' => $followUpMeasureCount,
                'visibleMeasures' => $measures->count(),
                'contextMeasures' => $contextMeasures->count(),
                'directMeasures' => $contextMeasureBindings->where('binding', 'direct_expected')->count(),
                'substitutedMeasures' => $contextMeasureBindings->where('binding', 'family_substitution')->count(),
                'freeMeasures' => $contextMeasureBindings->where('binding', 'free_measure')->count(),
                'unstructuredMeasures' => $contextMeasureBindings->where('binding', 'unstructured')->count(),
                'expectedGapRisks' => $expectedSnapshotsByRiskContext
                    ->filter(fn (array $snapshot) => (($snapshot['summary']['missing_count'] ?? 0) + ($snapshot['summary']['partial_count'] ?? 0)) > 0)
                    ->count(),
            ],
        ]);
    }

    private function reviewRoute(RiskProfileItem $profileItem): string
    {
        return $profileItem->profileable_type === Worker::class
            ? route('workers.risk-profile.review.show', [$profileItem->profileable_id, $profileItem->id])
            : route('companies.risk-profile.review.show', [$profileItem->profileable_id, $profileItem->id]);
    }

    private function measureManageRoute(RiskProfileItem $profileItem): string
    {
        return $profileItem->profileable_type === Worker::class
            ? route('workers.risk-profile.measures.show', [$profileItem->profileable_id, $profileItem->id])
            : route('companies.risk-profile.measures.show', [$profileItem->profileable_id, $profileItem->id]);
    }

    private function riskLinkKey(string $profileableType, int $profileableId, int $riskCatalogItemId): string
    {
        return implode(':', [$profileableType, $profileableId, $riskCatalogItemId]);
    }

    private function detailsSummary(RiskMeasure $measure): ?string
    {
        $details = collect($measure->details ?? []);

        return match ($measure->family) {
            RiskMeasure::FAMILY_TRAINING => collect([
                $details->get('provider'),
                $details->get('delivery_mode'),
                $details->get('valid_until') ? 'valida fino al '.$details->get('valid_until') : null,
            ])->filter()->implode(' | '),
            RiskMeasure::FAMILY_MEDICAL => collect([
                $details->get('physician'),
                $details->get('protocol'),
                $details->get('valid_until') ? 'scadenza '.$details->get('valid_until') : null,
            ])->filter()->implode(' | '),
            RiskMeasure::FAMILY_DPI => collect([
                $details->get('item_name'),
                $details->get('category'),
                $details->get('valid_until') ? 'sostituzione '.$details->get('valid_until') : null,
            ])->filter()->implode(' | '),
            default => collect([
                $details->get('owner'),
                $details->get('verification_method'),
            ])->filter()->implode(' | '),
        } ?: null;
    }

    private function registryMeasureBridgeSummary(
        RiskMeasure $measure,
        ?RiskProfileItem $profileItem,
        ?array $expectedBinding,
    ): string {
        $parts = collect([
            $expectedBinding['label'] ?? null,
            $measure->status === RiskMeasure::STATUS_NOT_IMPLEMENTED
                ? 'presidio ancora da attuare'
                : ($measure->status === RiskMeasure::STATUS_TO_VERIFY ? 'presidio in verifica' : 'presidio registrato'),
            $profileItem?->hasOpenFollowUp()
                ? 'follow-up operativo aperto'
                : null,
        ])->filter()->values();

        return $parts->isNotEmpty()
            ? $parts->implode(' | ')
            : 'Apri il contesto rischio per rileggere il ruolo operativo della misura.';
    }

    private function buildRegistryMeasureBridge(
        RiskMeasure $measure,
        ?RiskProfileItem $profileItem,
        ?array $expectedBinding,
        string $profileRoute,
        ?string $measuresRoute,
    ): array {
        if ($profileItem === null) {
            return [
                'label' => 'Apri profilo',
                'helper' => 'Il presidio e\' gia\' registrato ma non ha ancora un bridge operativo completo.',
                'route' => $profileRoute,
            ];
        }

        $binding = $expectedBinding['binding'] ?? null;

        return match (true) {
            $measure->status === RiskMeasure::STATUS_NOT_IMPLEMENTED => [
                'label' => 'Completa presidio in misure',
                'helper' => $binding === 'direct_expected' || $binding === 'family_substitution'
                    ? 'Il rischio attende ancora questo presidio: conviene chiuderlo nella gestione misure.'
                    : 'La misura e\' ancora non attuata e va consolidata nel perimetro del rischio.',
                'route' => $measuresRoute ?? $profileRoute,
            ],
            $profileItem->hasOpenFollowUp() => [
                'label' => 'Segui follow-up in review',
                'helper' => 'Il rischio e\' ancora in carico operativo: conviene chiudere il follow-up prima di archiviare il presidio.',
                'route' => $this->reviewRoute($profileItem),
            ],
            $measure->status === RiskMeasure::STATUS_TO_VERIFY => [
                'label' => 'Verifica presidio in review',
                'helper' => 'La misura e\' presente ma richiede ancora una validazione consulente sul rischio collegato.',
                'route' => $this->reviewRoute($profileItem),
            ],
            default => [
                'label' => 'Rileggi contesto rischio',
                'helper' => 'Il presidio e\' gia\' registrato: usa profilo e review per verificarne copertura e significato operativo.',
                'route' => $this->reviewRoute($profileItem),
            ],
        };
    }
}
