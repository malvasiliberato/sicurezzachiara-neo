<?php

namespace App\Support;

use App\Models\EquipmentType;
use App\Models\JobRole;
use App\Models\RiskCatalogItem;
use App\Models\RiskProfileItemSource;
use App\Models\WorkplaceType;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class CoreStarterPackContextBuilder
{
    public function buildForCompanySources(
        Collection $jobRoles,
        Collection $equipmentTypes,
        Collection $workplaceTypes,
    ): array {
        return $this->buildFromSourceCollections($jobRoles, $equipmentTypes, $workplaceTypes);
    }

    public function buildForWorkerSources(
        Collection $jobRoles,
        Collection $equipmentTypes,
        Collection $workplaceTypes,
    ): array {
        return $this->buildFromSourceCollections($jobRoles, $equipmentTypes, $workplaceTypes);
    }

    public function buildForProfileSources(Collection $profileSources): array
    {
        (new EloquentCollection($profileSources->all()))->loadMorph('sourceable', [
            JobRole::class => ['riskSourceLinks.riskCatalogItem.category'],
            EquipmentType::class => ['riskSourceLinks.riskCatalogItem.category'],
            WorkplaceType::class => ['riskSourceLinks.riskCatalogItem.category'],
        ]);

        return $this->buildFromSourceCollections(
            $profileSources
                ->where('sourceable_type', JobRole::class)
                ->map(fn (RiskProfileItemSource $source) => $source->sourceable)
                ->filter()
                ->values(),
            $profileSources
                ->where('sourceable_type', EquipmentType::class)
                ->map(fn (RiskProfileItemSource $source) => $source->sourceable)
                ->filter()
                ->values(),
            $profileSources
                ->where('sourceable_type', WorkplaceType::class)
                ->map(fn (RiskProfileItemSource $source) => $source->sourceable)
                ->filter()
                ->values(),
        );
    }

    private function buildFromSourceCollections(
        Collection $jobRoles,
        Collection $equipmentTypes,
        Collection $workplaceTypes,
    ): array {
        $families = collect([
            [
                'key' => 'job_roles',
                'label' => 'Mansioni',
                'items' => $this->normalizeSources($jobRoles, JobRole::SOURCE_CORE),
            ],
            [
                'key' => 'equipment_types',
                'label' => 'Macchinari',
                'items' => $this->normalizeSources($equipmentTypes, EquipmentType::SOURCE_CORE),
            ],
            [
                'key' => 'workplace_types',
                'label' => 'Luoghi',
                'items' => $this->normalizeSources($workplaceTypes, WorkplaceType::SOURCE_CORE),
            ],
        ]);

        $allItems = $families->flatMap(fn (array $family) => $family['items']);
        $coreItems = $allItems->where('source', 'core')->values();
        $tenantItems = $allItems->where('source', 'tenant')->values();

        $suggestedRisks = $coreItems
            ->flatMap(function (array $item) {
                return collect($item['linked_risks'])->map(fn (array $risk) => [
                    'id' => $risk['id'],
                    'name' => $risk['name'],
                    'category_name' => $risk['category_name'],
                    'default_priority' => $risk['default_priority'],
                    'expected_measures_count' => $risk['expected_measures_count'],
                    'trigger' => $item['name'],
                    'family_label' => $item['family_label'],
                ]);
            })
            ->groupBy('id')
            ->map(function (Collection $group) {
                $first = $group->first();

                return [
                    'id' => $first['id'],
                    'name' => $first['name'],
                    'category_name' => $first['category_name'],
                    'default_priority' => $first['default_priority'],
                    'expected_measures_count' => $first['expected_measures_count'],
                    'trigger_count' => $group->count(),
                    'triggers' => $group
                        ->map(fn (array $entry) => $entry['family_label'].': '.$entry['trigger'])
                        ->unique()
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy([
                ['trigger_count', 'desc'],
                ['name', 'asc'],
            ])
            ->values();

        return [
            'summary' => [
                'sourceCount' => $allItems->count(),
                'coreSourceCount' => $coreItems->count(),
                'tenantSourceCount' => $tenantItems->count(),
                'suggestedRisksCount' => $suggestedRisks->count(),
                'expectedMeasuresCount' => (int) $suggestedRisks->sum('expected_measures_count'),
            ],
            'families' => $families
                ->map(fn (array $family) => [
                    ...$family,
                    'items' => array_values($family['items']->all()),
                ])
                ->all(),
            'suggestedRisks' => $suggestedRisks->all(),
        ];
    }

    private function normalizeSources(Collection $sources, string $coreSource): Collection
    {
        return $sources
            ->filter()
            ->unique(fn ($source) => get_class($source).':'.$source->getKey())
            ->values()
            ->map(function ($source) use ($coreSource) {
                $linkedRisks = $source->riskSourceLinks
                    ->filter(fn ($link) => $link->riskCatalogItem?->source === RiskCatalogItem::SOURCE_CORE)
                    ->map(fn ($link) => [
                        'id' => $link->riskCatalogItem?->id,
                        'name' => $link->riskCatalogItem?->name,
                        'category_name' => $link->riskCatalogItem?->category?->name,
                        'default_priority' => $link->riskCatalogItem?->default_priority,
                        'expected_measures_count' => count($link->riskCatalogItem?->expected_measures ?? []),
                    ])
                    ->unique('id')
                    ->sortBy('name')
                    ->values()
                    ->all();

                return [
                    'id' => $source->id,
                    'name' => $source->name,
                    'description' => $source->description,
                    'source' => $source->source === $coreSource ? 'core' : 'tenant',
                    'family_label' => $this->familyLabel($source),
                    'linked_risks' => $linkedRisks,
                ];
            });
    }

    private function familyLabel(object $source): string
    {
        return match ($source::class) {
            JobRole::class => 'Mansione',
            EquipmentType::class => 'Macchinario',
            WorkplaceType::class => 'Luogo',
            default => 'Sorgente',
        };
    }
}
