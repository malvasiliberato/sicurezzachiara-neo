<?php

namespace App\Support;

use App\Models\Company;
use App\Models\EquipmentType;
use App\Models\JobRole;
use App\Models\RiskCatalogItem;
use App\Models\RiskProfileItem;
use App\Models\RiskSourceLink;
use App\Models\Tenant;
use App\Models\Worker;
use App\Models\WorkplaceType;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RiskProfileBuilder
{
    public function __construct(
        private readonly RiskCoverageResolver $riskCoverageResolver,
    ) {}

    public function rebuildCompany(Company $company): void
    {
        $sources = $this->collectCompanySources($company);
        $this->syncProfile($company, $sources);
    }

    public function rebuildWorker(Worker $worker): void
    {
        $sources = $this->collectWorkerSources($worker);
        $this->syncProfile($worker, $sources);
    }

    private function collectCompanySources(Company $company): array
    {
        $jobRoles = JobRole::query()
            ->whereHas('workerAssignments.worker', fn ($query) => $query->where('company_id', $company->id))
            ->get();

        $equipmentTypes = EquipmentType::query()
            ->whereHas('assets', fn ($query) => $query->where('company_id', $company->id))
            ->get();

        $workplaceTypes = WorkplaceType::query()
            ->whereHas('workplaces.site', fn ($query) => $query->where('company_id', $company->id))
            ->get();

        return [
            ['family' => 'job_role', 'items' => $jobRoles],
            ['family' => 'equipment_type', 'items' => $equipmentTypes],
            ['family' => 'workplace_type', 'items' => $workplaceTypes],
        ];
    }

    private function collectWorkerSources(Worker $worker): array
    {
        $worker->loadMissing([
            'jobRoles',
            'equipmentExposures.equipmentAsset.equipmentType',
            'workplaceExposures.workplace.workplaceType',
        ]);

        $jobRoles = $worker->jobRoles;
        $equipmentTypes = $worker->equipmentExposures
            ->map(fn ($exposure) => $exposure->equipmentAsset?->equipmentType)
            ->filter()
            ->unique('id')
            ->values();

        $workplaceTypes = $worker->workplaceExposures
            ->map(fn ($exposure) => $exposure->workplace?->workplaceType)
            ->filter()
            ->unique('id')
            ->values();

        return [
            ['family' => 'job_role', 'items' => $jobRoles],
            ['family' => 'equipment_type', 'items' => $equipmentTypes],
            ['family' => 'workplace_type', 'items' => $workplaceTypes],
        ];
    }

    private function syncProfile(Model $profileable, array $sourceFamilies): void
    {
        $links = $this->resolveLinks($this->tenantFor($profileable), $sourceFamilies);
        $groupedByRisk = $links->groupBy('risk_catalog_item_id');

        DB::transaction(function () use ($profileable, $groupedByRisk) {
            $existingItems = $profileable->riskProfileItems()
                ->get()
                ->keyBy('risk_catalog_item_id');

            $activeRiskIds = $groupedByRisk->keys()->map(fn ($key) => (int) $key)->all();

            if ($activeRiskIds === []) {
                foreach ($existingItems as $existingItem) {
                    $this->handleMissingDerivedRisk($profileable, $existingItem);
                }

                return;
            }

            foreach ($groupedByRisk as $riskCatalogItemId => $riskLinks) {
                $risk = $riskLinks->first()->riskCatalogItem;
                /** @var RiskProfileItem|null $existingItem */
                $existingItem = $existingItems->pull((int) $riskCatalogItemId);

                $attributes = [
                    'status' => $this->riskCoverageResolver->resolveForProfileable(
                        $profileable,
                        (int) $riskCatalogItemId,
                    ),
                    'priority' => $risk->default_priority,
                    'source_count' => $riskLinks->count(),
                    'is_currently_derived' => true,
                    'last_calculated_at' => now(),
                ];

                if ($existingItem === null) {
                    $profileItem = $profileable->riskProfileItems()->create([
                        'risk_catalog_item_id' => $riskCatalogItemId,
                        ...$attributes,
                        'is_manual' => false,
                        'operational_status' => RiskProfileItem::OPERATIONAL_STATUS_ACTIVE,
                    ]);
                } else {
                    $existingItem->update($attributes);
                    $profileItem = $existingItem->fresh();
                }

                $profileItem->sources()->delete();

                foreach ($riskLinks as $riskLink) {
                    $profileItem->sources()->create([
                        'sourceable_type' => $riskLink->sourceable_type,
                        'sourceable_id' => $riskLink->sourceable_id,
                        'source_family' => $this->sourceFamilyFromType($riskLink->sourceable_type),
                        'source_label' => $riskLink->sourceable?->name ?? 'Sorgente non disponibile',
                        'relevance' => $riskLink->relevance,
                    ]);
                }
            }

            foreach ($existingItems as $existingItem) {
                $this->handleMissingDerivedRisk($profileable, $existingItem);
            }
        });
    }

    private function handleMissingDerivedRisk(Model $profileable, RiskProfileItem $profileItem): void
    {
        $hasConsultantLayer = $profileItem->is_manual
            || $profileItem->consultant_decision !== null
            || $profileItem->consultant_notes !== null
            || $profileItem->final_priority !== null
            || $profileItem->review_due_at !== null;
        $hasMeasures = $profileable->riskMeasures()
            ->where('risk_catalog_item_id', $profileItem->risk_catalog_item_id)
            ->exists();

        if (! $hasConsultantLayer && ! $hasMeasures) {
            $profileItem->delete();

            return;
        }

        $profileItem->update([
            'status' => $this->riskCoverageResolver->resolveForProfileable(
                $profileable,
                (int) $profileItem->risk_catalog_item_id,
            ),
            'source_count' => 0,
            'is_currently_derived' => false,
            'last_calculated_at' => now(),
        ]);

        $profileItem->sources()->delete();
    }

    private function resolveLinks(Tenant $tenant, array $sourceFamilies): Collection
    {
        return collect($sourceFamilies)
            ->flatMap(function (array $sourceFamily) use ($tenant) {
                /** @var Collection|EloquentCollection $items */
                $items = collect($sourceFamily['items'])->filter()->unique('id')->values();

                return $items->flatMap(function (Model $item) use ($tenant) {
                    return RiskSourceLink::query()
                        ->with(['riskCatalogItem', 'sourceable'])
                        ->whereHas('riskCatalogItem', function ($query) use ($tenant) {
                            $query->where(function ($riskQuery) use ($tenant) {
                                $riskQuery->where('source', RiskCatalogItem::SOURCE_CORE)
                                    ->orWhere('tenant_id', $tenant->id);
                            });
                        })
                        ->where('sourceable_type', $item::class)
                        ->where('sourceable_id', $item->getKey())
                        ->get();
                });
            })
            ->unique(fn (RiskSourceLink $link) => implode(':', [
                $link->risk_catalog_item_id,
                $link->sourceable_type,
                $link->sourceable_id,
            ]))
            ->values();
    }

    private function tenantFor(Model $profileable): Tenant
    {
        if ($profileable instanceof Company) {
            return $profileable->tenant()->firstOrFail();
        }

        if ($profileable instanceof Worker) {
            return $profileable->company()->with('tenant')->firstOrFail()->tenant;
        }

        throw new \InvalidArgumentException('Profileable non supportato per il motore di profilo rischio.');
    }

    private function sourceFamilyFromType(string $type): string
    {
        return match ($type) {
            JobRole::class => 'job_role',
            EquipmentType::class => 'equipment_type',
            WorkplaceType::class => 'workplace_type',
            default => 'unknown',
        };
    }
}
