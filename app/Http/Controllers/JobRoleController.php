<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureTenantCanManageData;
use App\Http\Requests\StoreJobRoleRequest;
use App\Http\Requests\UpdateJobRoleRequest;
use App\Models\JobRole;
use App\Models\Tenant;
use App\Support\CurrentTenantResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobRoleController extends Controller
{
    public function __construct()
    {
        $this->middleware(EnsureTenantCanManageData::class)->only(['create', 'store', 'edit', 'update']);
    }

    public function index(Request $request, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());

        $jobRoles = JobRole::query()
            ->where(function ($query) use ($tenant) {
                $query->where('source', JobRole::SOURCE_CORE)
                    ->orWhere('tenant_id', $tenant->id);
            })
            ->withCount('workerAssignments')
            ->orderByRaw("case when source = 'tenant' then 0 else 1 end")
            ->orderBy('name')
            ->get();

        return Inertia::render('sicurezzachiara/job-roles/Index', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'jobRoles' => $jobRoles,
            'summary' => [
                'totalCount' => $jobRoles->count(),
                'tenantCount' => $jobRoles->where('source', JobRole::SOURCE_TENANT)->count(),
                'coreCount' => $jobRoles->where('source', JobRole::SOURCE_CORE)->count(),
            ],
        ]);
    }

    public function create(Request $request, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());

        return Inertia::render('sicurezzachiara/job-roles/Create', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
        ]);
    }

    public function store(StoreJobRoleRequest $request, CurrentTenantResolver $tenantResolver): RedirectResponse
    {
        $tenant = $tenantResolver->resolve($request->user());

        $jobRole = $tenant->jobRoles()->create([
            ...$request->validated(),
            'source' => JobRole::SOURCE_TENANT,
        ]);

        return redirect()
            ->route('job-roles.show', $jobRole)
            ->with('success', 'Mansione catalogo creata correttamente.');
    }

    public function show(Request $request, JobRole $jobRole, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());
        $jobRole = $this->jobRoleForTenant($tenant, $jobRole);
        $jobRole->load([
            'tenant:id,name',
            'workerAssignments' => fn ($query) => $query
                ->with(['worker.company:id,name', 'worker:id,company_id,first_name,last_name'])
                ->orderByDesc('is_primary')
                ->orderBy('id'),
            'riskSourceLinks' => fn ($query) => $query
                ->with(['riskCatalogItem.category:id,name', 'riskCatalogItem:id,risk_category_id,name,source,default_priority'])
                ->orderBy('id'),
        ]);

        return Inertia::render('sicurezzachiara/job-roles/Show', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'jobRole' => $jobRole,
        ]);
    }

    public function edit(Request $request, JobRole $jobRole, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());
        $jobRole = $this->tenantManagedJobRole($tenant, $jobRole);

        return Inertia::render('sicurezzachiara/job-roles/Edit', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'jobRole' => $jobRole,
        ]);
    }

    public function update(
        UpdateJobRoleRequest $request,
        JobRole $jobRole,
        CurrentTenantResolver $tenantResolver,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $jobRole = $this->tenantManagedJobRole($tenant, $jobRole);

        $jobRole->update($request->validated());

        return redirect()
            ->route('job-roles.show', $jobRole)
            ->with('success', 'Mansione catalogo aggiornata correttamente.');
    }

    private function jobRoleForTenant(Tenant $tenant, JobRole $jobRole): JobRole
    {
        $allowed = $jobRole->source === JobRole::SOURCE_CORE || $jobRole->tenant_id === $tenant->id;
        abort_unless($allowed, 404);

        return $jobRole;
    }

    private function tenantManagedJobRole(Tenant $tenant, JobRole $jobRole): JobRole
    {
        abort_unless($jobRole->tenant_id === $tenant->id && $jobRole->source === JobRole::SOURCE_TENANT, 404);

        return $jobRole;
    }
}
