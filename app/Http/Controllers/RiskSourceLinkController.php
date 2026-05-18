<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureTenantCanManageData;
use App\Http\Requests\StoreRiskSourceLinkRequest;
use App\Models\EquipmentType;
use App\Models\JobRole;
use App\Models\RiskCatalogItem;
use App\Models\RiskSourceLink;
use App\Models\Tenant;
use App\Models\WorkplaceType;
use App\Support\CurrentTenantResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RiskSourceLinkController extends Controller
{
    public function __construct()
    {
        $this->middleware(EnsureTenantCanManageData::class);
    }

    public function store(
        StoreRiskSourceLinkRequest $request,
        RiskCatalogItem $riskCatalogItem,
        CurrentTenantResolver $tenantResolver,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $riskCatalogItem = $this->riskForTenant($tenant, $riskCatalogItem);
        $validated = $request->validated();
        $sourceable = $this->resolveSourceable($tenant, $validated['source_family'], $validated['sourceable_id']);

        $duplicateExists = $riskCatalogItem->sourceLinks()
            ->where('sourceable_type', $sourceable::class)
            ->where('sourceable_id', $sourceable->id)
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'sourceable_id' => 'Questa sorgente e\' gia\' collegata al rischio selezionato.',
            ]);
        }

        $riskCatalogItem->sourceLinks()->create([
            'sourceable_type' => $sourceable::class,
            'sourceable_id' => $sourceable->id,
            'relevance' => $validated['relevance'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('risk-catalog.show', $riskCatalogItem)
            ->with('success', 'Collegamento sorgente -> rischio creato correttamente.');
    }

    public function destroy(
        Request $request,
        RiskCatalogItem $riskCatalogItem,
        RiskSourceLink $riskSourceLink,
        CurrentTenantResolver $tenantResolver,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $riskCatalogItem = $this->riskForTenant($tenant, $riskCatalogItem);

        abort_unless($riskSourceLink->risk_catalog_item_id === $riskCatalogItem->id, 404);

        $riskSourceLink->delete();

        return redirect()
            ->route('risk-catalog.show', $riskCatalogItem)
            ->with('success', 'Collegamento sorgente -> rischio rimosso.');
    }

    private function resolveSourceable(Tenant $tenant, string $sourceFamily, int $sourceableId): JobRole|EquipmentType|WorkplaceType
    {
        return match ($sourceFamily) {
            'job_role' => $this->jobRoleForTenant($tenant, JobRole::query()->findOrFail($sourceableId)),
            'equipment_type' => $this->equipmentTypeForTenant($tenant, EquipmentType::query()->findOrFail($sourceableId)),
            'workplace_type' => $this->workplaceTypeForTenant($tenant, WorkplaceType::query()->findOrFail($sourceableId)),
        };
    }

    private function riskForTenant(Tenant $tenant, RiskCatalogItem $riskCatalogItem): RiskCatalogItem
    {
        abort_unless(
            $riskCatalogItem->source === RiskCatalogItem::SOURCE_CORE || $riskCatalogItem->tenant_id === $tenant->id,
            404,
        );

        return $riskCatalogItem;
    }

    private function jobRoleForTenant(Tenant $tenant, JobRole $jobRole): JobRole
    {
        abort_unless($jobRole->source === JobRole::SOURCE_CORE || $jobRole->tenant_id === $tenant->id, 404);

        return $jobRole;
    }

    private function equipmentTypeForTenant(Tenant $tenant, EquipmentType $equipmentType): EquipmentType
    {
        abort_unless(
            $equipmentType->source === EquipmentType::SOURCE_CORE || $equipmentType->tenant_id === $tenant->id,
            404,
        );

        return $equipmentType;
    }

    private function workplaceTypeForTenant(Tenant $tenant, WorkplaceType $workplaceType): WorkplaceType
    {
        abort_unless(
            $workplaceType->source === WorkplaceType::SOURCE_CORE || $workplaceType->tenant_id === $tenant->id,
            404,
        );

        return $workplaceType;
    }
}
