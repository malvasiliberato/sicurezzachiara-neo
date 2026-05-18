<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureTenantCanManageData;
use App\Http\Requests\StoreRiskMeasureRequest;
use App\Http\Requests\UpdateRiskMeasureRequest;
use App\Models\Company;
use App\Models\RiskMeasure;
use App\Models\RiskProfileItem;
use App\Models\Tenant;
use App\Models\Worker;
use App\Support\AuditLogger;
use App\Support\CurrentTenantResolver;
use App\Support\RiskCoverageResolver;
use App\Support\RiskExpectedMeasureResolver;
use App\Support\RiskProfileBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class RiskMeasureController extends Controller
{
    public function __construct()
    {
        $this->middleware(EnsureTenantCanManageData::class)->only([
            'storeCompany',
            'updateCompany',
            'destroyCompany',
            'storeWorker',
            'updateWorker',
            'destroyWorker',
        ]);
    }

    public function showCompany(
        Request $request,
        Company $company,
        RiskProfileItem $riskProfileItem,
        CurrentTenantResolver $tenantResolver,
        RiskProfileBuilder $riskProfileBuilder,
    ): Response {
        $tenant = $tenantResolver->resolve($request->user());
        $company = $this->companyForTenant($tenant, $company);
        $riskProfileBuilder->rebuildCompany($company);
        $riskProfileItem = $this->profileItemForCompany($company, $riskProfileItem);

        return $this->renderManagePage(
            tenant: $tenant,
            parent: $company,
            parentType: 'company',
            riskProfileItem: $riskProfileItem,
        );
    }

    public function storeCompany(
        StoreRiskMeasureRequest $request,
        Company $company,
        RiskProfileItem $riskProfileItem,
        CurrentTenantResolver $tenantResolver,
        RiskProfileBuilder $riskProfileBuilder,
        RiskCoverageResolver $riskCoverageResolver,
        RiskExpectedMeasureResolver $riskExpectedMeasureResolver,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $company = $this->companyForTenant($tenant, $company);
        $riskProfileBuilder->rebuildCompany($company);
        $riskProfileItem = $this->profileItemForCompany($company, $riskProfileItem);

        $riskMeasure = $company->riskMeasures()->create(
            $this->measurePayload($request->validated(), $riskProfileItem, $riskExpectedMeasureResolver) + [
                'risk_catalog_item_id' => $riskProfileItem->risk_catalog_item_id,
            ],
        );
        $auditLogger->log(
            $tenant,
            $request->user(),
            'risk_measure.created',
            $riskMeasure,
            'Creata misura per rischio aziendale '.$company->name,
            [
                'parent_type' => 'company',
                'parent_id' => $company->id,
                'parent_name' => $company->name,
                'risk_catalog_item_id' => $riskProfileItem->risk_catalog_item_id,
                'measure_title' => $riskMeasure->title,
                'family' => $riskMeasure->family,
                'status' => $riskMeasure->status,
            ],
        );

        $riskCoverageResolver->syncForProfileableRisk($company, $riskProfileItem->risk_catalog_item_id);

        return redirect()
            ->route('companies.risk-profile.measures.show', [$company, $riskProfileItem])
            ->with('success', 'Misura collegata al rischio aziendale.');
    }

    public function updateCompany(
        UpdateRiskMeasureRequest $request,
        Company $company,
        RiskProfileItem $riskProfileItem,
        RiskMeasure $riskMeasure,
        CurrentTenantResolver $tenantResolver,
        RiskProfileBuilder $riskProfileBuilder,
        RiskCoverageResolver $riskCoverageResolver,
        RiskExpectedMeasureResolver $riskExpectedMeasureResolver,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $company = $this->companyForTenant($tenant, $company);
        $riskProfileBuilder->rebuildCompany($company);
        $riskProfileItem = $this->profileItemForCompany($company, $riskProfileItem);
        $riskMeasure = $this->measureForProfileItem($riskProfileItem, $company, $riskMeasure);

        $riskMeasure->update($this->measurePayload($request->validated(), $riskProfileItem, $riskExpectedMeasureResolver));
        $auditLogger->log(
            $tenant,
            $request->user(),
            'risk_measure.updated',
            $riskMeasure,
            'Aggiornata misura per rischio aziendale '.$company->name,
            [
                'parent_type' => 'company',
                'parent_id' => $company->id,
                'parent_name' => $company->name,
                'risk_catalog_item_id' => $riskProfileItem->risk_catalog_item_id,
                'measure_title' => $riskMeasure->title,
                'family' => $riskMeasure->family,
                'status' => $riskMeasure->status,
            ],
        );

        $riskCoverageResolver->syncForProfileableRisk($company, $riskProfileItem->risk_catalog_item_id);

        return redirect()
            ->route('companies.risk-profile.measures.show', [$company, $riskProfileItem])
            ->with('success', 'Misura aggiornata correttamente.');
    }

    public function destroyCompany(
        Request $request,
        Company $company,
        RiskProfileItem $riskProfileItem,
        RiskMeasure $riskMeasure,
        CurrentTenantResolver $tenantResolver,
        RiskProfileBuilder $riskProfileBuilder,
        RiskCoverageResolver $riskCoverageResolver,
        RiskExpectedMeasureResolver $riskExpectedMeasureResolver,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $company = $this->companyForTenant($tenant, $company);
        $riskProfileBuilder->rebuildCompany($company);
        $riskProfileItem = $this->profileItemForCompany($company, $riskProfileItem);
        $riskMeasure = $this->measureForProfileItem($riskProfileItem, $company, $riskMeasure);

        $auditLogger->log(
            $tenant,
            $request->user(),
            'risk_measure.removed',
            $riskMeasure,
            'Rimossa misura per rischio aziendale '.$company->name,
            [
                'parent_type' => 'company',
                'parent_id' => $company->id,
                'parent_name' => $company->name,
                'risk_catalog_item_id' => $riskProfileItem->risk_catalog_item_id,
                'measure_title' => $riskMeasure->title,
                'family' => $riskMeasure->family,
                'status' => $riskMeasure->status,
            ],
        );
        $riskMeasure->delete();

        $riskCoverageResolver->syncForProfileableRisk($company, $riskProfileItem->risk_catalog_item_id);

        return redirect()
            ->route('companies.risk-profile.measures.show', [$company, $riskProfileItem])
            ->with('success', 'Misura rimossa correttamente.');
    }

    public function showWorker(
        Request $request,
        Worker $worker,
        RiskProfileItem $riskProfileItem,
        CurrentTenantResolver $tenantResolver,
        RiskProfileBuilder $riskProfileBuilder,
    ): Response {
        $tenant = $tenantResolver->resolve($request->user());
        $worker = $this->workerForTenant($tenant, $worker);
        $riskProfileBuilder->rebuildWorker($worker);
        $riskProfileItem = $this->profileItemForWorker($worker, $riskProfileItem);

        return $this->renderManagePage(
            tenant: $tenant,
            parent: $worker,
            parentType: 'worker',
            riskProfileItem: $riskProfileItem,
        );
    }

    public function storeWorker(
        StoreRiskMeasureRequest $request,
        Worker $worker,
        RiskProfileItem $riskProfileItem,
        CurrentTenantResolver $tenantResolver,
        RiskProfileBuilder $riskProfileBuilder,
        RiskCoverageResolver $riskCoverageResolver,
        RiskExpectedMeasureResolver $riskExpectedMeasureResolver,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $worker = $this->workerForTenant($tenant, $worker);
        $riskProfileBuilder->rebuildWorker($worker);
        $riskProfileItem = $this->profileItemForWorker($worker, $riskProfileItem);

        $riskMeasure = $worker->riskMeasures()->create(
            $this->measurePayload($request->validated(), $riskProfileItem, $riskExpectedMeasureResolver) + [
                'risk_catalog_item_id' => $riskProfileItem->risk_catalog_item_id,
            ],
        );
        $auditLogger->log(
            $tenant,
            $request->user(),
            'risk_measure.created',
            $riskMeasure,
            'Creata misura per rischio lavoratore '.$worker->full_name,
            [
                'parent_type' => 'worker',
                'parent_id' => $worker->id,
                'parent_name' => $worker->full_name,
                'risk_catalog_item_id' => $riskProfileItem->risk_catalog_item_id,
                'measure_title' => $riskMeasure->title,
                'family' => $riskMeasure->family,
                'status' => $riskMeasure->status,
            ],
        );

        $riskCoverageResolver->syncForProfileableRisk($worker, $riskProfileItem->risk_catalog_item_id);

        return redirect()
            ->route('workers.risk-profile.measures.show', [$worker, $riskProfileItem])
            ->with('success', 'Misura collegata al rischio del lavoratore.');
    }

    public function updateWorker(
        UpdateRiskMeasureRequest $request,
        Worker $worker,
        RiskProfileItem $riskProfileItem,
        RiskMeasure $riskMeasure,
        CurrentTenantResolver $tenantResolver,
        RiskProfileBuilder $riskProfileBuilder,
        RiskCoverageResolver $riskCoverageResolver,
        RiskExpectedMeasureResolver $riskExpectedMeasureResolver,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $worker = $this->workerForTenant($tenant, $worker);
        $riskProfileBuilder->rebuildWorker($worker);
        $riskProfileItem = $this->profileItemForWorker($worker, $riskProfileItem);
        $riskMeasure = $this->measureForProfileItem($riskProfileItem, $worker, $riskMeasure);

        $riskMeasure->update($this->measurePayload($request->validated(), $riskProfileItem, $riskExpectedMeasureResolver));
        $auditLogger->log(
            $tenant,
            $request->user(),
            'risk_measure.updated',
            $riskMeasure,
            'Aggiornata misura per rischio lavoratore '.$worker->full_name,
            [
                'parent_type' => 'worker',
                'parent_id' => $worker->id,
                'parent_name' => $worker->full_name,
                'risk_catalog_item_id' => $riskProfileItem->risk_catalog_item_id,
                'measure_title' => $riskMeasure->title,
                'family' => $riskMeasure->family,
                'status' => $riskMeasure->status,
            ],
        );

        $riskCoverageResolver->syncForProfileableRisk($worker, $riskProfileItem->risk_catalog_item_id);

        return redirect()
            ->route('workers.risk-profile.measures.show', [$worker, $riskProfileItem])
            ->with('success', 'Misura aggiornata correttamente.');
    }

    public function destroyWorker(
        Request $request,
        Worker $worker,
        RiskProfileItem $riskProfileItem,
        RiskMeasure $riskMeasure,
        CurrentTenantResolver $tenantResolver,
        RiskProfileBuilder $riskProfileBuilder,
        RiskCoverageResolver $riskCoverageResolver,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $tenant = $tenantResolver->resolve($request->user());
        $worker = $this->workerForTenant($tenant, $worker);
        $riskProfileBuilder->rebuildWorker($worker);
        $riskProfileItem = $this->profileItemForWorker($worker, $riskProfileItem);
        $riskMeasure = $this->measureForProfileItem($riskProfileItem, $worker, $riskMeasure);

        $auditLogger->log(
            $tenant,
            $request->user(),
            'risk_measure.removed',
            $riskMeasure,
            'Rimossa misura per rischio lavoratore '.$worker->full_name,
            [
                'parent_type' => 'worker',
                'parent_id' => $worker->id,
                'parent_name' => $worker->full_name,
                'risk_catalog_item_id' => $riskProfileItem->risk_catalog_item_id,
                'measure_title' => $riskMeasure->title,
                'family' => $riskMeasure->family,
                'status' => $riskMeasure->status,
            ],
        );
        $riskMeasure->delete();

        $riskCoverageResolver->syncForProfileableRisk($worker, $riskProfileItem->risk_catalog_item_id);

        return redirect()
            ->route('workers.risk-profile.measures.show', [$worker, $riskProfileItem])
            ->with('success', 'Misura rimossa correttamente.');
    }

    private function renderManagePage(
        Tenant $tenant,
        Company|Worker $parent,
        string $parentType,
        RiskProfileItem $riskProfileItem,
    ): Response {
        $riskProfileItem->loadMissing('riskCatalogItem.category');

        $measures = RiskMeasure::query()
            ->where('profileable_type', $parent::class)
            ->where('profileable_id', $parent->getKey())
            ->where('risk_catalog_item_id', $riskProfileItem->risk_catalog_item_id)
            ->orderByRaw(
                'case status when ? then 0 when ? then 1 else 2 end',
                [RiskMeasure::STATUS_NOT_IMPLEMENTED, RiskMeasure::STATUS_TO_VERIFY],
            )
            ->orderBy('due_date')
            ->orderBy('title')
            ->get();
        $expectedMeasures = app(RiskExpectedMeasureResolver::class)
            ->snapshotForRisk($riskProfileItem->riskCatalogItem, $measures);
        $summary = [
            'totalMeasures' => $measures->count(),
            'implementedMeasures' => $measures->where('status', RiskMeasure::STATUS_IMPLEMENTED)->count(),
            'toVerifyMeasures' => $measures->where('status', RiskMeasure::STATUS_TO_VERIFY)->count(),
            'notImplementedMeasures' => $measures->where('status', RiskMeasure::STATUS_NOT_IMPLEMENTED)->count(),
            'familyBreakdown' => [
                'training' => $measures->where('family', RiskMeasure::FAMILY_TRAINING)->count(),
                'medical' => $measures->where('family', RiskMeasure::FAMILY_MEDICAL)->count(),
                'dpi' => $measures->where('family', RiskMeasure::FAMILY_DPI)->count(),
                'operational' => $measures->whereIn('family', [
                    RiskMeasure::FAMILY_ORGANIZATIONAL,
                    RiskMeasure::FAMILY_TECHNICAL,
                ])->count(),
            ],
        ];
        $companyId = $parentType === 'company' ? $parent->id : $parent->company?->id;
        $measureBridge = $this->buildMeasureBridge(
            parent: $parent,
            parentType: $parentType,
            riskProfileItem: $riskProfileItem,
            summary: $summary,
            expectedMeasures: $expectedMeasures,
            profileRoute: $parentType === 'company'
                ? route('companies.risk-profile.show', $parent)
                : route('workers.risk-profile.show', $parent),
            reviewRoute: $parentType === 'company'
                ? route('companies.risk-profile.review.show', [$parent, $riskProfileItem])
                : route('workers.risk-profile.review.show', [$parent, $riskProfileItem]),
            workspaceRoute: route('measure-registries.index', array_filter([
                'company_id' => $companyId,
                'risk_profile_item_id' => $riskProfileItem->id,
                'origin' => 'risk_measures',
                'focus' => ($summary['toVerifyMeasures'] + $summary['notImplementedMeasures']) > 0 ? 'follow_up' : 'reviews',
                'scope' => 'attention',
            ], fn ($value) => $value !== null && $value !== '')),
            dashboardRoute: route('dashboard', [
                'focus' => ($summary['toVerifyMeasures'] + $summary['notImplementedMeasures']) > 0 ? 'follow_up' : 'reviews',
            ]),
            workerRoute: $parentType === 'worker' ? route('workers.show', $parent) : null,
            companyRoute: $parentType === 'company'
                ? route('companies.show', $parent)
                : ($parent->company ? route('companies.show', $parent->company) : null),
        );

        return Inertia::render('sicurezzachiara/risk-measures/Manage', [
            'tenant' => $tenant->only(['id', 'name', 'slug']),
            'parentType' => $parentType,
            'parent' => $parentType === 'company'
                ? [
                    'id' => $parent->id,
                    'name' => $parent->name,
                ]
                : [
                    'id' => $parent->id,
                    'name' => $parent->full_name,
                    'company_name' => $parent->company?->name,
                ],
            'profileItem' => [
                'id' => $riskProfileItem->id,
                'status' => $riskProfileItem->status,
                'priority' => $riskProfileItem->priority,
                'risk_catalog_item_id' => $riskProfileItem->risk_catalog_item_id,
                'risk_catalog_item' => [
                    'name' => $riskProfileItem->riskCatalogItem?->name,
                    'description' => $riskProfileItem->riskCatalogItem?->description,
                    'category' => [
                        'name' => $riskProfileItem->riskCatalogItem?->category?->name,
                    ],
                ],
            ],
            'measures' => $measures->map(fn (RiskMeasure $measure) => [
                ...$measure->toArray(),
                'details_summary' => $this->detailsSummary($measure),
            ])->values(),
            'expectedMeasures' => $expectedMeasures,
            'formOptions' => [
                'families' => [
                    ['value' => RiskMeasure::FAMILY_ORGANIZATIONAL, 'label' => 'Organizzativa'],
                    ['value' => RiskMeasure::FAMILY_TECHNICAL, 'label' => 'Tecnica'],
                    ['value' => RiskMeasure::FAMILY_DPI, 'label' => 'DPI'],
                    ['value' => RiskMeasure::FAMILY_TRAINING, 'label' => 'Formazione'],
                    ['value' => RiskMeasure::FAMILY_MEDICAL, 'label' => 'Visite mediche'],
                ],
                'statuses' => [
                    ['value' => RiskMeasure::STATUS_NOT_IMPLEMENTED, 'label' => 'Non attuata'],
                    ['value' => RiskMeasure::STATUS_TO_VERIFY, 'label' => 'Da verificare'],
                    ['value' => RiskMeasure::STATUS_IMPLEMENTED, 'label' => 'Attuata'],
                ],
            ],
            'summary' => $summary,
            'measureBridge' => $measureBridge,
            'copy' => [
                'expectedMeasuresHelper' => 'Qui il motore mostra le misure attese per questo rischio. Le misure reali possono coprirle direttamente, in equivalenza di famiglia o restare libere come integrazioni del consulente.',
                'linkedMeasuresHelper' => 'Modello unico MVP delle misure collegate al rischio. DPI, formazione, visite mediche e presidi tecnico-organizzativi vivono qui come famiglie operative dello stesso workspace.',
                'newMeasureHelper' => 'Inserisci una misura collegata al rischio. Le famiglie DPI, formazione e visite restano parte dello stesso workspace operativo.',
            ],
        ]);
    }

    private function buildMeasureBridge(
        Company|Worker $parent,
        string $parentType,
        RiskProfileItem $riskProfileItem,
        array $summary,
        array $expectedMeasures,
        string $profileRoute,
        string $reviewRoute,
        string $workspaceRoute,
        string $dashboardRoute,
        ?string $workerRoute,
        ?string $companyRoute,
    ): array {
        $gapCount = (int) ($expectedMeasures['summary']['missing_count'] ?? 0)
            + (int) ($expectedMeasures['summary']['partial_count'] ?? 0);
        $pendingMeasures = (int) ($summary['toVerifyMeasures'] ?? 0) + (int) ($summary['notImplementedMeasures'] ?? 0);

        $decision = match (true) {
            $gapCount > 0 => [
                'label' => 'Completa i presidi attesi del rischio',
                'helper' => 'Il rischio porta ancora con se\' coperture mancanti o parziali: qui conviene chiudere i gap prima di congelare la review.',
                'tone' => 'danger',
                'laneLabel' => 'Corsia copertura',
                'actionLabel' => 'Apri gestione misure',
                'actionRoute' => $reviewRoute,
            ],
            $pendingMeasures > 0 => [
                'label' => 'Riallinea le misure ancora aperte',
                'helper' => 'Le misure esistono ma alcune sono ancora da attuare o verificare: conviene lavorarle prima di tornare alla sola review.',
                'tone' => 'warning',
                'laneLabel' => 'Corsia misure',
                'actionLabel' => 'Consolida misure aperte',
                'actionRoute' => $reviewRoute,
            ],
            $summary['totalMeasures'] === 0 => [
                'label' => 'Inserisci il primo presidio reale',
                'helper' => 'Non risultano ancora misure collegate: puoi partire da un presidio atteso o creare una misura libera motivata dal consulente.',
                'tone' => 'info',
                'laneLabel' => 'Corsia copertura',
                'actionLabel' => 'Aggiungi prima misura',
                'actionRoute' => $reviewRoute,
            ],
            default => [
                'label' => 'Copertura pronta per la review finale',
                'helper' => 'Il rischio dispone gia\' di presidi leggibili: il passo successivo piu\' utile e\' confermare lo stato operativo in review.',
                'tone' => 'success',
                'laneLabel' => 'Corsia review',
                'actionLabel' => 'Torna alla review',
                'actionRoute' => $reviewRoute,
            ],
        };

        $operationalQueue = [
            [
                'key' => 'coverage',
                'label' => 'Colma presidi attesi',
                'count' => $gapCount,
                'status' => $gapCount > 0 ? 'open' : 'aligned',
                'helper' => $gapCount > 0
                    ? 'Prima chiudi i gap attesi o le coperture parziali del rischio.'
                    : 'I presidi attesi risultano gia\' allineati.',
                'actionLabel' => 'Rileggi review',
                'tone' => $gapCount > 0 ? 'danger' : 'secondary',
                'laneLabel' => 'Corsia copertura',
                'actionRoute' => $reviewRoute,
            ],
            [
                'key' => 'measures',
                'label' => 'Consolida misure aperte',
                'count' => $pendingMeasures,
                'status' => $pendingMeasures > 0 ? 'open' : 'aligned',
                'helper' => $pendingMeasures > 0
                    ? 'Restano misure non attuate o da verificare prima della chiusura consulenziale.'
                    : 'Non risultano misure pendenti su questo rischio.',
                'actionLabel' => 'Apri registri contestuali',
                'tone' => $pendingMeasures > 0 ? 'warning' : 'secondary',
                'laneLabel' => 'Corsia misure',
                'actionRoute' => $workspaceRoute,
            ],
            [
                'key' => 'review',
                'label' => 'Rientra nella review finale',
                'count' => (int) ($summary['implementedMeasures'] ?? 0),
                'status' => ($gapCount === 0 && $pendingMeasures === 0) ? 'ready' : 'in_progress',
                'helper' => ($gapCount === 0 && $pendingMeasures === 0)
                    ? 'Il rischio ha presidi leggibili: la review puo\' essere riallineata o chiusa.'
                    : 'Torna in review quando copertura e stato misure sono piu\' allineati.',
                'actionLabel' => 'Apri review',
                'tone' => ($gapCount === 0 && $pendingMeasures === 0) ? 'success' : 'primary',
                'laneLabel' => 'Corsia review',
                'actionRoute' => $reviewRoute,
            ],
        ];

        return [
            'parentLabel' => $parentType === 'company' ? $parent->name : $parent->full_name,
            'decision' => $decision,
            'operationalQueue' => $operationalQueue,
            'stats' => [
                'pendingMeasures' => $pendingMeasures,
                'gapCount' => $gapCount,
                'implementedMeasures' => (int) ($summary['implementedMeasures'] ?? 0),
                'expectedMeasures' => (int) ($expectedMeasures['summary']['expected_count'] ?? 0),
            ],
            'actions' => [
                'profileRoute' => $profileRoute,
                'reviewRoute' => $reviewRoute,
                'workspaceRoute' => $workspaceRoute,
                'dashboardRoute' => $dashboardRoute,
                'workerRoute' => $workerRoute,
                'companyRoute' => $companyRoute,
            ],
            'coverageLabel' => $gapCount > 0
                ? 'Copertura attesa incompleta'
                : ($pendingMeasures > 0 ? 'Copertura in consolidamento' : 'Copertura attesa allineata'),
        ];
    }

    private function measurePayload(
        array $validated,
        RiskProfileItem $riskProfileItem,
        RiskExpectedMeasureResolver $riskExpectedMeasureResolver,
    ): array {
        $details = collect($validated['details'] ?? [])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        return [
            ...$validated,
            'expected_measure_code' => $riskExpectedMeasureResolver->sanitizeExpectedMeasureCode(
                $riskProfileItem->riskCatalogItem,
                $validated['expected_measure_code'] ?? null,
            ),
            'details' => $details === [] ? null : $details,
            'completed_at' => ($validated['status'] ?? null) === RiskMeasure::STATUS_IMPLEMENTED
                ? Carbon::now()
                : null,
        ];
    }

    private function detailsSummary(RiskMeasure $measure): ?string
    {
        $details = collect($measure->details ?? []);

        return match ($measure->family) {
            RiskMeasure::FAMILY_TRAINING => collect([
                $details->get('provider'),
                $details->get('delivery_mode'),
                $details->get('valid_until') ? 'valida fino al '.$details->get('valid_until') : null,
            ])->filter()->implode(' | '),
            RiskMeasure::FAMILY_MEDICAL => collect([
                $details->get('physician'),
                $details->get('protocol'),
                $details->get('valid_until') ? 'scadenza '.$details->get('valid_until') : null,
            ])->filter()->implode(' | '),
            RiskMeasure::FAMILY_DPI => collect([
                $details->get('item_name'),
                $details->get('category'),
                $details->get('valid_until') ? 'sostituzione '.$details->get('valid_until') : null,
            ])->filter()->implode(' | '),
            default => collect([
                $details->get('owner'),
                $details->get('verification_method'),
            ])->filter()->implode(' | '),
        } ?: null;
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

    private function profileItemForCompany(Company $company, RiskProfileItem $riskProfileItem): RiskProfileItem
    {
        abort_unless(
            $riskProfileItem->profileable_type === Company::class
            && (int) $riskProfileItem->profileable_id === (int) $company->id,
            404,
        );

        return $riskProfileItem;
    }

    private function profileItemForWorker(Worker $worker, RiskProfileItem $riskProfileItem): RiskProfileItem
    {
        abort_unless(
            $riskProfileItem->profileable_type === Worker::class
            && (int) $riskProfileItem->profileable_id === (int) $worker->id,
            404,
        );

        return $riskProfileItem;
    }

    private function measureForProfileItem(
        RiskProfileItem $riskProfileItem,
        Company|Worker $parent,
        RiskMeasure $riskMeasure,
    ): RiskMeasure {
        abort_unless(
            $riskMeasure->profileable_type === $parent::class
            && (int) $riskMeasure->profileable_id === (int) $parent->getKey()
            && (int) $riskMeasure->risk_catalog_item_id === (int) $riskProfileItem->risk_catalog_item_id,
            404,
        );

        return $riskMeasure;
    }
}
