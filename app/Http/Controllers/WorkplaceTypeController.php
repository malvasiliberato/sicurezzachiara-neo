<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureTenantCanManageData;
use App\Http\Requests\StoreWorkplaceTypeRequest;
use App\Http\Requests\UpdateWorkplaceTypeRequest;
use App\Models\Tenant;
use App\Models\WorkplaceType;
use App\Support\CurrentTenantResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkplaceTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware(EnsureTenantCanManageData::class)->only(['create', 'store', 'edit', 'update']);
    }

    public function index(Request $request, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());

        $workplaceTypes = WorkplaceType::query()
            ->where(fn ($query) => $query->where('source', WorkplaceType::SOURCE_CORE)->orWhere('tenant_id', $tenant->id))
            ->withCount('workplaces')
            ->orderByRaw("case when source = 'tenant' then 0 else 1 end")
            ->orderBy('name')
            ->get();

        return Inertia::render('sicurezzachiara/workplace-types/Index', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'workplaceTypes' => $workplaceTypes,
            'summary' => [
                'totalCount' => $workplaceTypes->count(),
                'tenantCount' => $workplaceTypes->where('source', WorkplaceType::SOURCE_TENANT)->count(),
                'coreCount' => $workplaceTypes->where('source', WorkplaceType::SOURCE_CORE)->count(),
            ],
        ]);
    }

    public function create(Request $request, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());

        return Inertia::render('sicurezzachiara/workplace-types/Create', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
        ]);
    }

    public function store(StoreWorkplaceTypeRequest $request, CurrentTenantResolver $tenantResolver): RedirectResponse
    {
        $tenant = $tenantResolver->resolve($request->user());

        $workplaceType = $tenant->workplaceTypes()->create([
            ...$request->validated(),
            'source' => WorkplaceType::SOURCE_TENANT,
        ]);

        return redirect()->route('workplace-types.show', $workplaceType)->with('success', 'Tipologia luogo creata correttamente.');
    }

    public function show(Request $request, WorkplaceType $workplaceType, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());
        $workplaceType = $this->workplaceTypeForTenant($tenant, $workplaceType);
        $workplaceType->load([
            'tenant:id,name',
            'workplaces' => fn ($query) => $query->with(['site.company:id,name', 'site:id,company_id,name'])->orderBy('name'),
            'riskSourceLinks' => fn ($query) => $query
                ->with(['riskCatalogItem.category:id,name', 'riskCatalogItem:id,risk_category_id,name,source,default_priority'])
                ->orderBy('id'),
        ]);

        return Inertia::render('sicurezzachiara/workplace-types/Show', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'workplaceType' => $workplaceType,
        ]);
    }

    public function edit(Request $request, WorkplaceType $workplaceType, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());
        $workplaceType = $this->tenantManagedWorkplaceType($tenant, $workplaceType);

        return Inertia::render('sicurezzachiara/workplace-types/Edit', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'workplaceType' => $workplaceType,
        ]);
    }

    public function update(UpdateWorkplaceTypeRequest $request, WorkplaceType $workplaceType, CurrentTenantResolver $tenantResolver): RedirectResponse
    {
        $tenant = $tenantResolver->resolve($request->user());
        $workplaceType = $this->tenantManagedWorkplaceType($tenant, $workplaceType);
        $workplaceType->update($request->validated());

        return redirect()->route('workplace-types.show', $workplaceType)->with('success', 'Tipologia luogo aggiornata correttamente.');
    }

    private function workplaceTypeForTenant(Tenant $tenant, WorkplaceType $workplaceType): WorkplaceType
    {
        abort_unless($workplaceType->source === WorkplaceType::SOURCE_CORE || $workplaceType->tenant_id === $tenant->id, 404);

        return $workplaceType;
    }

    private function tenantManagedWorkplaceType(Tenant $tenant, WorkplaceType $workplaceType): WorkplaceType
    {
        abort_unless($workplaceType->tenant_id === $tenant->id && $workplaceType->source === WorkplaceType::SOURCE_TENANT, 404);

        return $workplaceType;
    }
}
