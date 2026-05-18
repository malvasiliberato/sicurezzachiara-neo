<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureTenantCanManageData;
use App\Http\Requests\StoreEquipmentAssetRequest;
use App\Http\Requests\UpdateEquipmentAssetRequest;
use App\Models\Company;
use App\Models\CompanySite;
use App\Models\EquipmentAsset;
use App\Models\EquipmentType;
use App\Models\RiskMeasure;
use App\Models\RiskProfileItem;
use App\Models\Tenant;
use App\Models\Worker;
use App\Support\CurrentTenantResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EquipmentAssetController extends Controller
{
    public function __construct()
    {
        $this->middleware(EnsureTenantCanManageData::class)->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());
        $selectedCompany = null;

        $assetsQuery = $tenant->equipmentAssets()
            ->with([
                'company:id,name',
                'site:id,name',
                'equipmentType:id,name,source',
            ])
            ->withCount('workerExposures')
            ->orderBy('name');

        if ($request->filled('company_id')) {
            $selectedCompany = $this->companyForTenant(
                $tenant,
                Company::query()->findOrFail($request->integer('company_id')),
            );

            $assetsQuery->where('company_id', $selectedCompany->id);
        }

        $assets = $assetsQuery->get();

        return Inertia::render('sicurezzachiara/equipment-assets/Index', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'assets' => $assets,
            'summary' => [
                'totalCount' => $assets->count(),
                'activeCount' => $assets->where('status', 'active')->count(),
                'companiesCount' => $assets->pluck('company_id')->unique()->count(),
            ],
            'companyContext' => $selectedCompany ? [
                'id' => $selectedCompany->id,
                'name' => $selectedCompany->name,
                'showRoute' => route('companies.show', $selectedCompany),
                'configureRoute' => route('companies.edit', $selectedCompany),
                'createRoute' => route('equipment-assets.create', ['company' => $selectedCompany->id]),
            ] : null,
        ]);
    }

    public function create(Request $request, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());
        $selectedCompanyId = $request->integer('company');
        $selectedSiteId = $request->integer('site');

        return Inertia::render('sicurezzachiara/equipment-assets/Create', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'formOptions' => $this->formOptions($tenant),
            'defaults' => [
                'company_id' => $this->defaultCompanyId($tenant, $selectedCompanyId),
                'company_site_id' => $this->defaultSiteId($tenant, $selectedCompanyId, $selectedSiteId),
            ],
        ]);
    }

    public function store(
        StoreEquipmentAssetRequest $request,
        CurrentTenantResolver $tenantResolver,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $validated = $request->validated();

        $company = $this->companyForTenant($tenant, Company::query()->findOrFail($validated['company_id']));
        $equipmentType = $this->resolveEquipmentTypeForTenant($tenant, $validated);

        $this->ensureSiteBelongsToCompany($company, $validated['company_site_id'] ?? null);

        $asset = $company->equipmentAssets()->create([
            ...collect($validated)->except(['custom_equipment_type_name'])->all(),
            'equipment_type_id' => $equipmentType->id,
        ]);

        if ($request->boolean('redirect_to_company_edit')) {
            return redirect()
                ->route('companies.edit', $company)
                ->with('success', 'Macchinario registrato correttamente.');
        }

        if ($request->boolean('redirect_to_company')) {
            return redirect()
                ->route('companies.show', $company)
                ->with('success', 'Macchinario registrato correttamente.');
        }

        return redirect()
            ->route('equipment-assets.show', $asset)
            ->with('success', 'Macchinario registrato correttamente.');
    }

    public function show(
        Request $request,
        EquipmentAsset $equipmentAsset,
        CurrentTenantResolver $tenantResolver,
    ): Response {
        $tenant = $tenantResolver->resolve($request->user());
        $equipmentAsset = $this->assetForTenant($tenant, $equipmentAsset);
        $equipmentAsset->load([
            'company:id,name',
            'site:id,name,city,province',
            'equipmentType' => fn ($query) => $query
                ->select(['id', 'name', 'source', 'code'])
                ->with(['riskSourceLinks.riskCatalogItem.category']),
            'workerExposures' => fn ($query) => $query
                ->with(['worker.company:id,name', 'worker:id,company_id,first_name,last_name'])
                ->orderByDesc('is_primary')
                ->orderBy('id'),
        ]);

        return Inertia::render('sicurezzachiara/equipment-assets/Show', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'asset' => $equipmentAsset,
            'contextBridge' => $this->buildContextBridge($equipmentAsset),
            'governanceContext' => $this->buildGovernanceContext($equipmentAsset),
        ]);
    }

    public function edit(
        Request $request,
        EquipmentAsset $equipmentAsset,
        CurrentTenantResolver $tenantResolver,
    ): Response {
        $tenant = $tenantResolver->resolve($request->user());
        $equipmentAsset = $this->assetForTenant($tenant, $equipmentAsset);

        return Inertia::render('sicurezzachiara/equipment-assets/Edit', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'asset' => $equipmentAsset,
            'formOptions' => $this->formOptions($tenant),
        ]);
    }

    public function update(
        UpdateEquipmentAssetRequest $request,
        EquipmentAsset $equipmentAsset,
        CurrentTenantResolver $tenantResolver,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $equipmentAsset = $this->assetForTenant($tenant, $equipmentAsset);
        $validated = $request->validated();

        $company = $this->companyForTenant($tenant, Company::query()->findOrFail($validated['company_id']));
        $equipmentType = $this->resolveEquipmentTypeForTenant($tenant, $validated);

        $this->ensureSiteBelongsToCompany($company, $validated['company_site_id'] ?? null);

        $equipmentAsset->update([
            ...collect($validated)->except(['custom_equipment_type_name'])->all(),
            'equipment_type_id' => $equipmentType->id,
        ]);

        if ($request->boolean('redirect_to_company_edit')) {
            return redirect()
                ->route('companies.edit', $company)
                ->with('success', 'Macchinario aggiornato correttamente.');
        }

        if ($request->boolean('redirect_to_company')) {
            return redirect()
                ->route('companies.show', $company)
                ->with('success', 'Macchinario aggiornato correttamente.');
        }

        return redirect()
            ->route('equipment-assets.show', $equipmentAsset)
            ->with('success', 'Macchinario aggiornato correttamente.');
    }

    public function destroy(
        Request $request,
        EquipmentAsset $equipmentAsset,
        CurrentTenantResolver $tenantResolver,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $equipmentAsset = $this->assetForTenant($tenant, $equipmentAsset);
        $equipmentAsset->loadMissing([
            'company:id,name',
            'workerExposures.worker:id,first_name,last_name',
        ]);

        $workerNames = $equipmentAsset->workerExposures
            ->map(fn ($exposure) => trim(($exposure->worker->first_name ?? '').' '.($exposure->worker->last_name ?? '')))
            ->filter(fn ($value) => filled($value))
            ->values();

        if ($workerNames->isNotEmpty()) {
            $redirect = $request->boolean('redirect_to_company_edit')
                ? redirect()->route('companies.edit', $equipmentAsset->company)
                : redirect()->route('equipment-assets.show', $equipmentAsset);

            return $redirect->with('error', [
                'title' => 'Macchinario ancora in uso',
                'message' => 'Prima di cancellare il macchinario devi riallineare le esposizioni ancora collegate.',
                'references' => [[
                    'key' => 'workers',
                    'label' => 'Lavoratori',
                    'count' => $workerNames->count(),
                    'items' => $workerNames->take(3)->all(),
                    'has_more' => $workerNames->count() > 3,
                ]],
            ]);
        }

        $company = $equipmentAsset->company;
        $equipmentAsset->delete();

        if ($request->boolean('redirect_to_company_edit')) {
            return redirect()
                ->route('companies.edit', $company)
                ->with('success', 'Macchinario cancellato correttamente.');
        }

        return redirect()
            ->route('equipment-assets.index')
            ->with('success', 'Macchinario cancellato correttamente.');
    }

    private function formOptions(Tenant $tenant): array
    {
        $companies = $tenant->companies()
            ->with(['sites' => fn ($query) => $query->orderByDesc('is_headquarters')->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name']);

        $equipmentTypes = EquipmentType::query()
            ->where(function ($query) use ($tenant) {
                $query->where('source', EquipmentType::SOURCE_CORE)
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
            'equipmentTypes' => $equipmentTypes->map(fn (EquipmentType $equipmentType) => [
                'id' => $equipmentType->id,
                'name' => $equipmentType->name,
                'source' => $equipmentType->source,
            ])->values(),
        ];
    }

    private function companyForTenant(Tenant $tenant, Company $company): Company
    {
        abort_unless($company->tenant_id === $tenant->id, 404);

        return $company;
    }

    private function equipmentTypeForTenant(Tenant $tenant, EquipmentType $equipmentType): EquipmentType
    {
        abort_unless(
            $equipmentType->source === EquipmentType::SOURCE_CORE || $equipmentType->tenant_id === $tenant->id,
            404,
        );

        return $equipmentType;
    }

    private function resolveEquipmentTypeForTenant(Tenant $tenant, array $validated): EquipmentType
    {
        if (! empty($validated['equipment_type_id'])) {
            return $this->equipmentTypeForTenant(
                $tenant,
                EquipmentType::query()->findOrFail($validated['equipment_type_id']),
            );
        }

        $customName = Str::of($validated['custom_equipment_type_name'] ?? '')
            ->squish()
            ->toString();

        if ($customName === '') {
            throw ValidationException::withMessages([
                'custom_equipment_type_name' => 'Tipologia macchinario non valida.',
            ]);
        }

        $equipmentType = EquipmentType::query()
            ->where('tenant_id', $tenant->id)
            ->where('source', EquipmentType::SOURCE_TENANT)
            ->whereRaw('LOWER(name) = ?', [Str::lower($customName)])
            ->first();

        if ($equipmentType !== null) {
            if (! $equipmentType->is_active) {
                $equipmentType->forceFill(['is_active' => true])->save();
            }

            return $equipmentType;
        }

        return $tenant->equipmentTypes()->create([
            'source' => EquipmentType::SOURCE_TENANT,
            'code' => null,
            'name' => $customName,
            'description' => null,
            'is_active' => true,
        ]);
    }

    private function ensureSiteBelongsToCompany(Company $company, ?int $siteId): void
    {
        if ($siteId === null) {
            return;
        }

        $siteBelongsToCompany = $company->sites()->whereKey($siteId)->exists();

        if (! $siteBelongsToCompany) {
            throw ValidationException::withMessages([
                'company_site_id' => 'La sede del macchinario deve appartenere alla stessa azienda selezionata.',
            ]);
        }
    }

    private function assetForTenant(Tenant $tenant, EquipmentAsset $equipmentAsset): EquipmentAsset
    {
        $equipmentAsset->loadMissing('company');

        abort_unless(
            $equipmentAsset->company !== null && $equipmentAsset->company->tenant_id === $tenant->id,
            404,
        );

        return $equipmentAsset;
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

    private function buildContextBridge(EquipmentAsset $asset): array
    {
        $company = $asset->company;
        $suggestedRiskCount = $asset->equipmentType?->riskSourceLinks?->count() ?? 0;
        $linkedWorkerCount = $asset->workerExposures?->count() ?? 0;

        return [
            'sourceLabel' => 'Macchinario',
            'companyName' => $company?->name,
            'narrative' => $suggestedRiskCount > 0
                ? 'La tipologia di questo macchinario suggerisce i rischi da governare nel profilo aziendale. Da qui puoi passare subito al profilo rischio o ai registri misure.'
                : 'Questo macchinario e\' censito nel contesto aziendale. Se la tipologia non suggerisce ancora rischi espliciti, il governo resta comunque nel profilo rischio e nei registri della stessa azienda.',
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
                'sectionRoute' => $company ? route('equipment-assets.index', ['company_id' => $company->id]) : null,
            ],
        ];
    }

    private function buildGovernanceContext(EquipmentAsset $asset): array
    {
        $company = $asset->company;
        $today = CarbonImmutable::today();
        $riskIds = collect($asset->equipmentType?->riskSourceLinks ?? [])
            ->pluck('risk_catalog_item_id')
            ->filter()
            ->unique()
            ->values();
        $linkedWorkerIds = $asset->workerExposures
            ->pluck('worker_id')
            ->filter()
            ->unique()
            ->values();
        $workerLabels = $asset->workerExposures
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
