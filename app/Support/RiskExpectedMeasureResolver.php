<?php

namespace App\Support;

use App\Models\RiskCatalogItem;
use App\Models\RiskMeasure;
use App\Models\RiskProfileItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RiskExpectedMeasureResolver
{
    public const TEMPLATE_STATUS_COVERED = 'covered';

    public const TEMPLATE_STATUS_PARTIAL = 'partial';

    public const TEMPLATE_STATUS_MISSING = 'missing';

    public const COVERAGE_MODE_DIRECT = 'direct';

    public const COVERAGE_MODE_FAMILY_SUBSTITUTION = 'family_substitution';

    public const COVERAGE_MODE_MISSING = 'missing';

    public const BINDING_DIRECT_EXPECTED = 'direct_expected';

    public const BINDING_FAMILY_SUBSTITUTION = 'family_substitution';

    public const BINDING_FREE_MEASURE = 'free_measure';

    public const BINDING_UNSTRUCTURED = 'unstructured';

    public function templatesForRisk(RiskCatalogItem $riskCatalogItem): Collection
    {
        return collect($riskCatalogItem->expected_measures ?? [])
            ->map(function (array $template, int $index) {
                $code = (string) ($template['code'] ?? Str::slug(($template['family'] ?? 'measure').'-'.($template['title'] ?? 'template-'.$index)));

                return [
                    'code' => $code,
                    'family' => (string) ($template['family'] ?? RiskMeasure::FAMILY_ORGANIZATIONAL),
                    'title' => (string) ($template['title'] ?? 'Misura attesa'),
                    'description' => (string) ($template['description'] ?? ''),
                    'is_required' => (bool) ($template['is_required'] ?? true),
                    'allows_family_substitution' => (bool) ($template['allows_family_substitution'] ?? false),
                ];
            })
            ->filter(fn (array $template) => $template['title'] !== '')
            ->values();
    }

    public function snapshotForRisk(RiskCatalogItem $riskCatalogItem, Collection $measures): array
    {
        $templates = $this->templatesForRisk($riskCatalogItem);
        $requiredTemplates = $templates->where('is_required', true)->values();
        $availableSubstitutions = $measures
            ->filter(fn (RiskMeasure $measure) => blank($measure->expected_measure_code))
            ->values();

        $entries = $templates->map(function (array $template) use ($measures, &$availableSubstitutions) {
            $directMeasures = $measures
                ->where('expected_measure_code', $template['code'])
                ->values();

            $substitutionMeasure = null;
            if ($directMeasures->isEmpty() && $template['allows_family_substitution']) {
                $substitutionMeasure = $this->pickFamilySubstitution(
                    $availableSubstitutions,
                    $template['family'],
                );

                if ($substitutionMeasure !== null) {
                    $availableSubstitutions = $availableSubstitutions
                        ->reject(fn (RiskMeasure $measure) => $measure->id === $substitutionMeasure->id)
                        ->values();
                }
            }

            $matchedMeasures = $directMeasures
                ->concat($substitutionMeasure !== null ? collect([$substitutionMeasure]) : collect())
                ->values();

            $implementedCount = $matchedMeasures->where('status', RiskMeasure::STATUS_IMPLEMENTED)->count();
            $pendingCount = $matchedMeasures->count() - $implementedCount;
            $coverageMode = $this->coverageModeForMatchedMeasures($directMeasures, $substitutionMeasure);

            return [
                ...$template,
                'status' => $this->templateStatusForMatchedMeasures($implementedCount, $matchedMeasures->count()),
                'coverage_mode' => $coverageMode,
                'implemented_count' => $implementedCount,
                'pending_count' => $pendingCount,
                'linked_measures_count' => $matchedMeasures->count(),
                'linked_measures' => $matchedMeasures
                    ->map(fn (RiskMeasure $measure) => [
                        'id' => $measure->id,
                        'title' => $measure->title,
                        'status' => $measure->status,
                        'expected_code' => $template['code'],
                        'expected_title' => $template['title'],
                        'match_type' => $this->matchTypeForMeasure($measure, $template['code']),
                    ])
                    ->values()
                    ->all(),
            ];
        })->values();

        $unexpectedMeasures = $availableSubstitutions->values();
        $linkedMeasureMap = $entries
            ->flatMap(fn (array $entry) => collect($entry['linked_measures']))
            ->keyBy('id');
        $measureBindings = $measures
            ->map(function (RiskMeasure $measure) use ($templates, $linkedMeasureMap) {
                $linked = $linkedMeasureMap->get($measure->id);

                if ($linked !== null) {
                    $binding = $linked['match_type'] === self::COVERAGE_MODE_FAMILY_SUBSTITUTION
                        ? self::BINDING_FAMILY_SUBSTITUTION
                        : self::BINDING_DIRECT_EXPECTED;

                    return [
                        'measure_id' => $measure->id,
                        'binding' => $binding,
                        'label' => $linked['match_type'] === self::COVERAGE_MODE_FAMILY_SUBSTITUTION
                            ? 'Copertura equivalente'
                            : 'Aggancio diretto',
                        'detail' => $linked['match_type'] === self::COVERAGE_MODE_FAMILY_SUBSTITUTION
                            ? 'Copre "'.$linked['expected_title'].'" per equivalenza della stessa famiglia.'
                            : 'Copre direttamente "'.$linked['expected_title'].'".',
                        'expected_code' => $linked['expected_code'],
                        'expected_title' => $linked['expected_title'],
                    ];
                }

                if ($templates->isEmpty()) {
                    return [
                        'measure_id' => $measure->id,
                        'binding' => self::BINDING_UNSTRUCTURED,
                        'label' => 'Nessuna attesa esplicita',
                        'detail' => 'Il rischio non definisce ancora presidi attesi strutturati.',
                        'expected_code' => null,
                        'expected_title' => null,
                    ];
                }

                return [
                    'measure_id' => $measure->id,
                    'binding' => self::BINDING_FREE_MEASURE,
                    'label' => 'Misura libera',
                    'detail' => 'La misura e\' registrata sul rischio ma non copre alcun presidio atteso configurato.',
                    'expected_code' => null,
                    'expected_title' => null,
                ];
            })
            ->keyBy('measure_id');

        return [
            'templates' => $entries->all(),
            'measure_bindings' => $measureBindings->values()->all(),
            'summary' => [
                'expected_count' => $templates->count(),
                'required_count' => $requiredTemplates->count(),
                'covered_count' => $entries->where('status', self::TEMPLATE_STATUS_COVERED)->count(),
                'missing_count' => $entries->where('status', self::TEMPLATE_STATUS_MISSING)->count(),
                'partial_count' => $entries->where('status', self::TEMPLATE_STATUS_PARTIAL)->count(),
                'substituted_count' => $entries->where('coverage_mode', self::COVERAGE_MODE_FAMILY_SUBSTITUTION)->count(),
                'unexpected_measures_count' => $unexpectedMeasures->count(),
                'direct_measure_count' => $measureBindings->where('binding', self::BINDING_DIRECT_EXPECTED)->count(),
                'free_measures_count' => $measureBindings->where('binding', self::BINDING_FREE_MEASURE)->count(),
                'unstructured_measures_count' => $measureBindings->where('binding', self::BINDING_UNSTRUCTURED)->count(),
            ],
        ];
    }

    public function expectedGapCount(array $summary): int
    {
        return (int) ($summary['missing_count'] ?? 0) + (int) ($summary['partial_count'] ?? 0);
    }

    public function coverageStatusForRisk(RiskCatalogItem $riskCatalogItem, Collection $measures): string
    {
        $snapshot = $this->snapshotForRisk($riskCatalogItem, $measures);

        if ($snapshot['summary']['required_count'] > 0) {
            return $snapshot['summary']['missing_count'] === 0 && $snapshot['summary']['partial_count'] === 0
                ? RiskProfileItem::STATUS_COVERED
                : RiskProfileItem::STATUS_UNCOVERED;
        }

        if ($measures->isEmpty()) {
            return RiskProfileItem::STATUS_UNCOVERED;
        }

        $hasPendingMeasure = $measures->contains(
            fn (RiskMeasure $measure) => $measure->status !== RiskMeasure::STATUS_IMPLEMENTED,
        );

        return $hasPendingMeasure
            ? RiskProfileItem::STATUS_UNCOVERED
            : RiskProfileItem::STATUS_COVERED;
    }

    public function sanitizeExpectedMeasureCode(RiskCatalogItem $riskCatalogItem, ?string $expectedMeasureCode): ?string
    {
        if (blank($expectedMeasureCode)) {
            return null;
        }

        return $this->templatesForRisk($riskCatalogItem)
            ->pluck('code')
            ->contains($expectedMeasureCode)
            ? $expectedMeasureCode
            : null;
    }

    public function formOptions(): array
    {
        return [
            ['value' => RiskMeasure::FAMILY_ORGANIZATIONAL, 'label' => 'Organizzativa'],
            ['value' => RiskMeasure::FAMILY_TECHNICAL, 'label' => 'Tecnica'],
            ['value' => RiskMeasure::FAMILY_DPI, 'label' => 'DPI'],
            ['value' => RiskMeasure::FAMILY_TRAINING, 'label' => 'Formazione'],
            ['value' => RiskMeasure::FAMILY_MEDICAL, 'label' => 'Visita medica'],
        ];
    }

    private function pickFamilySubstitution(Collection $measures, string $family): ?RiskMeasure
    {
        return $measures
            ->filter(fn (RiskMeasure $measure) => $measure->family === $family)
            ->sortBy(fn (RiskMeasure $measure) => match ($measure->status) {
                RiskMeasure::STATUS_IMPLEMENTED => 0,
                RiskMeasure::STATUS_TO_VERIFY => 1,
                default => 2,
            })
            ->first();
    }

    private function templateStatusForMatchedMeasures(int $implementedCount, int $matchedMeasuresCount): string
    {
        if ($implementedCount > 0) {
            return self::TEMPLATE_STATUS_COVERED;
        }

        if ($matchedMeasuresCount > 0) {
            return self::TEMPLATE_STATUS_PARTIAL;
        }

        return self::TEMPLATE_STATUS_MISSING;
    }

    private function coverageModeForMatchedMeasures(Collection $directMeasures, ?RiskMeasure $substitutionMeasure): string
    {
        if ($directMeasures->isNotEmpty()) {
            return self::COVERAGE_MODE_DIRECT;
        }

        if ($substitutionMeasure !== null) {
            return self::COVERAGE_MODE_FAMILY_SUBSTITUTION;
        }

        return self::COVERAGE_MODE_MISSING;
    }

    private function matchTypeForMeasure(RiskMeasure $measure, string $expectedCode): string
    {
        return $measure->expected_measure_code === $expectedCode
            ? self::COVERAGE_MODE_DIRECT
            : self::COVERAGE_MODE_FAMILY_SUBSTITUTION;
    }
}
