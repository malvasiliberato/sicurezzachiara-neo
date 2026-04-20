<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanySiteRequest;
use App\Http\Requests\UpdateCompanySiteRequest;
use App\Models\Company;
use App\Models\CompanySite;
use App\Models\Tenant;
use App\Support\CurrentTenantResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanySiteController extends Controller
{
    public function store(
        StoreCompanySiteRequest $request,
        Company $company,
        CurrentTenantResolver $tenantResolver,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $company = $this->companyForTenant($tenant, $company);
        $validated = $request->validated();

        if ($validated['is_headquarters']) {
            $company->sites()->update(['is_headquarters' => false]);
        }

        $company->sites()->create($validated);

        return redirect()
            ->route('companies.show', $company)
            ->with('success', 'Sede aggiunta correttamente.');
    }

    public function edit(
        Request $request,
        Company $company,
        CompanySite $site,
        CurrentTenantResolver $tenantResolver,
    ): Response {
        $tenant = $tenantResolver->resolve($request->user());
        $company = $this->companyForTenant($tenant, $company);
        $site = $this->siteForCompany($company, $site);

        return Inertia::render('sicurezzachiara/company-sites/Edit', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'company' => $company->only(['id', 'name']),
            'site' => $site,
        ]);
    }

    public function update(
        UpdateCompanySiteRequest $request,
        Company $company,
        CompanySite $site,
        CurrentTenantResolver $tenantResolver,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $company = $this->companyForTenant($tenant, $company);
        $site = $this->siteForCompany($company, $site);
        $validated = $request->validated();

        if ($validated['is_headquarters']) {
            $company->sites()
                ->whereKeyNot($site->id)
                ->update(['is_headquarters' => false]);
        }

        $site->update($validated);

        return redirect()
            ->route('companies.show', $company)
            ->with('success', 'Sede aggiornata correttamente.');
    }

    private function companyForTenant(Tenant $tenant, Company $company): Company
    {
        abort_unless($company->tenant_id === $tenant->id, 404);

        return $company;
    }

    private function siteForCompany(Company $company, CompanySite $site): CompanySite
    {
        abort_unless($site->company_id === $company->id, 404);

        return $site;
    }
}
