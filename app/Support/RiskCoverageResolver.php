<?php

namespace App\Support;

use App\Models\RiskCatalogItem;
use App\Models\RiskMeasure;
use App\Models\RiskProfileItem;
use Illuminate\Database\Eloquent\Model;

class RiskCoverageResolver
{
    public function __construct(
        private readonly RiskExpectedMeasureResolver $riskExpectedMeasureResolver,
    ) {}

    public function resolveForProfileable(Model $profileable, int $riskCatalogItemId): string
    {
        $measures = RiskMeasure::query()
            ->where('profileable_type', $profileable::class)
            ->where('profileable_id', $profileable->getKey())
            ->where('risk_catalog_item_id', $riskCatalogItemId)
            ->get(['id', 'family', 'status', 'expected_measure_code', 'title']);

        $riskCatalogItem = RiskCatalogItem::query()->findOrFail($riskCatalogItemId);

        return $this->riskExpectedMeasureResolver->coverageStatusForRisk($riskCatalogItem, $measures);
    }

    public function syncForProfileableRisk(Model $profileable, int $riskCatalogItemId): void
    {
        $profileItem = RiskProfileItem::query()
            ->where('profileable_type', $profileable::class)
            ->where('profileable_id', $profileable->getKey())
            ->where('risk_catalog_item_id', $riskCatalogItemId)
            ->first();

        if (! $profileItem) {
            return;
        }

        $profileItem->update([
            'status' => $this->resolveForProfileable($profileable, $riskCatalogItemId),
        ]);
    }
}
