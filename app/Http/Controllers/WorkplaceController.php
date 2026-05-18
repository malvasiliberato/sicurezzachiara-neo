<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureTenantCanManageData;
use App\Http\Requests\StoreWorkplaceRequest;
use App\Http\Requests\UpdateWorkplaceRequest;
use App\Models\Company;
use App\Models\CompanySite;
use App\Models\RiskMeasure;
use App\Models\RiskProfileItem;
use App\Models\Tenant;
use App\Models\Worker;
use App\Models\Workplace;
use App\Models\WorkplaceType;
use App\Support\CurrentTenantResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WorkplaceController extends Controller
{
    public function __construct()
    {
        $this->middleware(EnsureTenantCanManageData::class)->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());
        $selectedCompany = null;

        $workplacesQuery = Workplace::query()
            ->whereHas('site.company', fn ($query) => $query->where('tenant_id', $tenant->id))
            ->with([
                'site.company:id,name',
                'site:id,company_id,name',
                'workplaceType:id,name,source',
            ])
            ->withCount('workerExposures')
            ->orderBy('name');

        if ($request->filled('company_id')) {
            $selectedCompany = $this->companyForTenant(
                $tenant,
                Company::query()->findOrFail($request->integer('company_id')),
            );

            $workplacesQuery->whereHas('site', fn ($query) => $query->where('company_id', $selectedCompany->id));
        }

        $workplaces = $workplacesQuery->get();

        return Inertia::render('sicurezzachiara/workplaces/Index', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'workplaces' => $workplaces,
            'summary' => [
                'totalCount' => $workplaces->count(),
                'activeCount' => $workplaces->where('status', 'active')->count(),
                'sitesCount' => $workplaces->pluck('company_site_id')->unique()->count(),
            ],
            'companyContext' => $selectedCompany ? [
                'id' => $selectedCompany->id,
                'name' => $selectedCompany->name,
                'showRoute' => route('companies.show', $selectedCompany),
                'configureRoute' => route('companies.edit', $selectedCompany),
                'createRoute' => route('workplaces.create', ['company' => $selectedCompany->id]),
            ] : null,
        ]);
    }

    public function create(Request $request, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());
        $selectedCompanyId = $request->integer('company');
        $selectedSiteId = $request->integer('site');

        return Inertia::render('sicurezzachiara/workplaces/Create', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'formOptions' => $this->formOptions($tenant),
            'defaults' => [
                'company_id' => $this->defaultCompanyId($tenant, $selectedCompanyId),
                'company_site_id' => $this->defaultSiteId($tenant, $selectedCompanyId, $selectedSiteId),
            ],
        ]);
    }

    public function store(StoreWorkplaceRequest $request, CurrentTenantResolver $tenantResolver): RedirectResponse
    {
        $tenant = $tenantResolver->resolve($request->user());
        $validated = $request->validated();

        $site = $this->siteForTenant($tenant, CompanySite::query()->findOrFail($validated['company_site_id']));
        $workplaceType = $this->resolveWorkplaceTypeForTenant($tenant, $validated);
        $payload = $this->normalizeOperationalNotes($validated);

        $workplace = $site->workplaces()->create([
            ...collect($payload)->except(['custom_workplace_type_name'])->all(),
            'workplace_type_id' => $workplaceType->id,
        ]);

        if ($request->boolean('redirect_to_company_edit')) {
            return redirect()
                ->route('companies.edit', $site->company_id)
                ->with('success', 'Luogo registrato correttamente.');
        }

        if ($request->boolean('redirect_to_company')) {
            return redirect()
                ->route('companies.show', $site->company_id)
                ->with('success', 'Luogo registrato correttamente.');
        }

        return redirect()
            ->route('workplaces.show', $workplace)
            ->with('success', 'Luogo registrato correttamente.');
    }

    public function show(Request $request, Workplace $workplace, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());
        $workplace = $this->workplaceForTenant($tenant, $workplace);
        $workplace->load([
            'site.company:id,name',
            'site:id,company_id,name,city,province',
            'workplaceType' => fn ($query) => $query
                ->select(['id', 'name', 'source', 'code'])
                ->with(['riskSourceLinks.riskCatalogItem.category']),
            'workerExposures' => fn ($query) => $query
                ->with(['worker.company:id,name', 'worker:id,company_id,first_name,last_name'])
                ->orderByDesc('is_primary')
                ->orderBy('id'),
        ]);

        return Inertia::render('sicurezzachiara/workplaces/Show', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'workplace' => $workplace,
            'contextBridge' => $this->buildContextBridge($workplace),
            'governanceContext' => $this->buildGovernanceContext($workplace),
        ]);
    }

    public function edit(Request $request, Workplace $workplace, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());
        $workplace = $this->workplaceForTenant($tenant, $workplace);
        $workplace->load('site:id,company_id,name');

        return Inertia::render('sicurezzachiara/workplaces/Edit', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'workplace' => $workplace,
            'formOptions' => $this->formOptions($tenant),
        ]);
    }

    public function update(
        UpdateWorkplaceRequest $request,
        Workplace $workplace,
        CurrentTenantResolver $tenantResolver,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $workplace = $this->workplaceForTenant($tenant, $workplace);
        $validated = $request->validated();

        $site = $this->siteForTenant($tenant, CompanySite::query()->findOrFail($validated['company_site_id']));
        $workplaceType = $this->resolveWorkplaceTypeForTenant($tenant, $validated);
        $payload = $this->normalizeOperationalNotes($validated);

        $workplace->update([
            ...collect($payload)->except(['custom_workplace_type_name'])->all(),
            'workplace_type_id' => $workplaceType->id,
        ]);

        if ($request->boolean('redirect_to_company_edit')) {
            return redirect()
                ->route('companies.edit', $site->company_id)
                ->with('success', 'Luogo aggiornato correttamente.');
        }

        return redirect()
            ->route('workplaces.show', $workplace)
            ->with('success', 'Luogo aggiornato correttamente.');
    }

    public function destroy(
        Request $request,
        Workplace $workplace,
        CurrentTenantResolver $tenantResolver,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $workplace = $this->workplaceForTenant($tenant, $workplace);
        $workplace->loadMissing([
            'site.company',
            'workerExposures.worker:id,first_name,last_name',
        ]);

        $references = $this->buildWorkerExposureReferences($workplace->workerExposures);

        if ($references !== null) {
            return redirect()
                ->route($request->boolean('redirect_to_company_edit') ? 'companies.edit' : 'workplaces.show', $request->boolean('redirect_to_company_edit') ? $workplace->site->company : $workplace)
                ->with('error', [
                    'title' => 'Luogo ancora in uso',
                    'message' => 'Prima di cancellare il luogo devi riallineare le assegnazioni dei lavoratori ancora collegate.',
                    'references' => [$references],
                ]);
        }

        $company = $workplace->site->company;
        $workplace->delete();

        if ($request->boolean('redirect_to_company_edit')) {
            return redirect()
                ->route('companies.edit', $company)
                ->with('success', 'Luogo cancellato correttamente.');
        }

        return redirect()
            ->route('workplaces.index')
            ->with('success', 'Luogo cancellato correttamente.');
    }

    private function formOptions(Tenant $tenant): array
    {
        $companies = $tenant->companies()
            ->with(['sites' => fn ($query) => $query->orderByDesc('is_headquarters')->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name']);

        $workplaceTypes = WorkplaceType::query()
            ->where(function ($query) use ($tenant) {
                $query->where('source', WorkplaceType::SOURCE_CORE)
                    ->orWhere('tenant_id', $tenant->id);
            })
            ->where('is_active', true)
            ->orderByRaw("case when source = 'tenant' then 0 else 1 end")
            ->orderBy('name')
            ->get(['id', 'name', 'source']);

        return [
            'companies' => $companies->map(fn (Company $company) => [
                'id' => $company->id,
                'name' => $company->name,
            ])->values(),
            'sitesByCompany' => $companies->mapWithKeys(fn (Company $company) => [
                (string) $company->id => $company->sites->map(fn (CompanySite $site) => [
                    'id' => $site->id,
                    'name' => $site->name,
                    'is_headquarters' => $site->is_headquarters,
                ])->values(),
            ]),
            'workplaceTypes' => $workplaceTypes->map(fn (WorkplaceType $workplaceType) => [
                'id' => $workplaceType->id,
                'name' => $workplaceType->name,
                'source' => $workplaceType->source,
            ])->values(),
        ];
    }

    private function siteForTenant(Tenant $tenant, CompanySite $site): CompanySite
    {
        $site->loadMissing('company');
        abort_unless($site->company !== null && $site->company->tenant_id === $tenant->id, 404);

        return $site;
    }

    private function companyForTenant(Tenant $tenant, Company $company): Company
    {
        abort_unless($company->tenant_id === $tenant->id, 404);

        return $company;
    }

    private function workplaceTypeForTenant(Tenant $tenant, WorkplaceType $workplaceType): WorkplaceType
    {
        abort_unless(
            $workplaceType->source === WorkplaceType::SOURCE_CORE || $workplaceType->tenant_id === $tenant->id,
            404,
        );

        return $workplaceType;
    }

    private function resolveWorkplaceTypeForTenant(Tenant $tenant, array $validated): WorkplaceType
    {
        if (! empty($validated['workplace_type_id'])) {
            return $this->workplaceTypeForTenant(
                $tenant,
                WorkplaceType::query()->findOrFail($validated['workplace_type_id']),
            );
        }

        $customName = Str::of($validated['custom_workplace_type_name'] ?? '')
            ->squish()
            ->toString();

        abort_if($customName === '', 422, 'Tipologia luogo non valida.');

        $workplaceType = WorkplaceType::query()
            ->where('tenant_id', $tenant->id)
            ->where('source', WorkplaceType::SOURCE_TENANT)
            ->whereRaw('LOWER(name) = ?', [Str::lower($customName)])
            ->first();

        if ($workplaceType !== null) {
            if (! $workplaceType->is_active) {
                $workplaceType->forceFill(['is_active' => true])->save();
            }

            return $workplaceType;
        }

        return $tenant->workplaceTypes()->create([
            'source' => WorkplaceType::SOURCE_TENANT,
            'code' => null,
            'name' => $customName,
            'description' => null,
            'is_active' => true,
        ]);
    }

    private function normalizeOperationalNotes(array $validated): array
    {
        $normalizedText = Str::of($validated['notes'] ?? $validated['description'] ?? '')
            ->squish()
            ->toString();

        $value = $normalizedText !== '' ? $normalizedText : null;

        $validated['description'] = $value;
        $validated['notes'] = $value;

        return $validated;
    }

    private function workplaceForTenant(Tenant $tenant, Workplace $workplace): Workplace
    {
        $workplace->loadMissing('site.company');
        abort_unless($workplace->site !== null && $workplace->site->company?->tenant_id === $tenant->id, 404);

        return $workplace;
    }

    private function defaultCompanyId(Tenant $tenant, ?int $selectedCompanyId): ?int
    {
        if ($selectedCompanyId === null) {
            return null;
        }

        return $tenant->companies()->whereKey($selectedCompanyId)->exists()
            ? $selectedCompanyId
            : null;
    }

    private function defaultSiteId(Tenant $tenant, ?int $selectedCompanyId, ?int $selectedSiteId): ?int
    {
        if ($selectedCompanyId === null || $selectedSiteId === null) {
            return null;
        }

        return CompanySite::query()
            ->whereKey($selectedSiteId)
            ->where('company_id', $selectedCompanyId)
            ->whereHas('company', fn ($query) => $query->where('tenant_id', $tenant->id))
            ->exists()
                ? $selectedSiteId
                : null;
    }

    private function buildWorkerExposureReferences(Collection $exposures): ?array
    {
        $workers = $exposures
            ->map(fn ($exposure) => trim(($exposure->worker->first_name ?? '').' '.($exposure->worker->last_name ?? '')))
            ->filter(fn ($value) => filled($value))
            ->values();

        if ($workers->isEmpty()) {
            return null;
        }

        return [
            'key' => 'lavoratori',
            'label' => 'Lavoratori',
            'count' => $workers->count(),
            'items' => $workers->take(3)->all(),
            'has_more' => $workers->count() > 3,
        ];
    }

    private function buildContextBridge(Workplace $workplace): array
    {
        $company = $workplace->site?->company;
        $suggestedRiskCount = $workplace->workplaceType?->riskSourceLinks?->count() ?? 0;
        $linkedWorkerCount = $workplace->workerExposures?->count() ?? 0;

        return [
            'sourceLabel' => 'Luogo',
            'companyName' => $company?->name,
            'narrative' => $suggestedRiskCount > 0
                ? 'La tipologia di questo luogo suggerisce i rischi da presidiare nel profilo aziendale. Da qui puoi passare subito alla lettura del rischio e ai registri misure.'
                : 'Questo luogo e\' censito nel contesto aziendale. Se la tipologia non suggerisce ancora rischi espliciti, il governo resta comunque nel profilo rischio e nei registri della stessa azienda.',
            'stats' => [
                'suggestedRisks' => $suggestedRiskCount,
                'linkedWorkers' => $linkedWorkerCount,
            ],
            'actions' => [
                'companyRoute' => $company ? route('companies.show', $company) : null,
                'riskProfileRoute' => $company ? route('companies.risk-profile.show', [
                    'company' => $company->id,
                    'origin' => 'company_show',
                ]) : null,
                'registryRoute' => $company ? route('measure-registries.index', [
                    'company_id' => $company->id,
                    'scope' => 'attention',
                    'origin' => 'company_show',
                ]) : null,
                'sectionRoute' => $company ? route('workplaces.index', ['company_id' => $company->id]) : null,
            ],
        ];
    }

    private function buildGovernanceContext(Workplace $workplace): array
    {
        $company = $workplace->site?->company;
        $today = CarbonImmutable::today();
        $riskIds = collect($workplace->workplaceType?->riskSourceLinks ?? [])
            ->pluck('risk_catalog_item_id')
            ->filter()
            ->unique()
            ->values();
        $linkedWorkerIds = $workplace->workerExposures
            ->pluck('worker_id')
            ->filter()
            ->unique()
            ->values();
        $workerLabels = $workplace->workerExposures
            ->mapWithKeys(fn ($exposure) => [$exposure->worker_id => $exposure->worker?->full_name])
            ->filter();

        if ($company === null || $riskIds->isEmpty()) {
            return [
                'summary' => [
                    'activeRisks' => 0,
                    'totalMeasures' => 0,
                    'overdueMeasures' => 0,
                    'toVerifyMeasures' => 0,
                    'followUpsOpen' => 0,
                ],
                'previewMeasures' => [],
            ];
        }

        $profileItems = RiskProfileItem::query()
            ->whereIn('risk_catalog_item_id', $riskIds)
            ->where(function ($query) use ($company, $linkedWorkerIds) {
                $query
                    ->where(function ($companyQuery) use ($company) {
                        $companyQuery->where('profileable_type', Company::class)
                            ->where('profileable_id', $company->id);
                    });

                if ($linkedWorkerIds->isNotEmpty()) {
                    $query->orWhere(function ($workerQuery) use ($linkedWorkerIds) {
                        $workerQuery->where('profileable_type', Worker::class)
                            ->whereIn('profileable_id', $linkedWorkerIds);
                    });
                }
            })
            ->get();

        $measures = RiskMeasure::query()
            ->with('riskCatalogItem:id,name')
            ->whereIn('risk_catalog_item_id', $riskIds)
            ->where(function ($query) use ($company, $linkedWorkerIds) {
                $query
                    ->where(function ($companyQuery) use ($company) {
                        $companyQuery->where('profileable_type', Company::class)
                            ->where('profileable_id', $company->id);
                    });

                if ($linkedWorkerIds->isNotEmpty()) {
                    $query->orWhere(function ($workerQuery) use ($linkedWorkerIds) {
                        $workerQuery->where('profileable_type', Worker::class)
                            ->whereIn('profileable_id', $linkedWorkerIds);
                    });
                }
            })
            ->orderBy('due_date')
            ->orderBy('title')
            ->get();

        return [
            'summary' => [
                'activeRisks' => $profileItems->where('operational_status', RiskProfileItem::OPERATIONAL_STATUS_ACTIVE)->count(),
                'totalMeasures' => $measures->count(),
                'overdueMeasures' => $measures->filter(fn (RiskMeasure $measure) => $measure->due_date !== null
                    && $measure->status !== RiskMeasure::STATUS_IMPLEMENTED
                    && $measure->due_date->lt($today))->count(),
                'toVerifyMeasures' => $measures->where('status', RiskMeasure::STATUS_TO_VERIFY)->count(),
                'followUpsOpen' => $profileItems->filter(fn (RiskProfileItem $item) => $item->hasOpenFollowUp())->count(),
            ],
            'previewMeasures' => $measures
                ->take(5)
                ->map(fn (RiskMeasure $measure) => [
                    'id' => $measure->id,
                    'title' => $measure->title,
                    'status' => $measure->status,
                    'family' => $measure->family,
                    'dueDate' => $measure->due_date?->format('Y-m-d'),
                    'riskName' => $measure->riskCatalogItem?->name,
                    'scopeLabel' => $measure->profileable_type === Worker::class ? 'Lavoratore' : 'Azienda',
                    'contextLabel' => $measure->profileable_type === Worker::class
                        ? ($workerLabels->get($measure->profileable_id) ?: 'Lavoratore collegato')
                        : $company->name,
                ])
                ->values()
                ->all(),
        ];
    }
}
