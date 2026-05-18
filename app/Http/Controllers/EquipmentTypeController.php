<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureTenantCanManageData;
use App\Http\Requests\StoreEquipmentTypeRequest;
use App\Http\Requests\UpdateEquipmentTypeRequest;
use App\Models\EquipmentType;
use App\Models\Tenant;
use App\Support\CurrentTenantResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EquipmentTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware(EnsureTenantCanManageData::class)->only(['create', 'store', 'edit', 'update']);
    }

    public function index(Request $request, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());

        $equipmentTypes = EquipmentType::query()
            ->where(fn ($query) => $query->where('source', EquipmentType::SOURCE_CORE)->orWhere('tenant_id', $tenant->id))
            ->withCount('assets')
            ->orderByRaw("case when source = 'tenant' then 0 else 1 end")
            ->orderBy('name')
            ->get();

        return Inertia::render('sicurezzachiara/equipment-types/Index', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'equipmentTypes' => $equipmentTypes,
            'summary' => [
                'totalCount' => $equipmentTypes->count(),
                'tenantCount' => $equipmentTypes->where('source', EquipmentType::SOURCE_TENANT)->count(),
                'coreCount' => $equipmentTypes->where('source', EquipmentType::SOURCE_CORE)->count(),
            ],
        ]);
    }

    public function create(Request $request, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());

        return Inertia::render('sicurezzachiara/equipment-types/Create', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
        ]);
    }

    public function store(StoreEquipmentTypeRequest $request, CurrentTenantResolver $tenantResolver): RedirectResponse
    {
        $tenant = $tenantResolver->resolve($request->user());

        $equipmentType = $tenant->equipmentTypes()->create([
            ...$request->validated(),
            'source' => EquipmentType::SOURCE_TENANT,
        ]);

        return redirect()->route('equipment-types.show', $equipmentType)->with('success', 'Tipologia macchinario creata correttamente.');
    }

    public function show(Request $request, EquipmentType $equipmentType, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());
        $equipmentType = $this->equipmentTypeForTenant($tenant, $equipmentType);
        $equipmentType->load([
            'tenant:id,name',
            'assets' => fn ($query) => $query->with(['company:id,name', 'site:id,name'])->orderBy('name'),
            'riskSourceLinks' => fn ($query) => $query
                ->with(['riskCatalogItem.category:id,name', 'riskCatalogItem:id,risk_category_id,name,source,default_priority'])
                ->orderBy('id'),
        ]);

        return Inertia::render('sicurezzachiara/equipment-types/Show', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'equipmentType' => $equipmentType,
        ]);
    }

    public function edit(Request $request, EquipmentType $equipmentType, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());
        $equipmentType = $this->tenantManagedEquipmentType($tenant, $equipmentType);

        return Inertia::render('sicurezzachiara/equipment-types/Edit', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'equipmentType' => $equipmentType,
        ]);
    }

    public function update(UpdateEquipmentTypeRequest $request, EquipmentType $equipmentType, CurrentTenantResolver $tenantResolver): RedirectResponse
    {
        $tenant = $tenantResolver->resolve($request->user());
        $equipmentType = $this->tenantManagedEquipmentType($tenant, $equipmentType);
        $equipmentType->update($request->validated());

        return redirect()->route('equipment-types.show', $equipmentType)->with('success', 'Tipologia macchinario aggiornata correttamente.');
    }

    private function equipmentTypeForTenant(Tenant $tenant, EquipmentType $equipmentType): EquipmentType
    {
        abort_unless($equipmentType->source === EquipmentType::SOURCE_CORE || $equipmentType->tenant_id === $tenant->id, 404);

        return $equipmentType;
    }

    private function tenantManagedEquipmentType(Tenant $tenant, EquipmentType $equipmentType): EquipmentType
    {
        abort_unless($equipmentType->tenant_id === $tenant->id && $equipmentType->source === EquipmentType::SOURCE_TENANT, 404);

        return $equipmentType;
    }
}
