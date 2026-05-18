<?php

namespace App\Support;

use App\Models\Company;
use App\Models\RiskMeasure;
use App\Models\RiskProfileItem;
use App\Models\Tenant;
use App\Models\Worker;

class CompanyDvrReportBuilder
{
    public function __construct(
        private readonly RiskEngineSnapshotBuilder $riskEngineSnapshotBuilder,
    ) {}

    public function build(Tenant $tenant, Company $company): array
    {
        $company->load([
            'sites' => fn ($query) => $query->orderByDesc('is_headquarters')->orderBy('name'),
            'workers' => fn ($query) => $query
                ->with([
                    'primarySite:id,name',
                    'jobRoleAssignments' => fn ($assignmentQuery) => $assignmentQuery
                        ->where('is_primary', true)
                        ->with([
                            'jobRole' => fn ($jobRoleQuery) => $jobRoleQuery
                                ->with(['riskSourceLinks.riskCatalogItem.category']),
                        ]),
                    'equipmentExposures.equipmentAsset.equipmentType:id,name',
                    'workplaceExposures.workplace.workplaceType:id,name',
                ])
                ->orderBy('last_name')
                ->orderBy('first_name'),
            'equipmentAssets' => fn ($query) => $query
                ->with([
                    'site:id,name',
                    'equipmentType' => fn ($equipmentTypeQuery) => $equipmentTypeQuery
                        ->with(['riskSourceLinks.riskCatalogItem.category']),
                ])
                ->orderBy('name'),
            'sites.workplaces' => fn ($query) => $query
                ->with([
                    'workplaceType' => fn ($workplaceTypeQuery) => $workplaceTypeQuery
                        ->with(['riskSourceLinks.riskCatalogItem.category']),
                ])
                ->orderBy('name'),
        ]);

        $workerIds = $company->workers->pluck('id');

        $profileItems = RiskProfileItem::query()
            ->with([
                'riskCatalogItem.category',
                'sources',
                'operationalOwner:id,name',
            ])
            ->where(function ($query) use ($company, $workerIds) {
                $query
                    ->where(function ($companyQuery) use ($company) {
                        $companyQuery->where('profileable_type', Company::class)
                            ->where('profileable_id', $company->id);
                    })
                    ->orWhere(function ($workerQuery) use ($workerIds) {
                        $workerQuery->where('profileable_type', Worker::class)
                            ->whereIn('profileable_id', $workerIds);
                    });
            })
            ->orderByDesc('priority')
            ->get();

        $measures = RiskMeasure::query()
            ->with('riskCatalogItem.category')
            ->where(function ($query) use ($company, $workerIds) {
                $query
                    ->where(function ($companyQuery) use ($company) {
                        $companyQuery->where('profileable_type', Company::class)
                            ->where('profileable_id', $company->id);
                    })
                    ->orWhere(function ($workerQuery) use ($workerIds) {
                        $workerQuery->where('profileable_type', Worker::class)
                            ->whereIn('profileable_id', $workerIds);
                    });
            })
            ->orderBy('due_date')
            ->orderBy('title')
            ->get();

        $workerLabels = $company->workers->mapWithKeys(fn (Worker $worker) => [$worker->id => $worker->full_name]);

        $sourceSummary = [
            'job_roles' => $company->workers
                ->flatMap(fn (Worker $worker) => $worker->jobRoleAssignments->map(fn ($assignment) => $assignment->jobRole?->name))
                ->filter()
                ->unique()
                ->values(),
            'equipment_assets' => $company->equipmentAssets
                ->map(fn ($asset) => $asset->name)
                ->filter()
                ->unique()
                ->values(),
            'workplaces' => $company->sites
                ->flatMap(fn ($site) => $site->workplaces->map(fn ($workplace) => $workplace->name))
                ->filter()
                ->unique()
                ->values(),
        ];
        $workersWithoutPrimarySite = $company->workers->filter(fn (Worker $worker) => $worker->primarySite === null)->count();
        $workersWithoutPrimaryJobRole = $company->workers->filter(
            fn (Worker $worker) => $worker->jobRoleAssignments->first()?->jobRole === null
        )->count();
        $sourceCount = $sourceSummary['job_roles']->count()
            + $sourceSummary['equipment_assets']->count()
            + $sourceSummary['workplaces']->count();

        $riskEntries = $profileItems->map(function (RiskProfileItem $item) use ($company, $workerLabels, $measures) {
            $relatedMeasures = $measures
                ->filter(fn (RiskMeasure $measure) => (int) $measure->risk_catalog_item_id === (int) $item->risk_catalog_item_id)
                ->filter(function (RiskMeasure $measure) use ($item, $company) {
                    if ($item->profileable_type === Company::class) {
                        return $measure->profileable_type === Company::class && (int) $measure->profileable_id === (int) $company->id;
                    }

                    return $measure->profileable_type === Worker::class && (int) $measure->profileable_id === (int) $item->profileable_id;
                })
                ->values();
            $expectedMeasures = $this->riskEngineSnapshotBuilder
                ->buildForProfileItem($item, $relatedMeasures)['expectedMeasures'];

            return [
                'id' => $item->id,
                'scope' => $item->profileable_type === Company::class ? 'Azienda' : 'Lavoratore',
                'scope_label' => $item->profileable_type === Company::class
                    ? $company->name
                    : ($workerLabels->get($item->profileable_id) ?? 'Lavoratore'),
                'risk_name' => $item->riskCatalogItem?->name,
                'risk_category' => $item->riskCatalogItem?->category?->name,
                'priority' => $this->priorityLabel($item->effectivePriority()),
                'status' => $item->operational_status === RiskProfileItem::OPERATIONAL_STATUS_EXCLUDED
                    ? 'Escluso'
                    : ($item->status === RiskProfileItem::STATUS_COVERED ? 'Coperto' : 'Da presidiare'),
                'consultant_decision' => $item->consultant_decision,
                'consultant_notes' => $item->consultant_notes,
                'review_due_at' => $item->review_due_at?->format('Y-m-d'),
                'reviewed_at' => $item->reviewed_at?->format('Y-m-d H:i'),
                'operational_owner_name' => $item->operationalOwner?->name,
                'follow_up_status' => $item->follow_up_status,
                'follow_up_notes' => $item->follow_up_notes,
                'follow_up_due_at' => $item->follow_up_due_at?->format('Y-m-d'),
                'follow_up_outcome_status' => $item->follow_up_outcome_status,
                'follow_up_outcome_notes' => $item->follow_up_outcome_notes,
                'follow_up_outcome_recorded_at' => $item->follow_up_outcome_recorded_at?->format('Y-m-d H:i'),
                'sources' => $item->sources->pluck('source_label')->filter()->values(),
                'expected_measures' => $expectedMeasures,
                'measures' => $relatedMeasures->map(fn (RiskMeasure $measure) => [
                    'title' => $measure->title,
                    'status' => $this->measureStatusLabel($measure->status),
                    'expected_measure_code' => $measure->expected_measure_code,
                    'due_date' => $measure->due_date?->format('Y-m-d'),
                ])->values(),
            ];
        })->values();

        $measureEntries = $measures->map(function (RiskMeasure $measure) use ($company, $workerLabels) {
            return [
                'id' => $measure->id,
                'title' => $measure->title,
                'family' => $this->familyLabel($measure->family),
                'status' => $this->measureStatusLabel($measure->status),
                'due_date' => $measure->due_date?->format('Y-m-d') ?? 'Non definita',
                'context' => $measure->profileable_type === Company::class
                    ? $company->name
                    : ($workerLabels->get($measure->profileable_id) ?? 'Lavoratore'),
                'risk_name' => $measure->riskCatalogItem?->name,
            ];
        })->values();

        $operationalItems = $profileItems->filter(fn (RiskProfileItem $item) => $item->isOperationallyActive());
        $measureGroups = $measures->groupBy(fn (RiskMeasure $measure) => implode(':', [
            $measure->profileable_type,
            (int) $measure->profileable_id,
            (int) $measure->risk_catalog_item_id,
        ]));

        $timelineEntries = $profileItems
            ->flatMap(function (RiskProfileItem $item) use ($measureGroups) {
                return app(RiskOperationalTimelineBuilder::class)->buildForProfileItem(
                    $item,
                    $measureGroups->get(implode(':', [
                        $item->profileable_type,
                        (int) $item->profileable_id,
                        (int) $item->risk_catalog_item_id,
                    ]), collect()),
                    6,
                )->map(fn (array $event) => [
                    ...$event,
                    'scope_label' => $item->profileable_type === Company::class
                        ? 'Azienda'
                        : 'Lavoratore',
                ]);
            })
            ->sortByDesc('occurred_at')
            ->take(10)
            ->values();

        $engine = $this->riskEngineSnapshotBuilder->buildFromCollections($profileItems, $measures);
        $coverageSignals = [
            [
                'label' => 'Agganci diretti',
                'value' => $engine['summary']['directExpectedMeasures'],
                'helper' => 'Presidi attesi coperti con misure direttamente agganciate al rischio.',
            ],
            [
                'label' => 'Coperture equivalenti',
                'value' => $engine['summary']['substitutedExpectedMeasures'],
                'helper' => 'Presidi attesi coperti tramite equivalenza della stessa famiglia.',
            ],
            [
                'label' => 'Misure libere',
                'value' => $engine['summary']['freeMeasures'],
                'helper' => 'Misure presenti ma non legate a un presidio atteso esplicito.',
            ],
            [
                'label' => 'Gap attesi',
                'value' => $engine['summary']['missingExpectedMeasures'],
                'helper' => 'Presidi attesi ancora mancanti o solo parzialmente coperti.',
            ],
        ];
        $dvrBridge = $this->buildDvrBridge($company, $engine['summary']);
        $documentScope = $this->buildDocumentScope(
            $sourceCount,
            $workersWithoutPrimarySite,
            $workersWithoutPrimaryJobRole,
            $engine['summary'],
        );

        return [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'company' => [
                ...$company->only([
                    'id',
                    'name',
                    'legal_name',
                    'industry',
                    'contact_email',
                    'contact_phone',
                    'city',
                    'province',
                    'notes',
                ]),
                'sites' => $company->sites->map->only(['id', 'name', 'site_code', 'city', 'province', 'is_headquarters'])->values(),
                'workers' => $company->workers->map(fn (Worker $worker) => [
                    'id' => $worker->id,
                    'full_name' => $worker->full_name,
                    'status' => $worker->status,
                    'primary_site' => $worker->primarySite?->name,
                    'job_role' => $worker->jobRoleAssignments->first()?->jobRole?->name,
                ])->values(),
            ],
            'summary' => [
                ...$engine['summary'],
                'sitesCount' => $company->sites->count(),
                'workersCount' => $company->workers->count(),
                'workersWithoutPrimarySite' => $workersWithoutPrimarySite,
                'workersWithoutPrimaryJobRole' => $workersWithoutPrimaryJobRole,
                'sourceCount' => $sourceCount,
                'followUpsClosed' => $profileItems->filter(fn (RiskProfileItem $item) => $item->follow_up_status === RiskProfileItem::FOLLOW_UP_STATUS_CLOSED)->count(),
                'overdueMeasures' => $measures->filter(fn (RiskMeasure $measure) => $measure->due_date !== null
                    && $measure->status !== RiskMeasure::STATUS_IMPLEMENTED
                    && $measure->due_date->isPast())->count(),
            ],
            'engine' => collect($engine)->except('risks')->all(),
            'sourceSummary' => [
                'job_roles' => $sourceSummary['job_roles'],
                'equipment_assets' => $sourceSummary['equipment_assets'],
                'workplaces' => $sourceSummary['workplaces'],
            ],
            'coreStarterPack' => $engine['coreStarterPack'],
            'coverageSignals' => $coverageSignals,
            'contextBridge' => $dvrBridge,
            'documentScope' => $documentScope,
            'riskEntries' => $riskEntries,
            'measureEntries' => $measureEntries,
            'timelineEntries' => $timelineEntries,
        ];
    }

    private function buildDocumentScope(
        int $sourceCount,
        int $workersWithoutPrimarySite,
        int $workersWithoutPrimaryJobRole,
        array $summary,
    ): array {
        $items = collect([
            [
                'label' => 'Sorgenti lette dal DVR',
                'value' => $sourceCount,
                'helper' => 'Mansioni, macchinari e luoghi gia\' censiti e quindi leggibili nel documento.',
                'tone' => $sourceCount > 0 ? 'success' : 'warning',
            ],
            [
                'label' => 'Lavoratori senza sede prevalente',
                'value' => $workersWithoutPrimarySite,
                'helper' => $workersWithoutPrimarySite > 0
                    ? 'Serve completare il contesto prima di considerare piena la lettura del DVR light.'
                    : 'Tutti i lavoratori censiti hanno gia\' una sede prevalente.',
                'tone' => $workersWithoutPrimarySite > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Lavoratori senza mansione prevalente',
                'value' => $workersWithoutPrimaryJobRole,
                'helper' => $workersWithoutPrimaryJobRole > 0
                    ? 'La mansione resta una sorgente chiave: senza assegnazione il DVR resta incompleto.'
                    : 'Ogni lavoratore censito ha gia\' una mansione prevalente.',
                'tone' => $workersWithoutPrimaryJobRole > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Gap attesi ancora aperti',
                'value' => (int) ($summary['missingExpectedMeasures'] ?? 0),
                'helper' => ((int) ($summary['missingExpectedMeasures'] ?? 0)) > 0
                    ? 'I presidi attesi ancora mancanti tengono il DVR light in uno stato da completare.'
                    : 'Non emergono gap attesi aperti nella lettura corrente del documento.',
                'tone' => ((int) ($summary['missingExpectedMeasures'] ?? 0)) > 0 ? 'warning' : 'success',
            ],
        ])->values();

        $needsAttention = $items->contains(fn (array $item) => $item['tone'] === 'warning');

        return [
            'title' => 'Perimetro letto dal DVR light',
            'helper' => 'Questa pagina resta una lettura consultabile del dominio corrente: mostra cosa il sistema ha gia\' censito e quali punti limitano ancora una lettura piena del documento.',
            'status' => $needsAttention ? 'Da completare' : 'Coerente col contesto attuale',
            'statusTone' => $needsAttention ? 'warning' : 'success',
            'items' => $items,
        ];
    }

    private function buildDvrBridge(Company $company, array $summary): array
    {
        $focus = match (true) {
            ($summary['followUpsOpen'] ?? 0) > 0 => 'follow_up',
            ($summary['reviewsDue'] ?? 0) > 0 => 'reviews',
            ($summary['missingExpectedMeasures'] ?? 0) > 0 => 'deadlines',
            default => 'all',
        };

        return [
            'focus' => $focus,
            'focusLabel' => match ($focus) {
                'follow_up' => 'Follow-up',
                'reviews' => 'Review',
                'deadlines' => 'Presidi e scadenze',
                default => 'Copertura',
            },
            'suggestedAction' => match ($focus) {
                'follow_up' => [
                    'label' => 'Segui i follow-up aperti',
                    'helper' => ($summary['followUpsOpen'] ?? 0).' rischi restano in carico operativo e chiedono continuita\' tra review e registri.',
                ],
                'reviews' => [
                    'label' => 'Riallinea le review dovute',
                    'helper' => ($summary['reviewsDue'] ?? 0).' review risultano in agenda e incidono sullo stato operativo del DVR.',
                ],
                'deadlines' => [
                    'label' => 'Chiudi presidi e scadenze aperte',
                    'helper' => ($summary['missingExpectedMeasures'] ?? 0).' gap attesi e '.($summary['overdueMeasures'] ?? 0).' misure scadute tengono il documento in pressione.',
                ],
                default => [
                    'label' => 'Rileggi il profilo aziendale',
                    'helper' => 'Il DVR e\' coerente col dominio corrente: puoi tornare al profilo per rileggere copertura, review e presidi.',
                ],
            },
            'stats' => [
                'activeRisks' => (int) ($summary['activeRisks'] ?? 0),
                'reviewsDue' => (int) ($summary['reviewsDue'] ?? 0),
                'followUpsOpen' => (int) ($summary['followUpsOpen'] ?? 0),
                'overdueMeasures' => (int) ($summary['overdueMeasures'] ?? 0),
            ],
            'actions' => [
                'riskProfileRoute' => route('companies.risk-profile.show', [
                    'company' => $company,
                    'origin' => 'company_dvr',
                    'focus' => $focus,
                ]),
                'registryRoute' => route('measure-registries.index', array_filter([
                    'company_id' => $company->id,
                    'origin' => 'company_dvr',
                    'focus' => $focus,
                    'scope' => $focus === 'follow_up' ? 'follow_up_open' : ($focus === 'deadlines' ? 'overdue' : 'attention'),
                    'family' => $focus === 'follow_up' ? 'follow_up' : null,
                ], fn ($value) => $value !== null && $value !== '')),
                'companyRoute' => route('companies.show', $company),
                'dashboardRoute' => route('dashboard', $focus !== 'all' ? ['focus' => $focus] : []),
            ],
        ];
    }

    private function priorityLabel(?string $priority): string
    {
        return match ($priority) {
            'high' => 'Alta',
            'medium' => 'Media',
            default => 'Bassa',
        };
    }

    private function measureStatusLabel(string $status): string
    {
        return match ($status) {
            RiskMeasure::STATUS_IMPLEMENTED => 'Attuata',
            RiskMeasure::STATUS_TO_VERIFY => 'Da verificare',
            default => 'Non attuata',
        };
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
}
