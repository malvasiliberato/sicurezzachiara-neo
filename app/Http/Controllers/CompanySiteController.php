<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureTenantCanManageData;
use App\Http\Requests\StoreCompanySiteRequest;
use App\Http\Requests\UpdateCompanySiteRequest;
use App\Models\Company;
use App\Models\CompanySite;
use App\Models\Tenant;
use App\Support\AuditLogger;
use App\Support\CurrentTenantResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CompanySiteController extends Controller
{
    public function __construct()
    {
        $this->middleware(EnsureTenantCanManageData::class)->only(['store', 'edit', 'update', 'destroy']);
    }

    public function store(
        StoreCompanySiteRequest $request,
        Company $company,
        CurrentTenantResolver $tenantResolver,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $company = $this->companyForTenant($tenant, $company);
        $validated = $request->validated();

        if ($validated['is_headquarters']) {
            $company->sites()->update(['is_headquarters' => false]);
        }

        $site = $company->sites()->create($validated);
        $auditLogger->log(
            $tenant,
            $request->user(),
            'company_site.created',
            $site,
            'Creata sede '.$site->name.' per '.$company->name,
            [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'site_name' => $site->name,
            ],
        );

        if ($request->boolean('redirect_to_company_edit')) {
            return redirect()
                ->route('companies.edit', $company)
                ->with('success', 'Sede aggiunta correttamente.');
        }

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
            'comuniConfig' => [
                'searchRoute' => route('comuni.search'),
                'initialOption' => $this->buildComuneOption($site->city, $site->province),
            ],
        ]);
    }

    public function update(
        UpdateCompanySiteRequest $request,
        Company $company,
        CompanySite $site,
        CurrentTenantResolver $tenantResolver,
        AuditLogger $auditLogger,
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
        $auditLogger->log(
            $tenant,
            $request->user(),
            'company_site.updated',
            $site,
            'Aggiornata sede '.$site->name.' per '.$company->name,
            [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'site_name' => $site->name,
            ],
        );

        $redirectRoute = $request->boolean('redirect_to_company_edit')
            ? route('companies.edit', $company)
            : route('companies.show', $company);

        return redirect()
            ->to($redirectRoute)
            ->with('success', 'Sede aggiornata correttamente.');
    }

    public function destroy(
        Request $request,
        Company $company,
        CompanySite $site,
        CurrentTenantResolver $tenantResolver,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $company = $this->companyForTenant($tenant, $company);
        $site = $this->siteForCompany($company, $site);

        $dependencies = $this->collectSiteDependencies($site);

        if ($dependencies['has_links']) {
            return redirect()
                ->route($request->boolean('redirect_to_company_edit') ? 'companies.edit' : 'companies.show', $company)
                ->with('error', [
                    'title' => 'Sede ancora in uso',
                    'message' => 'Prima di cancellare la sede devi riallineare i collegamenti ancora presenti.',
                    'references' => $dependencies['references'],
                ]);
        }

        $siteName = $site->name;
        $site->delete();

        $auditLogger->log(
            $tenant,
            $request->user(),
            'company_site.deleted',
            $company,
            'Cancellata sede '.$siteName.' per '.$company->name,
            [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'site_name' => $siteName,
            ],
        );

        return redirect()
            ->route($request->boolean('redirect_to_company_edit') ? 'companies.edit' : 'companies.show', $company)
            ->with('success', 'Sede cancellata correttamente.');
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

    private function buildComuneOption(?string $city, ?string $province): ?array
    {
        if (! $city || ! $province) {
            return null;
        }

        $entry = \Illuminate\Support\Facades\DB::table('comuni_elenco')
            ->select([
                'comune',
                'provincia',
                'provincia_esteso',
                \Illuminate\Support\Facades\DB::raw('MIN(istat) as istat'),
                \Illuminate\Support\Facades\DB::raw("ARRAY_AGG(DISTINCT cap ORDER BY cap) as caps"),
            ])
            ->where('comune', $city)
            ->where('provincia', $province)
            ->groupBy('comune', 'provincia', 'provincia_esteso')
            ->first();

        if (! $entry) {
            return null;
        }

        $caps = collect(explode(',', trim((string) ($entry->caps ?? ''), '{}')))
            ->map(fn ($value) => trim($value, '" '))
            ->flatMap(fn ($value) => $this->expandCapValue($value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'id' => (int) $entry->istat,
            'city' => $entry->comune,
            'province' => $entry->provincia,
            'provinceLabel' => $entry->provincia_esteso,
            'caps' => $caps,
            'label' => $entry->comune.' ('.$entry->provincia.')',
            'capLabel' => $caps === [] ? '' : (count($caps) === 1 ? $caps[0] : $caps[0].' - '.$caps[array_key_last($caps)]),
        ];
    }

    private function collectSiteDependencies(CompanySite $site): array
    {
        $site->loadMissing([
            'workplaces:id,company_site_id,name',
            'equipmentAssets:id,company_site_id,name',
            'primaryWorkers:id,primary_site_id,first_name,last_name',
        ]);

        $references = collect([
            $this->buildDependencyReference(
                'Luoghi',
                'luoghi',
                $site->workplaces->pluck('name'),
            ),
            $this->buildDependencyReference(
                'Macchinari',
                'macchinari',
                $site->equipmentAssets->pluck('name'),
            ),
            $this->buildDependencyReference(
                'Lavoratori',
                'lavoratori',
                $site->primaryWorkers->map(fn ($worker) => trim($worker->first_name.' '.$worker->last_name)),
            ),
        ])->filter()->values()->all();

        return [
            'has_links' => $references !== [],
            'references' => $references,
        ];
    }

    private function buildDependencyReference(string $label, string $key, Collection $items): ?array
    {
        $names = $items
            ->filter(fn ($value) => filled($value))
            ->values();

        if ($names->isEmpty()) {
            return null;
        }

        return [
            'key' => $key,
            'label' => $label,
            'count' => $names->count(),
            'items' => $names->take(3)->all(),
            'has_more' => $names->count() > 3,
        ];
    }

    private function expandCapValue(string $cap): array
    {
        if ($cap === '') {
            return [];
        }

        if (! str_contains($cap, '-')) {
            return [$cap];
        }

        [$from, $to] = array_map('trim', explode('-', $cap, 2));

        if (! ctype_digit($from) || ! ctype_digit($to)) {
            return [$cap];
        }

        $start = (int) $from;
        $end = (int) $to;

        if ($start > $end || ($end - $start) > 200) {
            return [$cap];
        }

        return collect(range($start, $end))
            ->map(fn ($number) => str_pad((string) $number, max(strlen($from), strlen($to)), '0', STR_PAD_LEFT))
            ->all();
    }
}
