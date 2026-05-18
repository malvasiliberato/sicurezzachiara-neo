<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Tenant;
use App\Support\CompanyDvrReportBuilder;
use App\Support\CurrentTenantResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyDvrReportController extends Controller
{
    public function show(
        Request $request,
        Company $company,
        CurrentTenantResolver $tenantResolver,
        CompanyDvrReportBuilder $reportBuilder,
    ): Response {
        $tenant = $tenantResolver->resolve($request->user());
        $company = $this->companyForTenant($tenant, $company);

        return Inertia::render('sicurezzachiara/reports/CompanyDvr', $reportBuilder->build($tenant, $company));
    }

    private function companyForTenant(Tenant $tenant, Company $company): Company
    {
        abort_unless($company->tenant_id === $tenant->id, 404);

        return $company;
    }
}
