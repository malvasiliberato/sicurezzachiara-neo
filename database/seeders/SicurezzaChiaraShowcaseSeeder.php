<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanySite;
use App\Models\EquipmentAsset;
use App\Models\EquipmentType;
use App\Models\JobRole;
use App\Models\RiskCatalogItem;
use App\Models\RiskCategory;
use App\Models\RiskMeasure;
use App\Models\RiskProfileItem;
use App\Models\RiskSourceLink;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerEquipmentExposure;
use App\Models\WorkerJobRoleAssignment;
use App\Models\WorkerWorkplaceExposure;
use App\Models\Workplace;
use App\Models\WorkplaceType;
use App\Support\RiskProfileBuilder;
use App\Support\RiskProfileOverrideManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SicurezzaChiaraShowcaseSeeder extends Seeder
{
    /**
     * Demo/staging only.
     * Non eseguire automaticamente su area/produzione.
     */
    public function run(): void
    {
        $this->call([
            SicurezzaChiaraCoreJobRolesSeeder::class,
            SicurezzaChiaraCoreEquipmentTypesSeeder::class,
            SicurezzaChiaraCoreWorkplaceTypesSeeder::class,
            SicurezzaChiaraCoreRiskCategoriesSeeder::class,
            SicurezzaChiaraCoreRisksSeeder::class,
            SicurezzaChiaraCoreSourceRiskMappingsSeeder::class,
        ]);

        $owner = $this->upsertUser(
            'owner.showcase@sicurezzachiara.test',
            'Liberato Demo Workspace',
        );

        $collaborator = $this->upsertUser(
            'collaboratore.showcase@sicurezzachiara.test',
            'Giulia Ferri',
        );

        $tenant = Tenant::query()->updateOrCreate(
            ['slug' => 'sicurezzachiara-showcase'],
            [
                'name' => 'SicurezzaChiara Showcase',
                'owner_user_id' => $owner->id,
            ],
        );

        $owner->forceFill([
            'current_tenant_id' => $tenant->id,
        ])->save();

        $collaborator->forceFill([
            'current_tenant_id' => $tenant->id,
        ])->save();

        TenantMembership::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'user_id' => $owner->id,
            ],
            [
                'role' => TenantMembership::ROLE_OWNER,
                'joined_at' => now(),
            ],
        );

        TenantMembership::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'user_id' => $collaborator->id,
            ],
            [
                'role' => TenantMembership::ROLE_ADMIN,
                'joined_at' => now(),
            ],
        );

        $companies = [
            [
                'name' => 'Metalnova S.r.l.',
                'legal_name' => 'Metalnova S.r.l.',
                'industry' => 'Metalmeccanica',
                'city' => 'Modena',
                'province' => 'MO',
                'contact_email' => 'sicurezza@metalnova.test',
                'contact_phone' => '059100100',
                'notes' => 'Cliente manifatturiero con reparto produzione e logistica interna.',
                'sites' => [
                    [
                        'name' => 'Stabilimento principale',
                        'site_code' => 'HQ',
                        'is_headquarters' => true,
                        'address_line' => 'Via dell\'Industria 14',
                        'postal_code' => '41122',
                        'city' => 'Modena',
                        'province' => 'MO',
                    ],
                    [
                        'name' => 'Magazzino ricambi',
                        'site_code' => 'MAG',
                        'is_headquarters' => false,
                        'address_line' => 'Via della Meccanica 8',
                        'postal_code' => '41012',
                        'city' => 'Carpi',
                        'province' => 'MO',
                    ],
                ],
            ],
            [
                'name' => 'Logiport Services S.r.l.',
                'legal_name' => 'Logiport Services S.r.l.',
                'industry' => 'Logistica e movimentazione merci',
                'city' => 'Bologna',
                'province' => 'BO',
                'contact_email' => 'operations@logiport.test',
                'contact_phone' => '051200200',
                'notes' => 'Cliente con piu\' sedi operative e turnazione estesa.',
                'sites' => [
                    [
                        'name' => 'Hub logistico',
                        'site_code' => 'HUB',
                        'is_headquarters' => true,
                        'address_line' => 'Interporto Lotto C',
                        'postal_code' => '40131',
                        'city' => 'Bologna',
                        'province' => 'BO',
                    ],
                    [
                        'name' => 'Deposito ovest',
                        'site_code' => 'OVEST',
                        'is_headquarters' => false,
                        'address_line' => 'Via del Carico 21',
                        'postal_code' => '40010',
                        'city' => 'Bentivoglio',
                        'province' => 'BO',
                    ],
                    [
                        'name' => 'Area cross-docking',
                        'site_code' => 'XDK',
                        'is_headquarters' => false,
                        'address_line' => 'Via dei Trasporti 3',
                        'postal_code' => '40010',
                        'city' => 'Bentivoglio',
                        'province' => 'BO',
                    ],
                ],
            ],
            [
                'name' => 'Studio Aurora S.a.s.',
                'legal_name' => 'Studio Aurora S.a.s.',
                'industry' => 'Servizi professionali',
                'city' => 'Reggio Emilia',
                'province' => 'RE',
                'contact_email' => 'amministrazione@aurora.test',
                'contact_phone' => '0522300300',
                'notes' => 'Cliente snello con sola sede direzionale.',
                'sites' => [
                    [
                        'name' => 'Sede direzionale',
                        'site_code' => 'DIR',
                        'is_headquarters' => true,
                        'address_line' => 'Viale della Consulenza 5',
                        'postal_code' => '42121',
                        'city' => 'Reggio Emilia',
                        'province' => 'RE',
                    ],
                ],
            ],
            [
                'name' => 'Verde Pulito Coop.',
                'legal_name' => 'Verde Pulito Societa\' Cooperativa',
                'industry' => 'Servizi ambientali',
                'city' => 'Parma',
                'province' => 'PR',
                'contact_email' => 'coordinamento@verdepulito.test',
                'contact_phone' => '0521400400',
                'notes' => 'Cliente inserito ma non ancora strutturato a livello di sedi operative.',
                'sites' => [],
            ],
        ];

        foreach ($companies as $companyData) {
            $sites = $companyData['sites'];
            unset($companyData['sites']);

            $company = Company::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name' => $companyData['name'],
                ],
                $companyData,
            );

            foreach ($sites as $siteData) {
                CompanySite::query()->updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'name' => $siteData['name'],
                    ],
                    $siteData,
                );
            }
        }

        $tenantJobRoles = [
            [
                'code' => 'TEN-QA',
                'name' => 'Responsabile qualita\'',
                'description' => 'Profilo tenant-level per ruoli di controllo processi e conformita\'.',
            ],
            [
                'code' => 'TEN-CROSS',
                'name' => 'Addetto cross-docking',
                'description' => 'Profilo tenant-level per attivita\' di smistamento rapido e baia.',
            ],
            [
                'code' => 'TEN-COORD',
                'name' => 'Coordinatore di cantiere interno',
                'description' => 'Profilo tenant-level per coordinamento operativo e presidio attivita\'.',
            ],
        ];

        foreach ($tenantJobRoles as $jobRoleData) {
            $tenant->jobRoles()->updateOrCreate(
                ['name' => $jobRoleData['name']],
                [
                    ...$jobRoleData,
                    'source' => JobRole::SOURCE_TENANT,
                    'is_active' => true,
                ],
            );
        }

        $tenantEquipmentTypes = [
            [
                'code' => 'TEN-CONV',
                'name' => 'Nastro trasportatore modulare',
                'description' => 'Tipologia tenant-level per linee di smistamento o trasferimento colli.',
            ],
            [
                'code' => 'TEN-SCOMP',
                'name' => 'Compattatore scarrabile',
                'description' => 'Tipologia tenant-level per mezzi e attrezzature legate a servizi ambientali.',
            ],
        ];

        foreach ($tenantEquipmentTypes as $equipmentTypeData) {
            $tenant->equipmentTypes()->updateOrCreate(
                ['name' => $equipmentTypeData['name']],
                [
                    ...$equipmentTypeData,
                    'source' => EquipmentType::SOURCE_TENANT,
                    'is_active' => true,
                ],
            );
        }

        $tenantWorkplaceTypes = [
            [
                'code' => 'TEN-BAIA',
                'name' => 'Baia di carico',
                'description' => 'Tipologia tenant-level per aree di carico/scarico mezzi e colli.',
            ],
            [
                'code' => 'TEN-VERDE',
                'name' => 'Area rimessa mezzi',
                'description' => 'Tipologia tenant-level per ricovero e presidio mezzi esterni.',
            ],
        ];

        foreach ($tenantWorkplaceTypes as $workplaceTypeData) {
            $tenant->workplaceTypes()->updateOrCreate(
                ['name' => $workplaceTypeData['name']],
                [
                    ...$workplaceTypeData,
                    'source' => WorkplaceType::SOURCE_TENANT,
                    'is_active' => true,
                ],
            );
        }

        $tenantRisks = [
            [
                'name' => 'Interferenze operative baie cross-docking',
                'code' => 'TEN-XDK-INT',
                'category' => 'Rischi organizzativi',
                'description' => 'Compresenza di mezzi, colli e operatori nelle baie a rapido smistamento.',
                'default_priority' => RiskCatalogItem::PRIORITY_HIGH,
            ],
            [
                'name' => 'Presidio mezzi in rimessa esterna',
                'code' => 'TEN-RIM-MEZZI',
                'category' => 'Rischi organizzativi',
                'description' => 'Rischio tenant-level collegato al ricovero e alle manovre in area mezzi.',
                'default_priority' => RiskCatalogItem::PRIORITY_MEDIUM,
            ],
        ];

        foreach ($tenantRisks as $tenantRiskData) {
            $category = RiskCategory::query()->where('name', $tenantRiskData['category'])->firstOrFail();

            $tenant->riskCatalogItems()->updateOrCreate(
                ['name' => $tenantRiskData['name']],
                [
                    'risk_category_id' => $category->id,
                    'source' => RiskCatalogItem::SOURCE_TENANT,
                    'code' => $tenantRiskData['code'],
                    'description' => $tenantRiskData['description'],
                    'default_priority' => $tenantRiskData['default_priority'],
                    'is_active' => true,
                ],
            );
        }

        $workers = [
            [
                'company_name' => 'Metalnova S.r.l.',
                'primary_site_name' => 'Stabilimento principale',
                'first_name' => 'Marco',
                'last_name' => 'Rossi',
                'tax_code' => 'RSSMRC82B10F257K',
                'email' => 'marco.rossi@metalnova.test',
                'status' => 'active',
                'job_roles' => [
                    ['name' => 'Operatore di produzione', 'is_primary' => true, 'notes' => 'Presidio linea assemblaggio.'],
                    ['name' => 'Responsabile qualita\'', 'is_primary' => false, 'notes' => 'Supporto controlli campione.'],
                ],
            ],
            [
                'company_name' => 'Logiport Services S.r.l.',
                'primary_site_name' => 'Area cross-docking',
                'first_name' => 'Elena',
                'last_name' => 'Ferrari',
                'tax_code' => 'FRRLNE90C55A944W',
                'email' => 'elena.ferrari@logiport.test',
                'status' => 'active',
                'job_roles' => [
                    ['name' => 'Addetto cross-docking', 'is_primary' => true, 'notes' => 'Attivita\' su baie e smistamento rapido.'],
                    ['name' => 'Addetto di magazzino', 'is_primary' => false, 'notes' => 'Copertura turni hub.'],
                ],
            ],
            [
                'company_name' => 'Studio Aurora S.a.s.',
                'primary_site_name' => 'Sede direzionale',
                'first_name' => 'Giulia',
                'last_name' => 'Neri',
                'tax_code' => 'NREGLL91D62H223O',
                'email' => 'giulia.neri@aurora.test',
                'status' => 'active',
                'job_roles' => [
                    ['name' => 'Impiegato amministrativo', 'is_primary' => true, 'notes' => 'Gestione ufficio e front-office.'],
                ],
            ],
            [
                'company_name' => 'Verde Pulito Coop.',
                'primary_site_name' => null,
                'first_name' => 'Davide',
                'last_name' => 'Orlandi',
                'tax_code' => 'RLNDVD87E12G337N',
                'email' => 'davide.orlandi@verdepulito.test',
                'status' => 'active',
                'job_roles' => [
                    ['name' => 'Coordinatore di cantiere interno', 'is_primary' => true, 'notes' => 'Cliente non ancora strutturato in sedi.'],
                ],
            ],
        ];

        foreach ($workers as $workerData) {
            $jobRoles = $workerData['job_roles'];
            $company = Company::query()
                ->where('tenant_id', $tenant->id)
                ->where('name', $workerData['company_name'])
                ->firstOrFail();
            $primarySite = $workerData['primary_site_name']
                ? CompanySite::query()->where('company_id', $company->id)->where('name', $workerData['primary_site_name'])->first()
                : null;

            unset($workerData['job_roles'], $workerData['company_name'], $workerData['primary_site_name']);

            $worker = Worker::query()->updateOrCreate(
                ['tax_code' => $workerData['tax_code']],
                [
                    ...$workerData,
                    'company_id' => $company->id,
                    'primary_site_id' => $primarySite?->id,
                ],
            );

            foreach ($jobRoles as $jobRoleAssignmentData) {
                $jobRole = JobRole::query()
                    ->where('name', $jobRoleAssignmentData['name'])
                    ->where(function ($query) use ($tenant) {
                        $query->where('source', JobRole::SOURCE_CORE)
                            ->orWhere('tenant_id', $tenant->id);
                    })
                    ->firstOrFail();

                if ($jobRoleAssignmentData['is_primary']) {
                    $worker->jobRoleAssignments()->update(['is_primary' => false]);
                }

                WorkerJobRoleAssignment::query()->updateOrCreate(
                    [
                        'worker_id' => $worker->id,
                        'job_role_id' => $jobRole->id,
                    ],
                    [
                        'is_primary' => $jobRoleAssignmentData['is_primary'],
                        'assigned_on' => now()->toDateString(),
                        'notes' => $jobRoleAssignmentData['notes'] ?? null,
                    ],
                );
            }
        }

        $equipmentAssets = [
            [
                'company_name' => 'Metalnova S.r.l.',
                'site_name' => 'Stabilimento principale',
                'equipment_type_name' => 'Pressa industriale',
                'asset_code' => 'PRS-01',
                'name' => 'Pressa piegatrice 110T',
                'manufacturer' => 'MechaForm',
                'model' => 'PF-110',
                'status' => 'active',
            ],
            [
                'company_name' => 'Logiport Services S.r.l.',
                'site_name' => 'Area cross-docking',
                'equipment_type_name' => 'Nastro trasportatore modulare',
                'asset_code' => 'NTR-03',
                'name' => 'Nastro smistamento baie',
                'manufacturer' => 'FlowMove',
                'model' => 'FM-900',
                'status' => 'active',
            ],
            [
                'company_name' => 'Verde Pulito Coop.',
                'site_name' => null,
                'equipment_type_name' => 'Compattatore scarrabile',
                'asset_code' => 'CMP-01',
                'name' => 'Compattatore mobile servizio urbano',
                'manufacturer' => 'EcoPress',
                'model' => 'EP-Compact',
                'status' => 'inactive',
            ],
        ];

        foreach ($equipmentAssets as $assetData) {
            $company = Company::query()->where('tenant_id', $tenant->id)->where('name', $assetData['company_name'])->firstOrFail();
            $site = $assetData['site_name']
                ? CompanySite::query()->where('company_id', $company->id)->where('name', $assetData['site_name'])->first()
                : null;
            $equipmentType = EquipmentType::query()
                ->where('name', $assetData['equipment_type_name'])
                ->where(fn ($query) => $query->where('source', EquipmentType::SOURCE_CORE)->orWhere('tenant_id', $tenant->id))
                ->firstOrFail();

            unset($assetData['company_name'], $assetData['site_name'], $assetData['equipment_type_name']);

            EquipmentAsset::query()->updateOrCreate(
                ['company_id' => $company->id, 'name' => $assetData['name']],
                [
                    ...$assetData,
                    'company_site_id' => $site?->id,
                    'equipment_type_id' => $equipmentType->id,
                ],
            );
        }

        $workplaces = [
            [
                'company_name' => 'Metalnova S.r.l.',
                'site_name' => 'Stabilimento principale',
                'workplace_type_name' => 'Linea produttiva',
                'code' => 'LINEA-01',
                'name' => 'Linea pressa e piega',
                'status' => 'active',
            ],
            [
                'company_name' => 'Logiport Services S.r.l.',
                'site_name' => 'Area cross-docking',
                'workplace_type_name' => 'Baia di carico',
                'code' => 'BAIA-C1',
                'name' => 'Baia carico C1',
                'status' => 'active',
            ],
            [
                'company_name' => 'Studio Aurora S.a.s.',
                'site_name' => 'Sede direzionale',
                'workplace_type_name' => 'Ufficio operativo',
                'code' => 'UFF-OPEN',
                'name' => 'Open space amministrazione',
                'status' => 'active',
            ],
        ];

        foreach ($workplaces as $workplaceData) {
            $company = Company::query()->where('tenant_id', $tenant->id)->where('name', $workplaceData['company_name'])->firstOrFail();
            $site = CompanySite::query()->where('company_id', $company->id)->where('name', $workplaceData['site_name'])->firstOrFail();
            $workplaceType = WorkplaceType::query()
                ->where('name', $workplaceData['workplace_type_name'])
                ->where(fn ($query) => $query->where('source', WorkplaceType::SOURCE_CORE)->orWhere('tenant_id', $tenant->id))
                ->firstOrFail();

            unset($workplaceData['company_name'], $workplaceData['site_name'], $workplaceData['workplace_type_name']);

            Workplace::query()->updateOrCreate(
                ['company_site_id' => $site->id, 'name' => $workplaceData['name']],
                [
                    ...$workplaceData,
                    'workplace_type_id' => $workplaceType->id,
                ],
            );
        }

        $workerExposures = [
            [
                'worker_tax_code' => 'RSSMRC82B10F257K',
                'equipment_asset_names' => ['Pressa piegatrice 110T'],
                'workplace_names' => ['Linea pressa e piega'],
            ],
            [
                'worker_tax_code' => 'FRRLNE90C55A944W',
                'equipment_asset_names' => ['Nastro smistamento baie'],
                'workplace_names' => ['Baia carico C1'],
            ],
            [
                'worker_tax_code' => 'NREGLL91D62H223O',
                'equipment_asset_names' => [],
                'workplace_names' => ['Open space amministrazione'],
            ],
        ];

        foreach ($workerExposures as $exposureData) {
            $worker = Worker::query()->where('tax_code', $exposureData['worker_tax_code'])->firstOrFail();

            foreach ($exposureData['equipment_asset_names'] as $index => $assetName) {
                $asset = EquipmentAsset::query()->where('company_id', $worker->company_id)->where('name', $assetName)->firstOrFail();

                WorkerEquipmentExposure::query()->updateOrCreate(
                    [
                        'worker_id' => $worker->id,
                        'equipment_asset_id' => $asset->id,
                    ],
                    [
                        'is_primary' => $index === 0,
                        'notes' => $index === 0 ? 'Esposizione principale nel dataset showcase.' : 'Esposizione secondaria nel dataset showcase.',
                    ],
                );
            }

            foreach ($exposureData['workplace_names'] as $index => $workplaceName) {
                $workplace = Workplace::query()
                    ->whereHas('site', fn ($query) => $query->where('company_id', $worker->company_id))
                    ->where('name', $workplaceName)
                    ->firstOrFail();

                WorkerWorkplaceExposure::query()->updateOrCreate(
                    [
                        'worker_id' => $worker->id,
                        'workplace_id' => $workplace->id,
                    ],
                    [
                        'is_primary' => $index === 0,
                        'notes' => $index === 0 ? 'Luogo operativo principale nel dataset showcase.' : 'Luogo secondario nel dataset showcase.',
                    ],
                );
            }
        }

        $riskLinks = [
            [
                'risk_name' => 'Schiacciamento e cesoiamento',
                'sourceable_type' => JobRole::class,
                'sourceable_name' => 'Operatore di produzione',
                'relevance' => RiskSourceLink::RELEVANCE_PRIMARY,
            ],
            [
                'risk_name' => 'Movimentazione manuale dei carichi',
                'sourceable_type' => JobRole::class,
                'sourceable_name' => 'Addetto cross-docking',
                'relevance' => RiskSourceLink::RELEVANCE_PRIMARY,
            ],
            [
                'risk_name' => 'Circolazione mezzi e interferenze',
                'sourceable_type' => EquipmentType::class,
                'sourceable_name' => 'Carrello elevatore',
                'relevance' => RiskSourceLink::RELEVANCE_PRIMARY,
            ],
            [
                'risk_name' => 'Interferenze operative baie cross-docking',
                'sourceable_type' => WorkplaceType::class,
                'sourceable_name' => 'Baia di carico',
                'relevance' => RiskSourceLink::RELEVANCE_PRIMARY,
            ],
            [
                'risk_name' => 'Uso prolungato di videoterminale',
                'sourceable_type' => WorkplaceType::class,
                'sourceable_name' => 'Ufficio operativo',
                'relevance' => RiskSourceLink::RELEVANCE_SECONDARY,
            ],
        ];

        foreach ($riskLinks as $linkData) {
            $risk = RiskCatalogItem::query()
                ->where('name', $linkData['risk_name'])
                ->where(fn ($query) => $query->where('source', RiskCatalogItem::SOURCE_CORE)->orWhere('tenant_id', $tenant->id))
                ->firstOrFail();

            $sourceable = $linkData['sourceable_type']::query()
                ->where('name', $linkData['sourceable_name'])
                ->where(fn ($query) => $query->where('source', 'core')->orWhere('tenant_id', $tenant->id))
                ->firstOrFail();

            RiskSourceLink::query()->updateOrCreate(
                [
                    'risk_catalog_item_id' => $risk->id,
                    'sourceable_type' => $linkData['sourceable_type'],
                    'sourceable_id' => $sourceable->id,
                ],
                [
                    'relevance' => $linkData['relevance'],
                ],
            );
        }

        $riskProfileBuilder = app(RiskProfileBuilder::class);

        $tenant->companies()->get()->each(fn (Company $company) => $riskProfileBuilder->rebuildCompany($company));
        $tenant->workers()->get()->each(fn (Worker $worker) => $riskProfileBuilder->rebuildWorker($worker));

        $metalnova = Company::query()->where('tenant_id', $tenant->id)->where('name', 'Metalnova S.r.l.')->firstOrFail();
        $logiport = Company::query()->where('tenant_id', $tenant->id)->where('name', 'Logiport Services S.r.l.')->firstOrFail();
        $mario = Worker::query()->where('tax_code', 'RSSMRC82B10F257K')->firstOrFail();
        $giulia = Worker::query()->where('tax_code', 'FRRLNE90C55A944W')->firstOrFail();

        $mechanicalRisk = RiskCatalogItem::query()->where('name', 'Schiacciamento e cesoiamento')->firstOrFail();
        $trafficRisk = RiskCatalogItem::query()->where('name', 'Circolazione mezzi e interferenze')->firstOrFail();
        $crossDockRisk = RiskCatalogItem::query()->where('name', 'Interferenze operative baie cross-docking')->firstOrFail();

        RiskMeasure::query()->updateOrCreate(
            [
                'profileable_type' => Company::class,
                'profileable_id' => $metalnova->id,
                'risk_catalog_item_id' => $mechanicalRisk->id,
                'title' => 'Verifica schermature e protezioni linea di taglio',
            ],
            [
                'family' => RiskMeasure::FAMILY_TECHNICAL,
                'expected_measure_code' => 'protections',
                'description' => 'Presidio tecnico principale sulle zone pericolose di linea.',
                'status' => RiskMeasure::STATUS_TO_VERIFY,
                'details' => [
                    'owner' => 'Responsabile manutenzione stabilimento',
                    'verification_method' => 'Sopralluogo con checklist protezioni',
                ],
                'due_date' => now()->subDays(3)->toDateString(),
                'notes' => 'Showcase: misura tecnica ancora da verificare.',
            ],
        );

        RiskMeasure::query()->updateOrCreate(
            [
                'profileable_type' => Company::class,
                'profileable_id' => $logiport->id,
                'risk_catalog_item_id' => $crossDockRisk->id,
                'title' => 'Segnaletica e percorsi separati area cross-docking',
            ],
            [
                'family' => RiskMeasure::FAMILY_ORGANIZATIONAL,
                'description' => 'Presidio di regolazione flussi tra mezzi, colli e operatori.',
                'status' => RiskMeasure::STATUS_IMPLEMENTED,
                'details' => [
                    'owner' => 'Coordinatore hub logistico',
                    'verification_method' => 'Verifica viabilita\' mensile',
                ],
                'completed_at' => now(),
                'due_date' => now()->addDays(20)->toDateString(),
                'notes' => 'Showcase: misura gia\' attuata ma da mantenere presidiata.',
            ],
        );

        RiskMeasure::query()->updateOrCreate(
            [
                'profileable_type' => Worker::class,
                'profileable_id' => $mario->id,
                'risk_catalog_item_id' => $mechanicalRisk->id,
                'title' => 'Addestramento operativo su aree di schiacciamento',
            ],
            [
                'family' => RiskMeasure::FAMILY_TRAINING,
                'description' => 'Misura formativa individuale collegata alla mansione prevalente di produzione.',
                'status' => RiskMeasure::STATUS_NOT_IMPLEMENTED,
                'details' => [
                    'provider' => 'Studio Formazione Sicura',
                    'delivery_mode' => 'Aula con prova pratica',
                    'valid_until' => now()->addYear()->toDateString(),
                ],
                'due_date' => now()->addDays(7)->toDateString(),
                'notes' => 'Showcase: da completare nel prossimo presidio operativo.',
            ],
        );

        RiskMeasure::query()->updateOrCreate(
            [
                'profileable_type' => Worker::class,
                'profileable_id' => $giulia->id,
                'risk_catalog_item_id' => $trafficRisk->id,
                'title' => 'Consegna DPI alta visibilita\' e briefing percorsi mezzi',
            ],
            [
                'family' => RiskMeasure::FAMILY_DPI,
                'description' => 'Presidio misto per esposizione in aree con circolazione mezzi.',
                'status' => RiskMeasure::STATUS_TO_VERIFY,
                'details' => [
                    'item_name' => 'Gilet alta visibilita\'',
                    'category' => 'Indumenti ad alta visibilita\'',
                    'valid_until' => now()->addMonths(6)->toDateString(),
                ],
                'due_date' => now()->addDays(5)->toDateString(),
                'notes' => 'Showcase: misura in presidio attivo ma non ancora chiusa.',
            ],
        );

        RiskMeasure::query()->updateOrCreate(
            [
                'profileable_type' => Worker::class,
                'profileable_id' => $mario->id,
                'risk_catalog_item_id' => $mechanicalRisk->id,
                'title' => 'Sorveglianza sanitaria mansione di produzione',
            ],
            [
                'family' => RiskMeasure::FAMILY_MEDICAL,
                'description' => 'Visita periodica collegata all\'esposizione della mansione di produzione.',
                'status' => RiskMeasure::STATUS_TO_VERIFY,
                'details' => [
                    'physician' => 'Dott.ssa Elisa Conti',
                    'protocol' => 'Protocollo produzione meccanica livello 1',
                    'valid_until' => now()->addMonths(12)->toDateString(),
                ],
                'due_date' => now()->addDays(45)->toDateString(),
                'notes' => 'Showcase: presidio medico programmato.',
            ],
        );

        $tenant->companies()->get()->each(fn (Company $company) => $riskProfileBuilder->rebuildCompany($company));
        $tenant->workers()->get()->each(fn (Worker $worker) => $riskProfileBuilder->rebuildWorker($worker));

        $overrideManager = app(RiskProfileOverrideManager::class);
        $aurora = Company::query()->where('tenant_id', $tenant->id)->where('name', 'Studio Aurora S.a.s.')->firstOrFail();
        $vdtRisk = RiskCatalogItem::query()->where('name', 'Uso prolungato di videoterminale')->firstOrFail();
        $overrideManager->upsertManualRisk(
            $aurora,
            $vdtRisk,
            RiskCatalogItem::PRIORITY_MEDIUM,
            'Rischio mantenuto manualmente per presidio consulenziale su postazioni amministrative.',
            now()->addDays(14)->toDateString(),
            $owner,
        );

        $crossDockWorker = Worker::query()->where('tax_code', 'FRRLNE90C55A944W')->firstOrFail();
        $crossDockRiskItem = $crossDockWorker->riskProfileItems()
            ->whereHas('riskCatalogItem', fn ($query) => $query->where('name', 'Interferenze operative baie cross-docking'))
            ->first();

        if ($crossDockRiskItem !== null) {
            $overrideManager->review($crossDockRiskItem, [
                'operational_status' => RiskProfileItem::OPERATIONAL_STATUS_EXCLUDED,
                'consultant_decision' => RiskProfileItem::DECISION_EXCLUDED,
                'final_priority' => RiskCatalogItem::PRIORITY_LOW,
                'consultant_notes' => 'Escluso per presidio locale gia\' consolidato e segregazione flussi confermata in sopralluogo.',
                'review_due_at' => now()->subDays(2)->toDateString(),
            ], $collaborator);
        }

        $metalnovaCompanyRiskItem = $metalnova->riskProfileItems()
            ->whereHas('riskCatalogItem', fn ($query) => $query->where('name', 'Schiacciamento e cesoiamento'))
            ->first();

        if ($metalnovaCompanyRiskItem !== null) {
            $overrideManager->review($metalnovaCompanyRiskItem, [
                'operational_status' => RiskProfileItem::OPERATIONAL_STATUS_ACTIVE,
                'consultant_decision' => RiskProfileItem::DECISION_CUSTOMIZED,
                'final_priority' => RiskCatalogItem::PRIORITY_HIGH,
                'consultant_notes' => 'Rischio mantenuto alto fino a verifica completa delle protezioni e chiusura addestramento.',
                'review_due_at' => now()->addDays(9)->toDateString(),
                'operational_owner_user_id' => $collaborator->id,
                'follow_up_status' => RiskProfileItem::FOLLOW_UP_STATUS_IN_PROGRESS,
                'follow_up_notes' => 'Consolidare esito sopralluogo linea e chiudere verifica schermature entro la prossima review.',
                'follow_up_due_at' => now()->addDays(4)->toDateString(),
            ], $owner);
        }

        $auroraManualRiskItem = $aurora->riskProfileItems()
            ->whereHas('riskCatalogItem', fn ($query) => $query->where('name', 'Uso prolungato di videoterminale'))
            ->first();

        if ($auroraManualRiskItem !== null) {
            $overrideManager->review($auroraManualRiskItem, [
                'operational_status' => RiskProfileItem::OPERATIONAL_STATUS_ACTIVE,
                'consultant_decision' => RiskProfileItem::DECISION_MANUAL_ADDITION,
                'final_priority' => RiskCatalogItem::PRIORITY_MEDIUM,
                'consultant_notes' => 'Rischio manuale confermato e ricondotto a presidio ergonomico gia\' verificato.',
                'review_due_at' => now()->addDays(14)->toDateString(),
                'operational_owner_user_id' => $owner->id,
                'follow_up_status' => RiskProfileItem::FOLLOW_UP_STATUS_CLOSED,
                'follow_up_notes' => 'Presidio ergonomico verificato e chiuso nel sopralluogo di riallineamento.',
                'follow_up_outcome_status' => RiskProfileItem::FOLLOW_UP_OUTCOME_MONITORED,
                'follow_up_outcome_notes' => 'Mantenere solo monitoraggio periodico su micro-pause e assetto postazioni.',
            ], $collaborator);
        }
    }

    private function upsertUser(string $email, string $name): User
    {
        $password = env('SC_SHOWCASE_USER_PASSWORD') ?: Str::password(20);

        $user = User::query()->updateOrCreate(
            [
                'email' => $email,
            ],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'is_system_admin' => false,
            ],
        );

        $user->forceFill([
            'name' => $name,
            'email_verified_at' => now(),
            'is_system_admin' => false,
        ])->save();

        return $user;
    }
}
