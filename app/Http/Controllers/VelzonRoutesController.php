<?php

namespace App\Http\Controllers;

use App\Models\EquipmentType;
use App\Models\JobRole;
use App\Models\RiskCatalogItem;
use App\Models\RiskSourceLink;
use App\Models\WorkplaceType;
use App\Support\CurrentTenantResolver;
use App\Support\OperationalWorkspaceBuilder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VelzonRoutesController extends Controller
{
    public function dashboard(
        Request $request,
        CurrentTenantResolver $tenantResolver,
        OperationalWorkspaceBuilder $workspaceBuilder,
    ): Response {
        $tenant = $tenantResolver->resolve($request->user());
        $focus = $request->string('focus')->toString() ?: 'all';

        abort_unless(in_array($focus, [
            'all',
            'urgent',
            'deadlines',
            'follow_up',
            'reviews',
        ], true), 404);

        return Inertia::render('dashboard/index', $workspaceBuilder->buildForTenant($tenant, $focus));
    }

    public function sicurezzachiara_ui_reference(): Response
    {
        return Inertia::render('sicurezzachiara/ui-reference');
    }

    public function sicurezzachiara_method(
        Request $request,
        CurrentTenantResolver $tenantResolver,
    ): Response {
        $tenant = $tenantResolver->resolve($request->user());

        $visibleRiskCatalog = RiskCatalogItem::query()
            ->where(function ($query) use ($tenant) {
                $query->where('source', RiskCatalogItem::SOURCE_CORE)
                    ->orWhere('tenant_id', $tenant->id);
            });

        $visibleJobRoles = JobRole::query()
            ->where(function ($query) use ($tenant) {
                $query->where('source', JobRole::SOURCE_CORE)
                    ->orWhere('tenant_id', $tenant->id);
            });

        $visibleEquipmentTypes = EquipmentType::query()
            ->where(function ($query) use ($tenant) {
                $query->where('source', EquipmentType::SOURCE_CORE)
                    ->orWhere('tenant_id', $tenant->id);
            });

        $visibleWorkplaceTypes = WorkplaceType::query()
            ->where(function ($query) use ($tenant) {
                $query->where('source', WorkplaceType::SOURCE_CORE)
                    ->orWhere('tenant_id', $tenant->id);
            });

        $summary = [
            'riskCatalog' => [
                'count' => (clone $visibleRiskCatalog)->count(),
                'tenantCount' => (clone $visibleRiskCatalog)->where('source', RiskCatalogItem::SOURCE_TENANT)->count(),
                'route' => route('risk-catalog.index'),
                'createRoute' => route('risk-catalog.create'),
                'label' => 'Rischi',
                'description' => 'Normalizza i rischi che SicurezzaChiara puo\' dedurre da mansioni, macchinari e luoghi.',
            ],
            'jobRoles' => [
                'count' => (clone $visibleJobRoles)->count(),
                'tenantCount' => (clone $visibleJobRoles)->where('source', JobRole::SOURCE_TENANT)->count(),
                'route' => route('job-roles.index'),
                'createRoute' => route('job-roles.create'),
                'label' => 'Mansioni',
                'description' => 'Definisce le mansioni tipo riusabili che alimentano il profilo rischio dei lavoratori.',
            ],
            'equipmentTypes' => [
                'count' => (clone $visibleEquipmentTypes)->count(),
                'tenantCount' => (clone $visibleEquipmentTypes)->where('source', EquipmentType::SOURCE_TENANT)->count(),
                'route' => route('equipment-types.index'),
                'createRoute' => route('equipment-types.create'),
                'label' => 'Tipologie macchinario',
                'description' => 'Configura le tipologie riusabili che diventano sorgenti di rischio nelle aziende reali.',
            ],
            'workplaceTypes' => [
                'count' => (clone $visibleWorkplaceTypes)->count(),
                'tenantCount' => (clone $visibleWorkplaceTypes)->where('source', WorkplaceType::SOURCE_TENANT)->count(),
                'route' => route('workplace-types.index'),
                'createRoute' => route('workplace-types.create'),
                'label' => 'Tipologie luogo',
                'description' => 'Prepara i luoghi tipo da cui SicurezzaChiara deduce contesto, esposizioni e rischi attesi.',
            ],
        ];

        $linksSummary = [
            [
                'label' => 'Mansione -> rischi',
                'count' => RiskSourceLink::query()->where('sourceable_type', JobRole::class)->count(),
                'status' => 'Operativo',
                'detail' => 'I collegamenti si governano oggi dal dettaglio del rischio.',
            ],
            [
                'label' => 'Macchinario -> rischi',
                'count' => RiskSourceLink::query()->where('sourceable_type', EquipmentType::class)->count(),
                'status' => 'Operativo',
                'detail' => 'Le tipologie macchinario alimentano i mapping senza aprire moduli specialistici separati.',
            ],
            [
                'label' => 'Luogo -> rischi',
                'count' => RiskSourceLink::query()->where('sourceable_type', WorkplaceType::class)->count(),
                'status' => 'Operativo',
                'detail' => 'Le tipologie luogo restano sorgenti di rischio leggibili e contestuali.',
            ],
            [
                'label' => 'Rischio -> misure',
                'count' => (clone $visibleRiskCatalog)
                    ->whereNotNull('expected_measures')
                    ->get()
                    ->filter(fn (RiskCatalogItem $risk) => filled($risk->expected_measures))
                    ->count(),
                'status' => 'Nel rischio',
                'detail' => 'Le misure attese si configurano oggi dentro il rischio, non in un catalogo autonomo.',
            ],
        ];

        return Inertia::render('sicurezzachiara/method/Index', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ],
            'methodSummary' => $summary,
            'linksSummary' => $linksSummary,
            'methodJourney' => [
                'title' => 'Come SicurezzaChiara costruisce il metodo',
                'steps' => [
                    'Definisci o rifinisci i cataloghi riusabili.',
                    'Aggancia i rischi alle sorgenti: mansioni, macchinari e luoghi.',
                    'Completa dentro il rischio le misure attese piu\' utili.',
                    'Applica poi il metodo alle aziende reali dal workspace operativo.',
                ],
            ],
            'operationalBoundary' => [
                'title' => 'Cosa resta in Aziende',
                'items' => [
                    'configurazione dati essenziali e contesto reale',
                    'primo profilo rischio leggibile',
                    'misure, registri famiglia e scadenze operative',
                    'consultazione del DVR light come output vivo',
                ],
            ],
        ]);
    }
}
