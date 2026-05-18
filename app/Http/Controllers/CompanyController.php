<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureTenantCanManageData;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Ateco2025;
use App\Models\Company;
use App\Models\CompanySite;
use App\Models\EquipmentType;
use App\Models\JobRole;
use App\Models\Tenant;
use App\Models\WorkplaceType;
use App\Support\AuditLogger;
use App\Support\CoreStarterPackContextBuilder;
use App\Support\CurrentTenantResolver;
use App\Support\RiskEngineSnapshotBuilder;
use App\Support\RiskProfileBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware(EnsureTenantCanManageData::class)->only(['create', 'store', 'edit', 'update']);
    }

    public function index(Request $request, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());

        $companies = $tenant->companies()
            ->with([
                'sites:id,company_id',
                'sites.workplaces:id,company_site_id',
            ])
            ->withCount(['sites', 'workers', 'equipmentAssets', 'jobRoleAssignments', 'riskProfileItems'])
            ->orderBy('name')
            ->get();

        return Inertia::render('sicurezzachiara/companies/Index', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'companies' => $companies->map(fn (Company $company) => [
                ...$company->toArray(),
                'area_one_journey' => $this->buildAreaOneJourneySummary($company),
            ])->values(),
            'summary' => [
                'companiesCount' => $companies->count(),
                'sitesCount' => (int) $companies->sum('sites_count'),
                'workersCount' => (int) $companies->sum('workers_count'),
                'workplacesCount' => (int) $companies->sum(fn (Company $company) => $company->sites->sum(fn ($site) => $site->workplaces->count())),
                'equipmentCount' => (int) $companies->sum('equipment_assets_count'),
            ],
            'areaOne' => [
                'label' => 'Area 1 - Gestione azienda',
                'description' => 'Configura anagrafica e sedi, costruisci il contesto con luoghi, macchinari, lavoratori e mansioni, poi passa al governo operativo del rischio.',
                'steps' => [
                    'Anagrafica e sedi',
                    'Luoghi di lavoro',
                    'Macchinari e attrezzature',
                    'Lavoratori e mansioni',
                    'Profilo rischio dedotto',
                    'Governance operativa',
                ],
            ],
        ]);
    }

    public function create(Request $request, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());

        return Inertia::render('sicurezzachiara/companies/Create', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'atecoConfig' => $this->buildAtecoConfig(),
            'comuniConfig' => $this->buildComuniConfig(),
        ]);
    }

    public function store(
        StoreCompanyRequest $request,
        CurrentTenantResolver $tenantResolver,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());

        $payload = $this->normalizeCompanyPayload($request->validated());

        $company = $tenant->companies()->create($payload);
        $auditLogger->log(
            $tenant,
            $request->user(),
            'company.created',
            $company,
            'Creata azienda '.$company->name,
            [
                'company_name' => $company->name,
            ],
        );

        return redirect()
            ->route('companies.edit', $company)
            ->with('success', 'Azienda creata correttamente.');
    }

    public function show(
        Request $request,
        Company $company,
        CurrentTenantResolver $tenantResolver,
        CoreStarterPackContextBuilder $coreStarterPackContextBuilder,
        RiskProfileBuilder $riskProfileBuilder,
        RiskEngineSnapshotBuilder $riskEngineSnapshotBuilder,
    ): Response {
        $tenant = $tenantResolver->resolve($request->user());
        $company = $this->companyForTenant($tenant, $company);
        $riskProfileBuilder->rebuildCompany($company);
        $company->load([
            'sites' => fn ($query) => $query->orderByDesc('is_headquarters')->orderBy('name'),
            'workers' => fn ($query) => $query
                ->with([
                    'primarySite:id,name',
                    'jobRoleAssignments' => fn ($assignmentQuery) => $assignmentQuery
                        ->where('is_primary', true)
                        ->with([
                            'jobRole' => fn ($jobRoleQuery) => $jobRoleQuery
                                ->with(['riskSourceLinks.riskCatalogItem.category']),
                        ]),
                ])
                ->orderBy('last_name')
                ->orderBy('first_name'),
            'equipmentAssets' => fn ($query) => $query
                ->with([
                    'site:id,name',
                    'equipmentType' => fn ($equipmentTypeQuery) => $equipmentTypeQuery
                        ->with(['riskSourceLinks.riskCatalogItem.category']),
                ])
                ->orderBy('name'),
            'sites.workplaces' => fn ($query) => $query
                ->with([
                    'workplaceType' => fn ($workplaceTypeQuery) => $workplaceTypeQuery
                        ->with(['riskSourceLinks.riskCatalogItem.category']),
                ])
                ->orderBy('name'),
            'riskProfileItems:id,profileable_type,profileable_id,risk_catalog_item_id,status,priority,final_priority,operational_status,review_due_at,follow_up_status',
            'riskMeasures:id,profileable_type,profileable_id,status,due_date',
        ]);
        $company->setRelation('sites', $company->sites->map(function (CompanySite $site) {
            $site->setAttribute('comune_option', $this->buildComuneOption($site->city, $site->province));

            return $site;
        }));
        $engine = $riskEngineSnapshotBuilder->buildForProfileable($company);
        $companyBridge = $this->buildCompanyContextBridge($company, $engine['summary']);

        $coreStarterPack = $coreStarterPackContextBuilder->buildForCompanySources(
            $company->workers
                ->flatMap(fn ($worker) => $worker->jobRoleAssignments->pluck('jobRole'))
                ->filter()
                ->values(),
            $company->equipmentAssets
                ->pluck('equipmentType')
                ->filter()
                ->values(),
            $company->sites
                ->flatMap(fn ($site) => $site->workplaces->pluck('workplaceType'))
                ->filter()
                ->values(),
        );

        return Inertia::render('sicurezzachiara/companies/Show', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'company' => $company,
            'coreStarterPack' => $coreStarterPack,
            'contextBridge' => $companyBridge,
            'areaOneJourney' => $this->buildAreaOneJourney($company, $companyBridge, $engine['summary']),
            'configureForms' => $this->buildConfigureForms($tenant, $company),
        ]);
    }

    public function edit(Request $request, Company $company, CurrentTenantResolver $tenantResolver): Response
    {
        $tenant = $tenantResolver->resolve($request->user());
        $company = $this->companyForTenant($tenant, $company);
        $company->load([
            'sites' => fn ($query) => $query
                ->with([
                    'workplaces' => fn ($workplaceQuery) => $workplaceQuery
                        ->with([
                            'workplaceType:id,name',
                            'workerExposures.worker:id,first_name,last_name',
                        ]),
                    'equipmentAssets:id,company_site_id,name',
                    'primaryWorkers:id,primary_site_id,first_name,last_name',
                ])
                ->orderByDesc('is_headquarters')
                ->orderBy('name'),
            'equipmentAssets' => fn ($query) => $query
                ->with(['site:id,name', 'equipmentType:id,name', 'workerExposures.worker:id,first_name,last_name'])
                ->orderBy('name'),
            'workers' => fn ($query) => $query
                ->with([
                    'primarySite:id,name',
                    'jobRoleAssignments' => fn ($assignmentQuery) => $assignmentQuery
                        ->where('is_primary', true)
                        ->with('jobRole:id,name'),
                    'equipmentExposures.equipmentAsset:id,name',
                    'workplaceExposures.workplace:id,name',
                ])
                ->withCount(['riskProfileItems', 'riskMeasures'])
                ->orderBy('last_name')
                ->orderBy('first_name'),
            'atecoEntry:id,codice,titolo_it',
        ])->loadCount('riskProfileItems');

        $company->setRelation('sites', $company->sites->map(function (CompanySite $site) {
            $site->setAttribute('comune_option', $this->buildComuneOption($site->city, $site->province));
            $site->setAttribute('dependency_alert', $this->buildSiteDependencyAlert($site));
            $site->setRelation('workplaces', $site->workplaces->map(function ($workplace) {
                $workplace->setAttribute('dependency_alert', $this->buildWorkplaceDependencyAlert($workplace));

                return $workplace;
            }));

            return $site;
        }));
        $company->setRelation('workers', $company->workers->map(function ($worker) {
            $worker->setAttribute('dependency_alert', $this->buildWorkerDependencyAlert($worker));

            return $worker;
        }));
        $company->setRelation('equipmentAssets', $company->equipmentAssets->map(function ($asset) {
            $asset->setAttribute('dependency_alert', $this->buildEquipmentDependencyAlert($asset));

            return $asset;
        }));

        return Inertia::render('sicurezzachiara/companies/Edit', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'company' => $company,
            'configureForms' => $this->buildConfigureForms($tenant, $company),
            'atecoConfig' => $this->buildAtecoConfig($company->atecoEntry),
            'comuniConfig' => $this->buildComuniConfig($company),
        ]);
    }

    public function update(
        UpdateCompanyRequest $request,
        Company $company,
        CurrentTenantResolver $tenantResolver,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $company = $this->companyForTenant($tenant, $company);

        $payload = $this->normalizeCompanyPayload($request->validated(), $company);

        $company->update($payload);
        $auditLogger->log(
            $tenant,
            $request->user(),
            'company.updated',
            $company,
            'Aggiornata azienda '.$company->name,
            [
                'company_name' => $company->name,
            ],
        );

        return redirect()
            ->route('companies.edit', $company)
            ->with('success', 'Azienda aggiornata correttamente.');
    }

    private function companyForTenant(Tenant $tenant, Company $company): Company
    {
        abort_unless($company->tenant_id === $tenant->id, 404);

        return $company;
    }

    private function buildAtecoConfig(?Ateco2025 $entry = null): array
    {
        return [
            'searchRoute' => route('ateco.search'),
            'initialOption' => $entry ? [
                'id' => $entry->id,
                'code' => $entry->codice,
                'title' => $entry->titolo_it,
                'label' => $entry->codice.' - '.$entry->titolo_it,
            ] : null,
        ];
    }

    private function buildComuniConfig(?Company $company = null): array
    {
        return [
            'searchRoute' => route('comuni.search'),
            'initialOption' => $this->buildComuneOption($company?->city, $company?->province),
        ];
    }

    private function buildComuneOption(?string $city, ?string $province): ?array
    {
        if (! $city || ! $province) {
            return null;
        }

        $entry = DB::table('comuni_elenco')
            ->select([
                'comune',
                'provincia',
                'provincia_esteso',
                DB::raw('MIN(istat) as istat'),
                DB::raw("ARRAY_AGG(DISTINCT cap ORDER BY cap) as caps"),
            ])
            ->where('comune', $city)
            ->where('provincia', $province)
            ->groupBy('comune', 'provincia', 'provincia_esteso')
            ->first();

        if (! $entry) {
            return null;
        }

        $caps = $this->normalizePostgresArray($entry->caps ?? []);

        return [
            'id' => (int) $entry->istat,
            'city' => $entry->comune,
            'province' => $entry->provincia,
            'provinceLabel' => $entry->provincia_esteso,
            'caps' => $caps,
            'label' => $entry->comune.' ('.$entry->provincia.')',
            'capLabel' => $this->formatCapLabel($caps),
        ];
    }

    private function normalizePostgresArray(mixed $values): array
    {
        if (is_array($values)) {
            return collect($values)
                ->flatMap(fn ($value) => $this->expandCapValue($value))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if (is_string($values)) {
            return collect(explode(',', trim($values, '{}')))
                ->map(fn ($value) => trim($value, '" '))
                ->flatMap(fn ($value) => $this->expandCapValue($value))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return [];
    }

    private function expandCapValue(mixed $value): array
    {
        $cap = trim((string) $value);

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

    private function formatCapLabel(array $caps): string
    {
        if ($caps === []) {
            return '';
        }

        if (count($caps) === 1) {
            return $caps[0];
        }

        return $caps[0].' - '.$caps[array_key_last($caps)];
    }

    private function normalizeCompanyPayload(array $payload, ?Company $company = null): array
    {
        $ateco = null;

        if (! empty($payload['ateco_2025_id'])) {
            $ateco = Ateco2025::query()->find($payload['ateco_2025_id']);
        }

        $payload['industry'] = $ateco?->titolo_it
            ?? $payload['industry']
            ?? $company?->industry
            ?? null;

        return $payload;
    }

    private function buildCompanyContextBridge(Company $company, array $summary): array
    {
        $reviewItem = $company->riskProfileItems
            ->sortByDesc(fn ($item) => [$item->operational_status === 'open', $item->final_priority ?? $item->priority ?? 0])
            ->first();
        $uncoveredRisks = (int) ($summary['uncoveredRisks'] ?? 0);
        $reviewsDue = (int) ($summary['reviewsDue'] ?? 0);
        $followUpsOpen = (int) ($summary['followUpsOpen'] ?? 0);
        $missingExpectedMeasures = (int) ($summary['missingExpectedMeasures'] ?? 0);
        $pendingMeasures = (int) ($summary['pendingMeasures'] ?? 0);
        $coverageRate = (int) ($summary['coverageRate'] ?? 0);
        $risksWithExpectedGaps = (int) ($summary['risksWithExpectedGaps'] ?? 0);
        $overdueMeasures = $company->riskMeasures
            ->filter(fn ($measure) => $measure->due_date !== null
                && $measure->status !== 'implemented'
                && $measure->due_date->isPast())
            ->count();
        $focus = match (true) {
            $overdueMeasures > 0 => 'deadlines',
            $followUpsOpen > 0 => 'follow_up',
            $reviewsDue > 0 => 'reviews',
            default => 'all',
        };
        $scope = match ($focus) {
            'deadlines' => 'overdue',
            'follow_up' => 'follow_up_open',
            default => 'attention',
        };

        $suggestedAction = match ($focus) {
            'deadlines' => [
                'label' => 'Chiudi le scadenze aperte',
                'helper' => $overdueMeasures.' misure oltre data richiedono un passaggio operativo immediato nel registro aziendale.',
            ],
            'follow_up' => [
                'label' => 'Segui i follow-up attivi',
                'helper' => $followUpsOpen.' rischi restano in carico operativo e vanno riallineati tra review e registri.',
            ],
            'reviews' => [
                'label' => 'Riallinea le review dovute',
                'helper' => $reviewsDue.' review consulente risultano in scadenza sul profilo aziendale.',
            ],
            default => [
                'label' => 'Verifica la copertura del profilo',
                'helper' => $missingExpectedMeasures.' gap attesi e '.$uncoveredRisks.' rischi scoperti restano da presidiare.',
            ],
        };

        $workQueue = collect([
            $overdueMeasures > 0 ? [
                'key' => 'deadlines',
                'label' => 'Scadenze da chiudere',
                'count' => $overdueMeasures,
                'helper' => 'Le misure oltre data sono il primo blocco operativo da riallineare nel registro aziendale.',
                'actionLabel' => 'Apri scadute',
                'actionRoute' => route('measure-registries.index', [
                    'company_id' => $company->id,
                    'origin' => 'company_show',
                    'focus' => 'deadlines',
                    'scope' => 'overdue',
                ]),
            ] : null,
            $reviewsDue > 0 ? [
                'key' => 'reviews',
                'label' => 'Review consulenziali',
                'count' => $reviewsDue,
                'helper' => 'Ci sono rischi che richiedono un riallineamento del giudizio professionale.',
                'actionLabel' => 'Apri review',
                'actionRoute' => $reviewItem
                    ? route('companies.risk-profile.review.show', [
                        'company' => $company,
                        'riskProfileItem' => $reviewItem,
                        'origin' => 'company_show',
                        'focus' => 'reviews',
                    ])
                    : route('companies.risk-profile.show', [
                        'company' => $company,
                        'origin' => 'company_show',
                        'focus' => 'reviews',
                    ]),
            ] : null,
            $followUpsOpen > 0 ? [
                'key' => 'follow_up',
                'label' => 'Follow-up aperti',
                'count' => $followUpsOpen,
                'helper' => 'Alcuni rischi restano in carico operativo e vanno chiusi tra review e registri.',
                'actionLabel' => 'Apri follow-up',
                'actionRoute' => route('measure-registries.index', [
                    'company_id' => $company->id,
                    'origin' => 'company_show',
                    'focus' => 'follow_up',
                    'scope' => 'follow_up_open',
                    'family' => 'follow_up',
                ]),
            ] : null,
            $missingExpectedMeasures > 0 ? [
                'key' => 'expected_gaps',
                'label' => 'Presidi attesi mancanti',
                'count' => $missingExpectedMeasures,
                'helper' => 'Il motore ha ancora misure attese non coperte nel perimetro corrente.',
                'actionLabel' => 'Apri misure',
                'actionRoute' => $reviewItem
                    ? route('companies.risk-profile.measures.show', [
                        'company' => $company,
                        'riskProfileItem' => $reviewItem,
                        'origin' => 'company_show',
                        'focus' => 'all',
                    ])
                    : route('companies.risk-profile.show', [
                        'company' => $company,
                        'origin' => 'company_show',
                        'focus' => 'all',
                    ]),
            ] : null,
        ])->filter()->values()->all();

        return [
            'focus' => $focus,
            'focusLabel' => match ($focus) {
                'deadlines' => 'Scadenze',
                'follow_up' => 'Follow-up',
                'reviews' => 'Review',
                default => 'Copertura',
            },
            'suggestedAction' => $suggestedAction,
            'workQueue' => $workQueue,
            'operationalQueue' => collect([
                [
                    'key' => 'reviews',
                    'label' => 'Review da chiudere',
                    'count' => $reviewsDue,
                    'status' => $reviewsDue > 0 ? 'open' : 'aligned',
                    'helper' => $reviewsDue > 0
                        ? 'Il giudizio consulenziale va riallineato sui rischi che oggi risultano in review.'
                        : 'Non risultano review aperte nel perimetro aziendale corrente.',
                    'actionLabel' => 'Apri review',
                    'actionRoute' => $reviewItem
                        ? route('companies.risk-profile.review.show', [
                            'company' => $company,
                            'riskProfileItem' => $reviewItem,
                            'origin' => 'company_show',
                            'focus' => 'reviews',
                        ])
                        : route('companies.risk-profile.show', [
                            'company' => $company,
                            'origin' => 'company_show',
                            'focus' => 'reviews',
                        ]),
                ],
                [
                    'key' => 'follow_up',
                    'label' => 'Follow-up operativi',
                    'count' => $followUpsOpen,
                    'status' => $followUpsOpen > 0 ? 'open' : 'aligned',
                    'helper' => $followUpsOpen > 0
                        ? 'Alcuni rischi restano in carico operativo e vanno chiusi tra profilo e registri.'
                        : 'Non risultano follow-up aperti nel perimetro aziendale corrente.',
                    'actionLabel' => 'Apri follow-up',
                    'actionRoute' => route('measure-registries.index', [
                        'company_id' => $company->id,
                        'origin' => 'company_show',
                        'focus' => 'follow_up',
                        'scope' => 'follow_up_open',
                        'family' => 'follow_up',
                    ]),
                ],
                [
                    'key' => 'registries',
                    'label' => 'Registri famiglia',
                    'count' => $pendingMeasures,
                    'status' => $pendingMeasures > 0 ? 'attention' : 'aligned',
                    'helper' => $overdueMeasures > 0
                        ? $overdueMeasures.' misure oltre data richiedono chiusura nel registro contestuale.'
                        : 'Controlla attuazione, owner e scadenze delle misure per famiglia.',
                    'actionLabel' => 'Apri registri',
                    'actionRoute' => route('measure-registries.index', array_filter([
                        'company_id' => $company->id,
                        'origin' => 'company_show',
                        'focus' => $focus,
                        'scope' => $scope,
                        'family' => $focus === 'follow_up' ? 'follow_up' : null,
                    ], fn ($value) => $value !== null && $value !== '')),
                ],
                [
                    'key' => 'dvr',
                    'label' => 'DVR light',
                    'count' => $missingExpectedMeasures,
                    'status' => $missingExpectedMeasures > 0 ? 'to_complete' : 'aligned',
                    'helper' => $missingExpectedMeasures > 0
                        ? 'Il DVR resta consultabile, ma ha ancora gap di presidio aperti nel perimetro corrente.'
                        : 'Il DVR riflette un perimetro gia\' piu\' allineato sul piano operativo.',
                    'actionLabel' => 'Apri DVR',
                    'actionRoute' => route('companies.dvr.show', $company),
                ],
            ])->all(),
            'stats' => [
                'activeRisks' => (int) ($summary['activeRisks'] ?? 0),
                'uncoveredRisks' => $uncoveredRisks,
                'reviewsDue' => $reviewsDue,
                'overdueMeasures' => $overdueMeasures,
                'followUpsOpen' => $followUpsOpen,
                'missingExpectedMeasures' => $missingExpectedMeasures,
                'pendingMeasures' => $pendingMeasures,
                'coverageRate' => $coverageRate,
                'risksWithExpectedGaps' => $risksWithExpectedGaps,
                'implementedMeasures' => (int) ($summary['implementedMeasures'] ?? 0),
                'coveredExpectedMeasures' => (int) ($summary['coveredExpectedMeasures'] ?? 0),
                'substitutedExpectedMeasures' => (int) ($summary['substitutedExpectedMeasures'] ?? 0),
                'highPriorityRisks' => (int) ($summary['highPriorityRisks'] ?? 0),
            ],
            'actions' => [
                'riskProfileRoute' => route('companies.risk-profile.show', [
                    'company' => $company,
                    'origin' => 'company_show',
                    'focus' => $focus,
                ]),
                'reviewRoute' => $reviewItem
                    ? route('companies.risk-profile.review.show', [
                        'company' => $company,
                        'riskProfileItem' => $reviewItem,
                        'origin' => 'company_show',
                        'focus' => $focus,
                    ])
                    : route('companies.risk-profile.show', [
                        'company' => $company,
                        'origin' => 'company_show',
                        'focus' => $focus,
                    ]),
                'measuresRoute' => $reviewItem
                    ? route('companies.risk-profile.measures.show', [
                        'company' => $company,
                        'riskProfileItem' => $reviewItem,
                        'origin' => 'company_show',
                        'focus' => $focus,
                    ])
                    : route('companies.risk-profile.show', [
                        'company' => $company,
                        'origin' => 'company_show',
                        'focus' => $focus,
                    ]),
                'registryRoute' => route('measure-registries.index', array_filter([
                    'company_id' => $company->id,
                    'origin' => 'company_show',
                    'focus' => $focus,
                    'scope' => $scope,
                    'family' => $focus === 'follow_up' ? 'follow_up' : null,
                ], fn ($value) => $value !== null && $value !== '')),
                'dashboardRoute' => route('dashboard', $focus !== 'all' ? ['focus' => $focus] : []),
                'dvrRoute' => route('companies.dvr.show', $company),
            ],
        ];
    }

    private function buildAreaOneJourneySummary(Company $company): array
    {
        $workplacesCount = (int) $company->sites->sum(fn ($site) => $site->workplaces->count());
        $completedSteps = collect([
            $company->sites_count > 0,
            $workplacesCount > 0,
            $company->equipment_assets_count > 0,
            $company->workers_count > 0 && $company->job_role_assignments_count > 0,
            $company->risk_profile_items_count > 0,
        ])->filter()->count();

        $nextStep = match (true) {
            $company->sites_count === 0 => [
                'label' => 'Completa sedi',
                'helper' => 'Prima costruisci il perimetro fisico dell\'azienda.',
                'route' => route('companies.show', $company),
            ],
            $workplacesCount === 0 => [
                'label' => 'Configura luoghi',
                'helper' => 'I luoghi danno forma al contesto di rischio della sede.',
                'route' => route('companies.show', $company),
            ],
            $company->equipment_assets_count === 0 => [
                'label' => 'Censisci macchinari',
                'helper' => 'Le attrezzature completano il contesto operativo aziendale.',
                'route' => route('companies.show', $company),
            ],
            $company->workers_count === 0 || $company->job_role_assignments_count === 0 => [
                'label' => 'Completa lavoratori e mansioni',
                'helper' => 'Il rischio operativo emerge davvero quando persone e mansioni sono agganciate al contesto.',
                'route' => route('companies.show', $company),
            ],
            $company->risk_profile_items_count === 0 => [
                'label' => 'Apri profilo rischio',
                'helper' => 'Il sistema puo\' ora restituire la prima deduzione strutturata dei rischi.',
                'route' => route('companies.show', $company),
            ],
            default => [
                'label' => 'Passa alla governance',
                'helper' => 'La configurazione di base e\' pronta: puoi entrare in review, misure e registri.',
                'route' => route('companies.show', $company),
            ],
        };

        return [
            'completedSteps' => $completedSteps,
            'totalSteps' => 5,
            'nextStep' => $nextStep,
        ];
    }

    private function buildAreaOneJourney(Company $company, array $contextBridge, array $summary): array
    {
        $firstSite = $company->sites->first();
        $workplacesCount = (int) $company->sites->sum(fn ($site) => $site->workplaces->count());
        $equipmentCount = (int) $company->equipmentAssets->count();
        $workerCount = (int) $company->workers->count();
        $jobRoleAssignmentsCount = (int) $company->workers
            ->flatMap(fn ($worker) => $worker->jobRoleAssignments)
            ->count();
        $activeRisks = (int) ($summary['activeRisks'] ?? 0);
        $completedSteps = collect([
            $company->sites->isNotEmpty(),
            $workplacesCount > 0,
            $equipmentCount > 0,
            $workerCount > 0 && $jobRoleAssignmentsCount > 0,
            $activeRisks > 0,
        ])->filter()->count();

        $steps = [
            [
                'number' => 1,
                'sectionId' => 'area1-step-1',
                'title' => 'Anagrafica e sedi',
                'helper' => 'Il codice ATECO resta un riferimento orientativo dell\'attivita\'; il vero perimetro parte da anagrafica e sedi reali.',
                'status' => $company->sites->isNotEmpty() ? 'completed' : 'current',
                'summary' => $company->sites->isNotEmpty()
                    ? $company->sites->count().' sedi censite'
                    : 'Manca almeno una sede operativa',
                'primaryAction' => $company->sites->isNotEmpty()
                    ? ['label' => 'Rivedi sedi', 'route' => route('companies.show', $company)]
                    : ['label' => 'Completa azienda e sedi', 'route' => route('companies.show', $company)],
                'secondaryAction' => $company->sites->isNotEmpty() && $company->sites->count() > 1
                    ? ['label' => 'Modifica sedi', 'route' => route('companies.show', $company)]
                    : null,
            ],
            [
                'number' => 2,
                'sectionId' => 'area1-step-2',
                'title' => 'Luoghi a rischio per sede',
                'helper' => 'I luoghi trasformano la sede in contesto operativo reale e contribuiscono alla deduzione del rischio.',
                'status' => $workplacesCount > 0 ? 'completed' : ($company->sites->isNotEmpty() ? 'current' : 'upcoming'),
                'summary' => $workplacesCount > 0
                    ? $workplacesCount.' luoghi censiti'
                    : 'Ancora nessun luogo registrato',
                'primaryAction' => [
                    'label' => $workplacesCount > 0 ? 'Gestisci luoghi' : 'Aggiungi primo luogo',
                    'route' => route('workplaces.create', array_filter([
                        'company' => $company->id,
                        'site' => $firstSite?->id,
                    ])),
                ],
                'secondaryAction' => [
                    'label' => 'Apri registro luoghi',
                    'route' => route('workplaces.index'),
                ],
            ],
            [
                'number' => 3,
                'sectionId' => 'area1-step-3',
                'title' => 'Macchinari e attrezzature',
                'helper' => 'Macchinari e attrezzature completano il contesto fisico da cui il sistema deduce i rischi principali.',
                'status' => $equipmentCount > 0 ? 'completed' : ($company->sites->isNotEmpty() ? 'current' : 'upcoming'),
                'summary' => $equipmentCount > 0
                    ? $equipmentCount.' macchinari censiti'
                    : 'Ancora nessun macchinario registrato',
                'primaryAction' => [
                    'label' => $equipmentCount > 0 ? 'Gestisci macchinari' : 'Aggiungi primo macchinario',
                    'route' => route('equipment-assets.create', array_filter([
                        'company' => $company->id,
                        'site' => $firstSite?->id,
                    ])),
                ],
                'secondaryAction' => [
                    'label' => 'Apri registro macchinari',
                    'route' => route('equipment-assets.index'),
                ],
            ],
            [
                'number' => 4,
                'sectionId' => 'area1-step-4',
                'title' => 'Lavoratori e mansioni',
                'helper' => 'Dopo il contesto fisico, il consulente aggancia persone e mansioni per far emergere il rischio operativo reale.',
                'status' => $workerCount > 0 && $jobRoleAssignmentsCount > 0 ? 'completed' : ($company->sites->isNotEmpty() ? 'current' : 'upcoming'),
                'summary' => $workerCount > 0
                    ? $workerCount.' lavoratori | '.$jobRoleAssignmentsCount.' assegnazioni mansione'
                    : 'Ancora nessun lavoratore collegato',
                'primaryAction' => [
                    'label' => $workerCount > 0 ? 'Gestisci lavoratori' : 'Aggiungi primo lavoratore',
                    'route' => route('workers.create', ['company' => $company->id]),
                ],
                'secondaryAction' => [
                    'label' => 'Apri catalogo mansioni',
                    'route' => route('job-roles.index'),
                ],
            ],
            [
                'number' => 5,
                'sectionId' => 'area1-step-5',
                'title' => 'Profilo rischio dedotto',
                'helper' => 'Qui il sistema restituisce il rischio dedotto dell\'azienda e dei lavoratori a partire dal contesto costruito.',
                'status' => $activeRisks > 0 ? 'completed' : (($workerCount > 0 || $equipmentCount > 0 || $workplacesCount > 0) ? 'current' : 'upcoming'),
                'summary' => $activeRisks > 0
                    ? $activeRisks.' rischi attivi nel profilo'
                    : 'Il profilo esiste ma va ancora letto come risultato del setup',
                'primaryAction' => [
                    'label' => 'Apri profilo rischio',
                    'route' => $contextBridge['actions']['riskProfileRoute'],
                ],
                'secondaryAction' => [
                    'label' => 'Consulta DVR iniziale',
                    'route' => $contextBridge['actions']['dvrRoute'],
                ],
            ],
            [
                'number' => 6,
                'sectionId' => 'area1-step-6',
                'title' => 'Governance operativa',
                'helper' => 'Dopo la configurazione, il consulente passa a review, misure, copertura, registri, agenda e DVR.',
                'status' => 'current',
                'summary' => $contextBridge['suggestedAction']['helper'],
                'primaryAction' => [
                    'label' => $contextBridge['suggestedAction']['label'],
                    'route' => $contextBridge['actions']['riskProfileRoute'],
                ],
                'secondaryAction' => [
                    'label' => 'Apri registri azienda',
                    'route' => $contextBridge['actions']['registryRoute'],
                ],
            ],
        ];

        $currentStep = collect($steps)
            ->first(fn (array $step) => $step['status'] === 'current' && $step['number'] <= 5)
            ?? collect($steps)->firstWhere('number', 5);

        return [
            'label' => 'Area 1 - Gestione azienda',
            'description' => 'Un percorso unico: costruisci il contesto aziendale, fai emergere il rischio dedotto e poi passa alla governance operativa.',
            'completedSteps' => $completedSteps,
            'totalSetupSteps' => 5,
            'setupComplete' => $completedSteps >= 5,
            'currentStep' => $currentStep,
            'governanceStep' => collect($steps)->firstWhere('number', 6),
            'steps' => $steps,
        ];
    }

    private function buildConfigureForms(Tenant $tenant, Company $company): array
    {
        $company->loadMissing(['sites' => fn ($query) => $query->orderByDesc('is_headquarters')->orderBy('name')]);

        $companies = collect([
            [
                'id' => $company->id,
                'name' => $company->name,
            ],
        ]);

        $sitesByCompany = [
            (string) $company->id => $company->sites->map(fn (CompanySite $site) => [
                'id' => $site->id,
                'name' => $site->name,
                'is_headquarters' => $site->is_headquarters,
            ])->values(),
        ];

        $workplaceTypes = WorkplaceType::query()
            ->where(function ($query) use ($tenant) {
                $query->where('source', WorkplaceType::SOURCE_CORE)
                    ->orWhere('tenant_id', $tenant->id);
            })
            ->where('is_active', true)
            ->orderByRaw("case when source = 'tenant' then 0 else 1 end")
            ->orderBy('name')
            ->get(['id', 'name', 'source'])
            ->map(fn (WorkplaceType $workplaceType) => [
                'id' => $workplaceType->id,
                'name' => $workplaceType->name,
                'source' => $workplaceType->source,
            ])
            ->values();

        $equipmentTypes = EquipmentType::query()
            ->where(function ($query) use ($tenant) {
                $query->where('source', EquipmentType::SOURCE_CORE)
                    ->orWhere('tenant_id', $tenant->id);
            })
            ->where('is_active', true)
            ->orderByRaw("case when source = 'tenant' then 0 else 1 end")
            ->orderBy('name')
            ->get(['id', 'name', 'source'])
            ->map(fn (EquipmentType $equipmentType) => [
                'id' => $equipmentType->id,
                'name' => $equipmentType->name,
                'source' => $equipmentType->source,
            ])
            ->values();

        return [
            'worker' => [
                'formOptions' => [
                    'companies' => $companies,
                    'sitesByCompany' => $sitesByCompany,
                    'jobRoles' => JobRole::query()
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
                        ->values(),
                ],
                'defaults' => [
                    'company_id' => $company->id,
                ],
            ],
            'workplace' => [
                'formOptions' => [
                    'companies' => $companies,
                    'sitesByCompany' => $sitesByCompany,
                    'workplaceTypes' => $workplaceTypes,
                ],
                'defaults' => [
                    'company_id' => $company->id,
                    'company_site_id' => $company->sites->first()?->id,
                ],
            ],
            'equipment' => [
                'formOptions' => [
                    'companies' => $companies,
                    'sitesByCompany' => $sitesByCompany,
                    'equipmentTypes' => $equipmentTypes,
                ],
                'defaults' => [
                    'company_id' => $company->id,
                    'company_site_id' => $company->sites->first()?->id,
                ],
            ],
        ];
    }

    private function buildSiteDependencyAlert(CompanySite $site): ?array
    {
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
        ])->filter()->values();

        if ($references->isEmpty()) {
            return null;
        }

        return [
            'title' => 'Prima di cancellare la sede devi riallineare i collegamenti attivi.',
            'references' => $references->all(),
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

    private function buildWorkplaceDependencyAlert(\App\Models\Workplace $workplace): ?array
    {
        $reference = $this->buildDependencyReference(
            'Lavoratori',
            'lavoratori',
            $workplace->workerExposures->map(fn ($exposure) => trim(($exposure->worker->first_name ?? '').' '.($exposure->worker->last_name ?? ''))),
        );

        if ($reference === null) {
            return null;
        }

        return [
            'title' => 'Prima di cancellare il luogo devi riallineare le assegnazioni ancora collegate.',
            'references' => [$reference],
        ];
    }

    private function buildWorkerDependencyAlert(\App\Models\Worker $worker): ?array
    {
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
            $worker->risk_profile_items_count > 0 ? [
                'key' => 'risk_profile',
                'label' => 'Profilo rischio',
                'count' => (int) $worker->risk_profile_items_count,
                'items' => [],
                'has_more' => false,
            ] : null,
            $worker->risk_measures_count > 0 ? [
                'key' => 'risk_measures',
                'label' => 'Misure',
                'count' => (int) $worker->risk_measures_count,
                'items' => [],
                'has_more' => false,
            ] : null,
        ])->filter()->values();

        if ($references->isEmpty()) {
            return null;
        }

        return [
            'title' => 'Prima di cancellare il lavoratore devi riallineare i collegamenti ancora attivi.',
            'references' => $references->all(),
        ];
    }

    private function buildEquipmentDependencyAlert(\App\Models\EquipmentAsset $asset): ?array
    {
        $reference = $this->buildDependencyReference(
            'Lavoratori',
            'workers',
            $asset->workerExposures->map(fn ($exposure) => trim(($exposure->worker->first_name ?? '').' '.($exposure->worker->last_name ?? ''))),
        );

        if ($reference === null) {
            return null;
        }

        return [
            'title' => 'Prima di cancellare il macchinario devi riallineare le esposizioni ancora collegate.',
            'references' => [$reference],
        ];
    }
}
