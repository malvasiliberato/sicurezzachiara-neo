<?php

namespace App\Support;

use App\Models\Company;
use App\Models\RiskMeasure;
use App\Models\RiskProfileItem;
use App\Models\Worker;
use Illuminate\Support\Collection;

class RiskEngineSnapshotBuilder
{
    public function __construct(
        private readonly RiskExpectedMeasureResolver $riskExpectedMeasureResolver,
        private readonly CoreStarterPackContextBuilder $coreStarterPackContextBuilder,
    ) {}

    public function buildForProfileable(Company|Worker $profileable): array
    {
        $profileable->loadMissing([
            'riskProfileItems.riskCatalogItem.category',
            'riskProfileItems.sources',
            'riskProfileItems.operationalOwner:id,name',
            'riskMeasures.riskCatalogItem.category',
        ]);

        $profileItems = collect($profileable->riskProfileItems);
        $measures = collect($profileable->riskMeasures);

        return $this->buildFromCollections($profileItems, $measures);
    }

    public function buildFromCollections(Collection $profileItems, Collection $measures): array
    {
        $coreStarterPack = $this->coreStarterPackContextBuilder->buildForProfileSources(
            $profileItems->flatMap(fn (RiskProfileItem $item) => $item->sources)->values()
        );
        $summary = $this->buildSummary($profileItems, $measures, $coreStarterPack['summary']);
        $sourceFamilies = $this->buildSourceFamilies($profileItems);
        $sourceInputs = $this->buildSourceInputs($profileItems);
        $risks = $this->buildRiskEntries($profileItems, $measures)->values();

        return [
            'summary' => $summary,
            'flow' => $this->buildFlow($summary, $coreStarterPack['summary']),
            'sourceFamilies' => $sourceFamilies->values()->all(),
            'sourceInputs' => $sourceInputs->values()->all(),
            'coreStarterPack' => $coreStarterPack,
            'risks' => $risks->all(),
        ];
    }

    public function buildForProfileItem(RiskProfileItem $profileItem, Collection $measures): array
    {
        $profileItem->loadMissing(['riskCatalogItem.category', 'sources', 'operationalOwner:id,name']);

        $measureSummary = $this->buildMeasureSummary($measures);
        $expectedMeasures = $this->riskExpectedMeasureResolver->snapshotForRisk($profileItem->riskCatalogItem, $measures);
        $sourceState = $this->sourceStateForRisk($profileItem);
        $finalState = $this->finalStateForRisk($profileItem);
        $coverage = $this->coverageForRisk($profileItem, $measureSummary, $expectedMeasures['summary']);

        return [
            'sourceState' => $sourceState,
            'finalState' => $finalState,
            'coverage' => $coverage,
            'expectedMeasures' => $expectedMeasures,
            'measureBindings' => $expectedMeasures['measure_bindings'],
            'flow' => [
                [
                    'label' => 'Sorgenti attive',
                    'value' => (string) $profileItem->sources->count(),
                    'helper' => $sourceState['helper'],
                    'tone' => $sourceState['tone'],
                ],
                [
                    'label' => 'Rischio finale',
                    'value' => $finalState['label'],
                    'helper' => $finalState['helper'],
                    'tone' => $finalState['tone'],
                ],
                [
                    'label' => 'Misure collegate',
                    'value' => (string) $measureSummary['count'],
                    'helper' => $expectedMeasures['summary']['expected_count'] > 0
                        ? $expectedMeasures['summary']['covered_count'].' presidi attesi coperti'
                            .($expectedMeasures['summary']['substituted_count'] > 0 ? ' | '.$expectedMeasures['summary']['substituted_count'].' per equivalenza' : '')
                            .' | '.($expectedMeasures['summary']['missing_count'] + $expectedMeasures['summary']['partial_count']).' gap'
                        : ($measureSummary['count'] > 0
                            ? $measureSummary['implemented'].' attuate | '.$measureSummary['pending'].' da presidiare'
                            : 'Nessun presidio ancora collegato'),
                    'tone' => $measureSummary['count'] > 0 ? 'primary' : 'secondary',
                ],
                [
                    'label' => 'Copertura',
                    'value' => $coverage['label'],
                    'helper' => $coverage['helper'],
                    'tone' => $coverage['tone'],
                ],
            ],
        ];
    }

    private function buildSummary(Collection $profileItems, Collection $measures, array $coreSummary): array
    {
        $activeItems = $profileItems->filter(fn (RiskProfileItem $item) => $item->isOperationallyActive());
        $implementedMeasures = $measures->where('status', RiskMeasure::STATUS_IMPLEMENTED)->count();
        $pendingMeasures = $measures->count() - $implementedMeasures;
        $sourceInputs = $this->buildSourceInputs($profileItems);
        $sourceFamilies = $this->buildSourceFamilies($profileItems);
        $measuresByRiskContext = $this->groupMeasuresByRiskContext($measures);
        $expectedCoverage = $profileItems
            ->map(function (RiskProfileItem $item) use ($measuresByRiskContext) {
                return $this->riskExpectedMeasureResolver
                    ->snapshotForRisk(
                        $item->riskCatalogItem,
                        $measuresByRiskContext->get($this->riskContextKeyForProfileItem($item), collect()),
                    )['summary'];
            });
        $coverageRate = $activeItems->count() > 0
            ? (int) round(($activeItems->where('status', RiskProfileItem::STATUS_COVERED)->count() / $activeItems->count()) * 100)
            : 0;

        return [
            'sourceInputs' => $sourceInputs->count(),
            'sourceFamilies' => $sourceFamilies->count(),
            'coreSourceInputs' => $coreSummary['coreSourceCount'],
            'tenantSourceInputs' => $coreSummary['tenantSourceCount'],
            'suggestedCoreRisks' => $coreSummary['suggestedRisksCount'],
            'expectedCoreMeasures' => $coreSummary['expectedMeasuresCount'],
            'derivedRisks' => $profileItems->where('is_currently_derived', true)->count(),
            'manualRisks' => $profileItems->where('is_manual', true)->count(),
            'consultantAdjustedRisks' => $profileItems->filter(
                fn (RiskProfileItem $item) => $item->consultant_decision !== null || $item->is_manual || $item->final_priority !== null
            )->count(),
            'activeRisks' => $activeItems->count(),
            'totalRisks' => $activeItems->count(),
            'excludedRisks' => $profileItems->where('operational_status', RiskProfileItem::OPERATIONAL_STATUS_EXCLUDED)->count(),
            'uncoveredRisks' => $activeItems->where('status', RiskProfileItem::STATUS_UNCOVERED)->count(),
            'coveredRisks' => $activeItems->where('status', RiskProfileItem::STATUS_COVERED)->count(),
            'reviewsDue' => $activeItems->filter(fn (RiskProfileItem $item) => $item->isReviewDue())->count(),
            'followUpsOpen' => $activeItems->filter(fn (RiskProfileItem $item) => $item->hasOpenFollowUp())->count(),
            'highPriorityRisks' => $activeItems->filter(
                fn (RiskProfileItem $item) => $item->effectivePriority() === 'high'
            )->count(),
            'totalMeasures' => $measures->count(),
            'measuresCount' => $measures->count(),
            'implementedMeasures' => $implementedMeasures,
            'pendingMeasures' => $pendingMeasures,
            'expectedMeasures' => $expectedCoverage->sum('expected_count'),
            'coveredExpectedMeasures' => $expectedCoverage->sum('covered_count'),
            'directExpectedMeasures' => $expectedCoverage->sum('direct_measure_count'),
            'substitutedExpectedMeasures' => $expectedCoverage->sum('substituted_count'),
            'freeMeasures' => $expectedCoverage->sum('free_measures_count'),
            'missingExpectedMeasures' => $expectedCoverage->sum(
                fn (array $summary) => $this->riskExpectedMeasureResolver->expectedGapCount($summary),
            ),
            'risksWithExpectedGaps' => $activeItems
                ->filter(function (RiskProfileItem $item) use ($measuresByRiskContext) {
                    $summary = $this->riskExpectedMeasureResolver
                        ->snapshotForRisk(
                            $item->riskCatalogItem,
                            $measuresByRiskContext->get($this->riskContextKeyForProfileItem($item), collect()),
                        )['summary'];

                    return $this->riskExpectedMeasureResolver->expectedGapCount($summary) > 0;
                })
                ->count(),
            'coverageRate' => $coverageRate,
        ];
    }

    private function buildFlow(array $summary, array $coreSummary): array
    {
        return [
            [
                'key' => 'sources',
                'label' => 'Sorgenti attive',
                'value' => (string) $summary['sourceInputs'],
                'helper' => $summary['sourceFamilies'].' famiglie presenti tra mansioni, macchinari e luoghi',
                'tone' => 'info',
            ],
            [
                'key' => 'core',
                'label' => 'Starter pack core',
                'value' => (string) $coreSummary['suggestedRisksCount'],
                'helper' => $coreSummary['coreSourceCount'].' sorgenti core | '.$coreSummary['expectedMeasuresCount'].' presidi attesi suggeriti',
                'tone' => 'primary',
            ],
            [
                'key' => 'risks',
                'label' => 'Rischi dedotti',
                'value' => (string) $summary['derivedRisks'],
                'helper' => $summary['activeRisks'].' attivi nel profilo finale',
                'tone' => 'warning',
            ],
            [
                'key' => 'consultant',
                'label' => 'Intervento consulente',
                'value' => (string) $summary['consultantAdjustedRisks'],
                'helper' => $summary['manualRisks'].' manuali | '.$summary['excludedRisks'].' esclusi',
                'tone' => 'primary',
            ],
            [
                'key' => 'coverage',
                'label' => 'Misure e copertura',
                'value' => (string) $summary['pendingMeasures'],
                'helper' => $summary['expectedMeasures'] > 0
                    ? $summary['coveredExpectedMeasures'].' presidi attesi coperti'
                        .($summary['substitutedExpectedMeasures'] > 0 ? ' | '.$summary['substitutedExpectedMeasures'].' per equivalenza' : '')
                        .' | '.$summary['missingExpectedMeasures'].' gap'
                    : $summary['implementedMeasures'].' attuate | copertura '.$summary['coverageRate'].'%',
                'tone' => $summary['pendingMeasures'] > 0 ? 'danger' : 'success',
            ],
        ];
    }

    private function buildSourceFamilies(Collection $profileItems): Collection
    {
        return $profileItems
            ->flatMap(fn (RiskProfileItem $item) => $item->sources)
            ->groupBy('source_family')
            ->map(fn (Collection $sources, string $family) => [
                'family' => $family,
                'label' => $this->sourceFamilyLabel($family),
                'count' => $sources
                    ->unique(fn ($source) => $source->sourceable_type.':'.$source->sourceable_id)
                    ->count(),
            ])
            ->sortBy('label')
            ->values();
    }

    private function buildSourceInputs(Collection $profileItems): Collection
    {
        return $profileItems
            ->flatMap(fn (RiskProfileItem $item) => $item->sources)
            ->unique(fn ($source) => $source->sourceable_type.':'.$source->sourceable_id)
            ->map(fn ($source) => [
                'family' => $source->source_family,
                'family_label' => $this->sourceFamilyLabel($source->source_family),
                'label' => $source->source_label,
                'relevance' => $source->relevance,
            ])
            ->sortBy(fn (array $source) => $source['family_label'].'-'.$source['label'])
            ->values();
    }

    private function buildRiskEntries(Collection $profileItems, Collection $measures): Collection
    {
        $measuresByRiskContext = $this->groupMeasuresByRiskContext($measures);

        return $profileItems->map(function (RiskProfileItem $item) use ($measuresByRiskContext) {
            $riskMeasures = $measuresByRiskContext->get($this->riskContextKeyForProfileItem($item), collect());
            $measureSummary = $this->buildMeasureSummary($riskMeasures);
            $expectedSummary = $this->riskExpectedMeasureResolver
                ->snapshotForRisk($item->riskCatalogItem, $riskMeasures)['summary'];

            return [
                'id' => $item->id,
                'source_state' => $this->sourceStateForRisk($item),
                'final_state' => $this->finalStateForRisk($item),
                'coverage' => $this->coverageForRisk($item, $measureSummary, $expectedSummary),
            ];
        });
    }

    private function groupMeasuresByRiskContext(Collection $measures): Collection
    {
        return $measures
            ->groupBy(fn (RiskMeasure $measure) => $this->riskContextKey(
                $measure->profileable_type,
                (int) $measure->profileable_id,
                (int) $measure->risk_catalog_item_id,
            ))
            ->map(fn (Collection $riskMeasures) => $riskMeasures->values());
    }

    private function sourceStateForRisk(RiskProfileItem $item): array
    {
        if ($item->is_manual) {
            return [
                'key' => 'manual',
                'label' => 'Aggiunta manuale',
                'helper' => 'Il rischio e\' stato introdotto o mantenuto dal consulente oltre la deduzione standard.',
                'tone' => 'primary',
            ];
        }

        if ($item->is_currently_derived) {
            return [
                'key' => 'derived',
                'label' => 'Derivato da sorgenti attive',
                'helper' => $item->source_count.' sorgenti attive collegate nel contesto operativo.',
                'tone' => 'info',
            ];
        }

        return [
            'key' => 'retained',
            'label' => 'Mantenuto oltre la deduzione',
            'helper' => 'La derivazione automatica non e\' piu\' attiva, ma il rischio resta tracciato per decisione consulenziale o presidi esistenti.',
            'tone' => 'secondary',
        ];
    }

    private function finalStateForRisk(RiskProfileItem $item): array
    {
        if ($item->operational_status === RiskProfileItem::OPERATIONAL_STATUS_EXCLUDED) {
            return [
                'label' => 'Escluso dal profilo finale',
                'helper' => 'Il consulente ha esplicitamente escluso il rischio dal perimetro operativo corrente.',
                'tone' => 'secondary',
            ];
        }

        return [
            'label' => 'Attivo nel profilo finale',
            'helper' => $item->consultant_decision !== null
                ? 'Decisione consulente: '.$this->decisionLabel($item->consultant_decision)
                : 'Rischio attivo e governato nel profilo operativo finale.',
            'tone' => 'warning',
        ];
    }

    private function coverageForRisk(RiskProfileItem $item, array $measureSummary, array $expectedSummary): array
    {
        if ($item->operational_status === RiskProfileItem::OPERATIONAL_STATUS_EXCLUDED) {
            return [
                'label' => 'Fuori perimetro operativo',
                'helper' => 'Il rischio e\' escluso e non concorre alla copertura corrente.',
                'tone' => 'secondary',
                'summary' => [
                    'measures' => $measureSummary,
                    'expected' => $expectedSummary,
                ],
            ];
        }

        if ($item->status === RiskProfileItem::STATUS_COVERED) {
            return [
                'label' => 'Coperto',
                'helper' => $expectedSummary['expected_count'] > 0
                    ? $expectedSummary['covered_count'].' presidi attesi coperti su '.$expectedSummary['expected_count']
                        .($expectedSummary['substituted_count'] > 0 ? ' | '.$expectedSummary['substituted_count'].' tramite equivalenza di famiglia.' : '.')
                    : $measureSummary['implemented'].' misure attuate su '.$measureSummary['count'].' collegate.',
                'tone' => 'success',
                'summary' => [
                    'measures' => $measureSummary,
                    'expected' => $expectedSummary,
                ],
            ];
        }

        if ($expectedSummary['expected_count'] > 0) {
            return [
                'label' => 'Da presidiare',
                'helper' => ($expectedSummary['missing_count'] + $expectedSummary['partial_count']).' presidi attesi ancora non coperti.',
                'tone' => 'warning',
                'summary' => [
                    'measures' => $measureSummary,
                    'expected' => $expectedSummary,
                ],
            ];
        }

        if ($measureSummary['count'] === 0) {
            return [
                'label' => 'Da presidiare',
                'helper' => 'Nessuna misura ancora collegata a questo rischio.',
                'tone' => 'danger',
                'summary' => [
                    'measures' => $measureSummary,
                    'expected' => $expectedSummary,
                ],
            ];
        }

        return [
            'label' => 'Da presidiare',
            'helper' => $measureSummary['pending'].' misure ancora aperte o da verificare.',
            'tone' => 'warning',
            'summary' => [
                'measures' => $measureSummary,
                'expected' => $expectedSummary,
            ],
        ];
    }

    private function buildMeasureSummary(Collection $measures): array
    {
        return [
            'count' => $measures->count(),
            'implemented' => $measures->where('status', RiskMeasure::STATUS_IMPLEMENTED)->count(),
            'to_verify' => $measures->where('status', RiskMeasure::STATUS_TO_VERIFY)->count(),
            'pending' => $measures->filter(
                fn (RiskMeasure $measure) => $measure->status !== RiskMeasure::STATUS_IMPLEMENTED
            )->count(),
        ];
    }

    private function riskContextKeyForProfileItem(RiskProfileItem $item): string
    {
        return $this->riskContextKey(
            $item->profileable_type,
            (int) $item->profileable_id,
            (int) $item->risk_catalog_item_id,
        );
    }

    private function riskContextKey(string $profileableType, int $profileableId, int $riskCatalogItemId): string
    {
        return implode(':', [$profileableType, $profileableId, $riskCatalogItemId]);
    }

    private function sourceFamilyLabel(?string $family): string
    {
        return match ($family) {
            'job_role' => 'Mansioni',
            'equipment_type' => 'Macchinari',
            'workplace_type' => 'Luoghi',
            default => 'Sorgenti',
        };
    }

    private function decisionLabel(?string $decision): string
    {
        return match ($decision) {
            RiskProfileItem::DECISION_CONFIRMED => 'confermato',
            RiskProfileItem::DECISION_CUSTOMIZED => 'personalizzato',
            RiskProfileItem::DECISION_EXCLUDED => 'escluso',
            RiskProfileItem::DECISION_MANUAL_ADDITION => 'aggiunta manuale',
            default => 'non esplicitata',
        };
    }
}
