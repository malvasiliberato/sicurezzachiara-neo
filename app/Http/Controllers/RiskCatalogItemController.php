<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureTenantCanManageData;
use App\Http\Requests\StoreRiskCatalogItemRequest;
use App\Http\Requests\UpdateRiskCatalogItemRequest;
use App\Models\EquipmentType;
use App\Models\JobRole;
use App\Models\RiskCatalogItem;
use App\Models\RiskCategory;
use App\Models\Tenant;
use App\Models\WorkplaceType;
use App\Support\CurrentTenantResolver;
use App\Support\RiskExpectedMeasureResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RiskCatalogItemController extends Controller
{
    public function __construct()
    {
        $this->middleware(EnsureTenantCanManageData::class)->only(['create', 'store', 'edit', 'update']);
    }

    public function index(Request $request, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());

        $risks = RiskCatalogItem::query()
            ->where(function ($query) use ($tenant) {
                $query->where('source', RiskCatalogItem::SOURCE_CORE)
                    ->orWhere('tenant_id', $tenant->id);
            })
            ->with('category:id,name')
            ->withCount('sourceLinks')
            ->orderByRaw("case when source = 'tenant' then 0 else 1 end")
            ->orderBy('name')
            ->get();

        return Inertia::render('sicurezzachiara/risk-catalog/Index', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'risks' => $risks,
            'summary' => [
                'totalCount' => $risks->count(),
                'tenantCount' => $risks->where('source', RiskCatalogItem::SOURCE_TENANT)->count(),
                'coreCount' => $risks->where('source', RiskCatalogItem::SOURCE_CORE)->count(),
                'categoriesCount' => $risks->pluck('risk_category_id')->unique()->count(),
            ],
        ]);
    }

    public function create(Request $request, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());

        return Inertia::render('sicurezzachiara/risk-catalog/Create', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'formOptions' => $this->formOptions($tenant),
        ]);
    }

    public function store(
        StoreRiskCatalogItemRequest $request,
        CurrentTenantResolver $tenantResolver,
        RiskExpectedMeasureResolver $riskExpectedMeasureResolver,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());

        $risk = $tenant->riskCatalogItems()->create([
            ...$this->catalogPayload($request->validated(), $riskExpectedMeasureResolver),
            'source' => RiskCatalogItem::SOURCE_TENANT,
        ]);

        return redirect()
            ->route('risk-catalog.show', $risk)
            ->with('success', 'Rischio catalogo creato correttamente.');
    }

    public function show(
        Request $request,
        RiskCatalogItem $riskCatalogItem,
        CurrentTenantResolver $tenantResolver,
    ): Response {
        $tenant = $tenantResolver->resolve($request->user());
        $riskCatalogItem = $this->riskForTenant($tenant, $riskCatalogItem);
        $riskCatalogItem->load([
            'tenant:id,name',
            'category:id,name,description',
            'sourceLinks' => fn ($query) => $query->with('sourceable')->orderBy('sourceable_type')->orderBy('id'),
        ]);

        return Inertia::render('sicurezzachiara/risk-catalog/Show', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'risk' => $riskCatalogItem,
            'formOptions' => $this->mappingOptions($tenant),
        ]);
    }

    public function edit(
        Request $request,
        RiskCatalogItem $riskCatalogItem,
        CurrentTenantResolver $tenantResolver,
    ): Response {
        $tenant = $tenantResolver->resolve($request->user());
        $riskCatalogItem = $this->tenantManagedRisk($tenant, $riskCatalogItem);

        return Inertia::render('sicurezzachiara/risk-catalog/Edit', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'risk' => $riskCatalogItem,
            'formOptions' => $this->formOptions($tenant),
        ]);
    }

    public function update(
        UpdateRiskCatalogItemRequest $request,
        RiskCatalogItem $riskCatalogItem,
        CurrentTenantResolver $tenantResolver,
        RiskExpectedMeasureResolver $riskExpectedMeasureResolver,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $riskCatalogItem = $this->tenantManagedRisk($tenant, $riskCatalogItem);

        $riskCatalogItem->update($this->catalogPayload($request->validated(), $riskExpectedMeasureResolver));

        return redirect()
            ->route('risk-catalog.show', $riskCatalogItem)
            ->with('success', 'Rischio catalogo aggiornato correttamente.');
    }

    private function formOptions(Tenant $tenant): array
    {
        return [
            'categories' => RiskCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'description'])
                ->toArray(),
            'priorities' => [
                ['value' => RiskCatalogItem::PRIORITY_LOW, 'label' => 'Bassa'],
                ['value' => RiskCatalogItem::PRIORITY_MEDIUM, 'label' => 'Media'],
                ['value' => RiskCatalogItem::PRIORITY_HIGH, 'label' => 'Alta'],
            ],
            'measureFamilies' => app(RiskExpectedMeasureResolver::class)->formOptions(),
            'tenant' => $tenant->only(['id', 'name']),
        ];
    }

    private function catalogPayload(array $validated, RiskExpectedMeasureResolver $riskExpectedMeasureResolver): array
    {
        $expectedMeasures = collect($validated['expected_measures'] ?? [])
            ->map(function (array $expectedMeasure, int $index) {
                return [
                    'code' => $expectedMeasure['code'] ?: 'measure_'.($index + 1),
                    'family' => $expectedMeasure['family'],
                    'title' => $expectedMeasure['title'],
                    'description' => $expectedMeasure['description'] ?? null,
                    'is_required' => (bool) ($expectedMeasure['is_required'] ?? true),
                ];
            })
            ->filter(fn (array $expectedMeasure) => filled($expectedMeasure['title']))
            ->values()
            ->all();

        return [
            ...$validated,
            'expected_measures' => $expectedMeasures === [] ? null : $riskExpectedMeasureResolver
                ->templatesForRisk(new RiskCatalogItem(['expected_measures' => $expectedMeasures]))
                ->values()
                ->all(),
        ];
    }

    private function mappingOptions(Tenant $tenant): array
    {
        return [
            'jobRoles' => JobRole::query()
                ->where(function ($query) use ($tenant) {
                    $query->where('source', JobRole::SOURCE_CORE)
                        ->orWhere('tenant_id', $tenant->id);
                })
                ->where('is_active', true)
                ->orderByRaw("case when source = 'tenant' then 0 else 1 end")
                ->orderBy('name')
                ->get(['id', 'name', 'source'])
                ->toArray(),
            'equipmentTypes' => EquipmentType::query()
                ->where(function ($query) use ($tenant) {
                    $query->where('source', EquipmentType::SOURCE_CORE)
                        ->orWhere('tenant_id', $tenant->id);
                })
                ->where('is_active', true)
                ->orderByRaw("case when source = 'tenant' then 0 else 1 end")
                ->orderBy('name')
                ->get(['id', 'name', 'source'])
                ->toArray(),
            'workplaceTypes' => WorkplaceType::query()
                ->where(function ($query) use ($tenant) {
                    $query->where('source', WorkplaceType::SOURCE_CORE)
                        ->orWhere('tenant_id', $tenant->id);
                })
                ->where('is_active', true)
                ->orderByRaw("case when source = 'tenant' then 0 else 1 end")
                ->orderBy('name')
                ->get(['id', 'name', 'source'])
                ->toArray(),
        ];
    }

    private function riskForTenant(Tenant $tenant, RiskCatalogItem $riskCatalogItem): RiskCatalogItem
    {
        abort_unless(
            $riskCatalogItem->source === RiskCatalogItem::SOURCE_CORE || $riskCatalogItem->tenant_id === $tenant->id,
            404,
        );

        return $riskCatalogItem;
    }

    private function tenantManagedRisk(Tenant $tenant, RiskCatalogItem $riskCatalogItem): RiskCatalogItem
    {
        abort_unless(
            $riskCatalogItem->tenant_id === $tenant->id && $riskCatalogItem->source === RiskCatalogItem::SOURCE_TENANT,
            404,
        );

        return $riskCatalogItem;
    }
}
