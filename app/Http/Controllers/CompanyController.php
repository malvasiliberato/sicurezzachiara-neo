<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\Tenant;
use App\Support\CurrentTenantResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(Request $request, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());

        $companies = $tenant->companies()
            ->withCount('sites')
            ->orderBy('name')
            ->get();

        return Inertia::render('sicurezzachiara/companies/Index', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'companies' => $companies,
            'summary' => [
                'companiesCount' => $companies->count(),
                'sitesCount' => (int) $companies->sum('sites_count'),
            ],
        ]);
    }

    public function create(Request $request, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());

        return Inertia::render('sicurezzachiara/companies/Create', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
        ]);
    }

    public function store(StoreCompanyRequest $request, CurrentTenantResolver $tenantResolver): RedirectResponse
    {
        $tenant = $tenantResolver->resolve($request->user());

        $company = $tenant->companies()->create($request->validated());

        return redirect()
            ->route('companies.show', $company)
            ->with('success', 'Azienda creata correttamente.');
    }

    public function show(Request $request, Company $company, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());
        $company = $this->companyForTenant($tenant, $company);
        $company->load(['sites' => fn ($query) => $query->orderByDesc('is_headquarters')->orderBy('name')]);

        return Inertia::render('sicurezzachiara/companies/Show', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'company' => $company,
        ]);
    }

    public function edit(Request $request, Company $company, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());
        $company = $this->companyForTenant($tenant, $company);

        return Inertia::render('sicurezzachiara/companies/Edit', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'company' => $company,
        ]);
    }

    public function update(
        UpdateCompanyRequest $request,
        Company $company,
        CurrentTenantResolver $tenantResolver,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $company = $this->companyForTenant($tenant, $company);

        $company->update($request->validated());

        return redirect()
            ->route('companies.show', $company)
            ->with('success', 'Azienda aggiornata correttamente.');
    }

    private function companyForTenant(Tenant $tenant, Company $company): Company
    {
        abort_unless($company->tenant_id === $tenant->id, 404);

        return $company;
    }
}
