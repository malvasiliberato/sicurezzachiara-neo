<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureTenantCanManageData;
use App\Http\Requests\StoreWorkerRequest;
use App\Http\Requests\UpdateWorkerRequest;
use App\Models\Company;
use App\Models\CompanySite;
use App\Models\EquipmentAsset;
use App\Models\JobRole;
use App\Models\Tenant;
use App\Models\Worker;
use App\Models\Workplace;
use App\Support\AuditLogger;
use App\Support\CoreStarterPackContextBuilder;
use App\Support\CurrentTenantResolver;
use App\Support\RiskEngineSnapshotBuilder;
use App\Support\RiskProfileBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class WorkerController extends Controller
{
    public function __construct()
    {
        $this->middleware(EnsureTenantCanManageData::class)->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());
        $selectedCompany = null;

        $workersQuery = $tenant->workers()
            ->with([
                'company:id,name',
                'primarySite:id,name',
                'jobRoleAssignments' => fn ($query) => $query
                    ->where('is_primary', true)
                    ->with('jobRole:id,name,source'),
            ])
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($request->filled('company_id')) {
            $selectedCompany = $this->companyForTenant(
                $tenant,
                Company::query()->findOrFail($request->integer('company_id')),
            );

            $workersQuery->where('company_id', $selectedCompany->id);
        }

        $workers = $workersQuery->get();

        return Inertia::render('sicurezzachiara/workers/Index', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'workers' => $workers,
            'summary' => [
                'workersCount' => $workers->count(),
                'activeCount' => $workers->where('status', 'active')->count(),
                'companiesCount' => $workers->pluck('company_id')->unique()->count(),
            ],
            'companyContext' => $selectedCompany ? [
                'id' => $selectedCompany->id,
                'name' => $selectedCompany->name,
                'showRoute' => route('companies.show', $selectedCompany),
                'configureRoute' => route('companies.edit', $selectedCompany),
                'createRoute' => route('workers.create', ['company' => $selectedCompany->id]),
                'riskProfileRoute' => route('companies.risk-profile.show', [
                    'company' => $selectedCompany,
                    'origin' => 'workers_index',
                ]),
                'registryRoute' => route('measure-registries.index', [
                    'company_id' => $selectedCompany->id,
                    'origin' => 'workers_index',
                    'scope' => 'attention',
                ]),
            ] : null,
        ]);
    }

    public function create(Request $request, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());
        $selectedCompanyId = $request->integer('company');
        $selectedCompany = $selectedCompanyId
            ? $this->companyForTenant($tenant, Company::query()->findOrFail($selectedCompanyId))
            : null;

        return Inertia::render('sicurezzachiara/workers/Create', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'formOptions' => $this->formOptions($tenant),
            'defaults' => [
                'company_id' => $this->defaultCompanyId($tenant, $selectedCompanyId),
            ],
            'companyContext' => $selectedCompany ? [
                'id' => $selectedCompany->id,
                'name' => $selectedCompany->name,
                'workersRoute' => route('workers.index', ['company_id' => $selectedCompany->id]),
                'showRoute' => route('companies.show', $selectedCompany),
            ] : null,
        ]);
    }

    public function store(
        StoreWorkerRequest $request,
        CurrentTenantResolver $tenantResolver,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $validated = $request->validated();
        $company = $this->companyForTenant($tenant, Company::query()->findOrFail($validated['company_id']));
        $jobRole = $this->resolveJobRoleForTenant($tenant, $validated['job_role_id'] ?? null);

        $this->ensurePrimarySiteBelongsToCompany($company, $validated['primary_site_id'] ?? null);

        $worker = $company->workers()->create(collect($validated)->except('job_role_id')->all());
        $this->syncPrimaryJobRole($worker, $jobRole, $validated['hire_date'] ?? null);
        $auditLogger->log(
            $tenant,
            $request->user(),
            'worker.created',
            $worker,
            'Creato lavoratore '.$worker->full_name,
            [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'worker_name' => $worker->full_name,
            ],
        );

        if ($request->boolean('redirect_to_company_edit')) {
            return redirect()
                ->route('companies.edit', $company)
                ->with('success', 'Lavoratore creato correttamente.');
        }

        if ($request->boolean('redirect_to_company')) {
            return redirect()
                ->route('companies.show', $company)
                ->with('success', 'Lavoratore creato correttamente.');
        }

        return redirect()
            ->route('workers.show', $worker)
            ->with('success', 'Lavoratore creato correttamente.');
    }

    public function show(
        Request $request,
        Worker $worker,
        CurrentTenantResolver $tenantResolver,
        CoreStarterPackContextBuilder $coreStarterPackContextBuilder,
        RiskProfileBuilder $riskProfileBuilder,
        RiskEngineSnapshotBuilder $riskEngineSnapshotBuilder,
    ): Response {
        $tenant = $tenantResolver->resolve($request->user());
        $worker = $this->workerForTenant($tenant, $worker);
        $riskProfileBuilder->rebuildWorker($worker);
        $worker->load([
            'company:id,name,industry,city,province',
            'primarySite:id,name,city,province,is_headquarters',
            'jobRoleAssignments' => fn ($query) => $query
                ->with([
                    'jobRole' => fn ($jobRoleQuery) => $jobRoleQuery
                        ->with(['riskSourceLinks.riskCatalogItem.category']),
                ])
                ->orderByDesc('is_primary')
                ->orderBy('assigned_on')
                ->orderBy('id'),
            'equipmentExposures' => fn ($query) => $query
                ->with([
                    'equipmentAsset.company:id,name',
                    'equipmentAsset:id,company_id,name,status,equipment_type_id',
                    'equipmentAsset.equipmentType' => fn ($equipmentTypeQuery) => $equipmentTypeQuery
                        ->with(['riskSourceLinks.riskCatalogItem.category']),
                ])
                ->orderByDesc('is_primary')
                ->orderBy('id'),
            'workplaceExposures' => fn ($query) => $query
                ->with([
                    'workplace.site.company:id,name',
                    'workplace.site:id,company_id,name',
                    'workplace:id,company_site_id,name,status,workplace_type_id',
                    'workplace.workplaceType' => fn ($workplaceTypeQuery) => $workplaceTypeQuery
                        ->with(['riskSourceLinks.riskCatalogItem.category']),
                ])
                ->orderByDesc('is_primary')
                ->orderBy('id'),
            'riskProfileItems' => fn ($query) => $query
                ->with('riskCatalogItem:id,name')
                ->select([
                    'id',
                    'profileable_type',
                    'profileable_id',
                    'risk_catalog_item_id',
                    'status',
                    'priority',
                    'final_priority',
                    'operational_status',
                    'review_due_at',
                    'follow_up_status',
                ]),
            'riskMeasures' => fn ($query) => $query
                ->with('riskCatalogItem:id,name')
                ->select([
                    'id',
                    'profileable_type',
                    'profileable_id',
                    'risk_catalog_item_id',
                    'family',
                    'title',
                    'status',
                    'due_date',
                ]),
        ]);
        $engine = $riskEngineSnapshotBuilder->buildForProfileable($worker);
        $workerBridge = $this->buildWorkerContextBridge($worker, $engine['summary']);

        $coreStarterPack = $coreStarterPackContextBuilder->buildForWorkerSources(
            $worker->jobRoleAssignments->pluck('jobRole')->filter()->values(),
            $worker->equipmentExposures->pluck('equipmentAsset.equipmentType')->filter()->values(),
            $worker->workplaceExposures->pluck('workplace.workplaceType')->filter()->values(),
        );

        return Inertia::render('sicurezzachiara/workers/Show', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'worker' => $worker,
            'coreStarterPack' => $coreStarterPack,
            'contextBridge' => $workerBridge,
            'governanceContext' => $this->buildWorkerGovernanceContext($worker),
            'jobRoleOptions' => $this->jobRoleOptions($tenant),
            'equipmentAssetOptions' => $this->equipmentAssetOptions($worker),
            'workplaceOptions' => $this->workplaceOptions($worker),
        ]);
    }

    public function edit(Request $request, Worker $worker, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());
        $worker = $this->workerForTenant($tenant, $worker);
        $worker->load([
            'jobRoleAssignments' => fn ($query) => $query
                ->where('is_primary', true)
                ->with('jobRole:id,name'),
        ]);

        return Inertia::render('sicurezzachiara/workers/Edit', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'worker' => $worker,
            'formOptions' => $this->formOptions($tenant),
            'companyContext' => [
                'id' => $worker->company_id,
                'name' => $worker->company?->name,
                'workersRoute' => route('workers.index', ['company_id' => $worker->company_id]),
                'showRoute' => route('companies.show', $worker->company_id),
            ],
        ]);
    }

    public function update(
        UpdateWorkerRequest $request,
        Worker $worker,
        CurrentTenantResolver $tenantResolver,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $worker = $this->workerForTenant($tenant, $worker);
        $validated = $request->validated();
        $company = $this->companyForTenant($tenant, Company::query()->findOrFail($validated['company_id']));
        $jobRole = $this->resolveJobRoleForTenant($tenant, $validated['job_role_id'] ?? null);

        $this->ensurePrimarySiteBelongsToCompany($company, $validated['primary_site_id'] ?? null);

        $worker->update(collect($validated)->except('job_role_id')->all());
        $this->syncPrimaryJobRole($worker, $jobRole, $validated['hire_date'] ?? null);
        $auditLogger->log(
            $tenant,
            $request->user(),
            'worker.updated',
            $worker,
            'Aggiornato lavoratore '.$worker->full_name,
            [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'worker_name' => $worker->full_name,
            ],
        );

        if ($request->boolean('redirect_to_company_edit')) {
            return redirect()
                ->route('companies.edit', $company)
                ->with('success', 'Lavoratore aggiornato correttamente.');
        }

        if ($request->boolean('redirect_to_company')) {
            return redirect()
                ->route('companies.show', $company)
                ->with('success', 'Lavoratore aggiornato correttamente.');
        }

        return redirect()
            ->route('workers.show', $worker)
            ->with('success', 'Lavoratore aggiornato correttamente.');
    }

    public function destroy(
        Request $request,
        Worker $worker,
        CurrentTenantResolver $tenantResolver,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $worker = $this->workerForTenant($tenant, $worker);
        $worker->loadMissing([
            'company:id,name',
            'jobRoleAssignments.jobRole:id,name',
            'equipmentExposures.equipmentAsset:id,name',
            'workplaceExposures.workplace:id,name',
            'riskProfileItems:id,profileable_type,profileable_id,risk_catalog_item_id',
            'riskMeasures:id,profileable_type,profileable_id,risk_catalog_item_id',
        ]);

        $references = collect([
            $this->buildDependencyReference(
                'Mansioni',
                'job_roles',
                $worker->jobRoleAssignments->map(fn ($assignment) => $assignment->jobRole?->name),
            ),
            $this->buildDependencyReference(
                'Macchinari',
                'equipment',
                $worker->equipmentExposures->map(fn ($exposure) => $exposure->equipmentAsset?->name),
            ),
            $this->buildDependencyReference(
                'Luoghi',
                'workplaces',
                $worker->workplaceExposures->map(fn ($exposure) => $exposure->workplace?->name),
            ),
            $this->buildCountReference(
                'Profilo rischio',
                'risk_profile',
                $worker->riskProfileItems->count(),
            ),
            $this->buildCountReference(
                'Misure',
                'risk_measures',
                $worker->riskMeasures->count(),
            ),
        ])->filter()->values();

        if ($references->isNotEmpty()) {
            $redirect = $request->boolean('redirect_to_company_edit')
                ? redirect()->route('companies.edit', $worker->company)
                : redirect()->route('workers.show', $worker);

            return $redirect->with('error', [
                'title' => 'Lavoratore ancora in uso',
                'message' => 'Prima di cancellare il lavoratore devi riallineare i collegamenti ancora attivi.',
                'references' => $references->all(),
            ]);
        }

        $workerName = $worker->full_name;
        $company = $worker->company;

        $worker->delete();

        $auditLogger->log(
            $tenant,
            $request->user(),
            'worker.deleted',
            $company,
            'Cancellato lavoratore '.$workerName,
            [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'worker_name' => $workerName,
            ],
        );

        if ($request->boolean('redirect_to_company_edit')) {
            return redirect()
                ->route('companies.edit', $company)
                ->with('success', 'Lavoratore cancellato correttamente.');
        }

        return redirect()
            ->route('workers.index')
            ->with('success', 'Lavoratore cancellato correttamente.');
    }

    private function formOptions(Tenant $tenant): array
    {
        $companies = $tenant->companies()
            ->with(['sites' => fn ($query) => $query->orderByDesc('is_headquarters')->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name']);

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
            'jobRoles' => $this->jobRoleOptions($tenant),
        ];
    }

    private function jobRoleOptions(Tenant $tenant): array
    {
        return JobRole::query()
            ->where(function ($query) use ($tenant) {
                $query->where('source', JobRole::SOURCE_CORE)
                    ->orWhere('tenant_id', $tenant->id);
            })
            ->where('is_active', true)
            ->orderByRaw("case when source = 'tenant' then 0 else 1 end")
            ->orderBy('name')
            ->get(['id', 'name', 'source'])
            ->map(fn (JobRole $jobRole) => [
                'id' => $jobRole->id,
                'name' => $jobRole->name,
                'source' => $jobRole->source,
            ])
            ->values()
            ->all();
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

    private function companyForTenant(Tenant $tenant, Company $company): Company
    {
        abort_unless($company->tenant_id === $tenant->id, 404);

        return $company;
    }

    private function workerForTenant(Tenant $tenant, Worker $worker): Worker
    {
        $worker->loadMissing('company');

        abort_unless($worker->company !== null && $worker->company->tenant_id === $tenant->id, 404);

        return $worker;
    }

    private function ensurePrimarySiteBelongsToCompany(Company $company, ?int $primarySiteId): void
    {
        if ($primarySiteId === null) {
            return;
        }

        $siteBelongsToCompany = $company->sites()->whereKey($primarySiteId)->exists();

        if (! $siteBelongsToCompany) {
            throw ValidationException::withMessages([
                'primary_site_id' => 'La sede prevalente deve appartenere alla stessa azienda del lavoratore.',
            ]);
        }
    }

    private function resolveJobRoleForTenant(Tenant $tenant, ?int $jobRoleId): ?JobRole
    {
        if ($jobRoleId === null) {
            return null;
        }

        return $this->jobRoleForTenant($tenant, JobRole::query()->findOrFail($jobRoleId));
    }

    private function jobRoleForTenant(Tenant $tenant, JobRole $jobRole): JobRole
    {
        abort_unless(
            $jobRole->source === JobRole::SOURCE_CORE || $jobRole->tenant_id === $tenant->id,
            404,
        );

        return $jobRole;
    }

    private function syncPrimaryJobRole(Worker $worker, ?JobRole $jobRole, ?string $assignedOn = null): void
    {
        if ($jobRole === null) {
            return;
        }

        $existingAssignment = $worker->jobRoleAssignments()
            ->where('job_role_id', $jobRole->id)
            ->first();

        $worker->jobRoleAssignments()->update(['is_primary' => false]);

        if ($existingAssignment) {
            $existingAssignment->update([
                'is_primary' => true,
                'assigned_on' => $assignedOn ?: $existingAssignment->assigned_on,
            ]);

            return;
        }

        $worker->jobRoleAssignments()->create([
            'job_role_id' => $jobRole->id,
            'is_primary' => true,
            'assigned_on' => $assignedOn ?: null,
        ]);
    }

    private function equipmentAssetOptions(Worker $worker): array
    {
        return EquipmentAsset::query()
            ->where('company_id', $worker->company_id)
            ->with('equipmentType:id,name,source')
            ->orderBy('name')
            ->get(['id', 'company_id', 'equipment_type_id', 'name', 'status'])
            ->map(fn (EquipmentAsset $equipmentAsset) => [
                'id' => $equipmentAsset->id,
                'name' => $equipmentAsset->name,
                'status' => $equipmentAsset->status,
                'equipment_type_name' => $equipmentAsset->equipmentType?->name,
                'source' => $equipmentAsset->equipmentType?->source,
            ])
            ->values()
            ->all();
    }

    private function workplaceOptions(Worker $worker): array
    {
        return Workplace::query()
            ->whereHas('site', fn ($query) => $query->where('company_id', $worker->company_id))
            ->with(['workplaceType:id,name,source', 'site:id,name'])
            ->orderBy('name')
            ->get(['id', 'company_site_id', 'workplace_type_id', 'name', 'status'])
            ->map(fn (Workplace $workplace) => [
                'id' => $workplace->id,
                'name' => $workplace->name,
                'status' => $workplace->status,
                'site_name' => $workplace->site?->name,
                'workplace_type_name' => $workplace->workplaceType?->name,
                'source' => $workplace->workplaceType?->source,
            ])
            ->values()
            ->all();
    }

    private function buildWorkerContextBridge(Worker $worker, array $summary): array
    {
        $overdueMeasures = $worker->riskMeasures
            ->filter(fn ($measure) => $measure->due_date !== null
                && $measure->status !== 'implemented'
                && $measure->due_date->isPast())
            ->count();
        $focus = match (true) {
            $overdueMeasures > 0 => 'deadlines',
            ($summary['followUpsOpen'] ?? 0) > 0 => 'follow_up',
            ($summary['reviewsDue'] ?? 0) > 0 => 'reviews',
            default => 'all',
        };
        $scope = match ($focus) {
            'deadlines' => 'overdue',
            'follow_up' => 'follow_up_open',
            default => 'attention',
        };

        $suggestedAction = match ($focus) {
            'deadlines' => [
                'label' => 'Chiudi le scadenze legate al lavoratore',
                'helper' => $overdueMeasures.' misure oltre data impattano questo profilo e chiedono un passaggio nel registro aziendale.',
            ],
            'follow_up' => [
                'label' => 'Segui il rischio in carico',
                'helper' => ($summary['followUpsOpen'] ?? 0).' rischi del lavoratore restano in follow-up operativo.',
            ],
            'reviews' => [
                'label' => 'Riallinea la review lavoratore',
                'helper' => ($summary['reviewsDue'] ?? 0).' review risultano dovute sul profilo di questo lavoratore.',
            ],
            default => [
                'label' => 'Verifica la copertura del profilo',
                'helper' => ($summary['missingExpectedMeasures'] ?? 0).' gap attesi e '.($summary['uncoveredRisks'] ?? 0).' rischi scoperti richiedono lettura sul profilo.',
            ],
        };

        return [
            'focus' => $focus,
            'focusLabel' => match ($focus) {
                'deadlines' => 'Scadenze',
                'follow_up' => 'Follow-up',
                'reviews' => 'Review',
                default => 'Copertura',
            },
            'suggestedAction' => $suggestedAction,
            'stats' => [
                'activeRisks' => (int) ($summary['activeRisks'] ?? 0),
                'overdueMeasures' => $overdueMeasures,
                'followUpsOpen' => (int) ($summary['followUpsOpen'] ?? 0),
                'missingExpectedMeasures' => (int) ($summary['missingExpectedMeasures'] ?? 0),
            ],
            'actions' => [
                'riskProfileRoute' => route('workers.risk-profile.show', [
                    'worker' => $worker,
                    'origin' => 'worker_show',
                    'focus' => $focus,
                ]),
                'registryRoute' => route('measure-registries.index', array_filter([
                    'company_id' => $worker->company_id,
                    'origin' => 'worker_show',
                    'focus' => $focus,
                    'scope' => $scope,
                    'family' => $focus === 'follow_up' ? 'follow_up' : null,
                ], fn ($value) => $value !== null && $value !== '')),
                'workersRoute' => route('workers.index', ['company_id' => $worker->company_id]),
                'companyRoute' => route('companies.show', $worker->company_id),
                'dashboardRoute' => route('dashboard', $focus !== 'all' ? ['focus' => $focus] : []),
            ],
        ];
    }

    private function buildWorkerGovernanceContext(Worker $worker): array
    {
        $today = now()->startOfDay();
        $profileItems = $worker->riskProfileItems;
        $measures = $worker->riskMeasures
            ->sortBy([
                ['due_date', 'asc'],
                ['title', 'asc'],
            ])
            ->values();

        return [
            'summary' => [
                'activeRisks' => $profileItems->where('operational_status', 'active')->count(),
                'reviewsDue' => $profileItems->filter(fn ($item) => $item->review_due_at !== null && $item->review_due_at->lte($today))->count(),
                'followUpsOpen' => $profileItems->filter(fn ($item) => in_array($item->follow_up_status, ['open', 'in_progress', 'blocked'], true))->count(),
                'totalMeasures' => $measures->count(),
                'overdueMeasures' => $measures->filter(fn ($measure) => $measure->due_date !== null
                    && $measure->status !== 'implemented'
                    && $measure->due_date->lt($today))->count(),
                'toVerifyMeasures' => $measures->where('status', 'to_verify')->count(),
            ],
            'previewMeasures' => $measures
                ->take(5)
                ->map(fn ($measure) => [
                    'id' => $measure->id,
                    'title' => $measure->title,
                    'status' => $measure->status,
                    'family' => $measure->family,
                    'dueDate' => $measure->due_date?->format('Y-m-d'),
                    'riskName' => $measure->riskCatalogItem?->name,
                ])
                ->values()
                ->all(),
            'reviewAlerts' => $profileItems
                ->filter(fn ($item) => $item->review_due_at !== null || $item->follow_up_status !== null)
                ->take(5)
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'riskName' => $item->riskCatalogItem?->name,
                    'reviewDueAt' => $item->review_due_at?->format('Y-m-d'),
                    'followUpStatus' => $item->follow_up_status,
                ])
                ->values()
                ->all(),
        ];
    }

    private function buildDependencyReference(string $label, string $key, \Illuminate\Support\Collection $items): ?array
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

    private function buildCountReference(string $label, string $key, int $count): ?array
    {
        if ($count === 0) {
            return null;
        }

        return [
            'key' => $key,
            'label' => $label,
            'count' => $count,
            'items' => [],
            'has_more' => false,
        ];
    }
}
