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

class SicurezzaChiaraBaselineSeeder extends Seeder
{
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
            'owner.baseline@sicurezzachiara.test',
            'Studio Sicurezza Chiara',
        );

        $tenant = Tenant::query()->updateOrCreate(
            ['slug' => 'studio-sicurezza-chiara-baseline'],
            [
                'name' => 'Studio Sicurezza Chiara Baseline',
                'owner_user_id' => $owner->id,
            ],
        );

        $owner->forceFill([
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

        $companies = [
            [
                'name' => 'Metalnova S.r.l.',
                'legal_name' => 'Metalnova S.r.l.',
                'industry' => 'Metalmeccanica',
                'city' => 'Modena',
                'province' => 'MO',
                'contact_email' => 'sicurezza@metalnova.test',
                'sites' => [
                    [
                        'name' => 'Stabilimento principale',
                        'site_code' => 'HQ',
                        'is_headquarters' => true,
                        'city' => 'Modena',
                        'province' => 'MO',
                    ],
                    [
                        'name' => 'Magazzino ricambi',
                        'site_code' => 'MAG',
                        'is_headquarters' => false,
                        'city' => 'Carpi',
                        'province' => 'MO',
                    ],
                ],
            ],
            [
                'name' => 'Logiport Services S.r.l.',
                'legal_name' => 'Logiport Services S.r.l.',
                'industry' => 'Logistica',
                'city' => 'Bologna',
                'province' => 'BO',
                'contact_email' => 'operations@logiport.test',
                'sites' => [
                    [
                        'name' => 'Hub logistico',
                        'site_code' => 'HUB',
                        'is_headquarters' => true,
                        'city' => 'Bologna',
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
                'sites' => [
                    [
                        'name' => 'Sede direzionale',
                        'site_code' => 'DIR',
                        'is_headquarters' => true,
                        'city' => 'Reggio Emilia',
                        'province' => 'RE',
                    ],
                ],
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

        $metalnova = Company::query()->where('tenant_id', $tenant->id)->where('name', 'Metalnova S.r.l.')->firstOrFail();
        $aurora = Company::query()->where('tenant_id', $tenant->id)->where('name', 'Studio Aurora S.a.s.')->firstOrFail();

        $tenantJobRoles = [
            [
                'code' => 'TEN-ASSY',
                'name' => 'Addetto assemblaggio',
                'description' => 'Profilo tenant-level per attivita\' di montaggio e assemblaggio interno.',
            ],
            [
                'code' => 'TEN-UFF',
                'name' => 'Referente amministrativo',
                'description' => 'Profilo tenant-level per ruoli di coordinamento ufficio e amministrazione.',
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
                'code' => 'TEN-BANCO',
                'name' => 'Banco assemblaggio attrezzato',
                'description' => 'Postazione attrezzata tenant-level per micro-lavorazioni e assemblaggio.',
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
                'code' => 'TEN-ACC',
                'name' => 'Area accettazione merci',
                'description' => 'Tipologia tenant-level per aree di ricezione e controllo ingresso.',
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

        $tenant->riskCatalogItems()->updateOrCreate(
            ['name' => 'Interferenze interne area ricevimento'],
            [
                'risk_category_id' => RiskCategory::query()->where('name', 'Rischi organizzativi')->firstOrFail()->id,
                'source' => RiskCatalogItem::SOURCE_TENANT,
                'code' => 'TEN-INT-RCV',
                'description' => 'Rischio tenant-level per fasi di ricevimento materiali e compresenza operatori/mezzi.',
                'default_priority' => RiskCatalogItem::PRIORITY_MEDIUM,
                'is_active' => true,
            ],
        );

        $workers = [
            [
                'company_id' => $metalnova->id,
                'primary_site_name' => 'Stabilimento principale',
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'tax_code' => 'RSSMRA80A01F257X',
                'email' => 'mario.rossi@metalnova.test',
                'status' => 'active',
                'job_roles' => [
                    ['name' => 'Addetto assemblaggio', 'is_primary' => true],
                    ['name' => 'Operatore di produzione', 'is_primary' => false],
                ],
            ],
            [
                'company_id' => $aurora->id,
                'primary_site_name' => 'Sede direzionale',
                'first_name' => 'Laura',
                'last_name' => 'Bianchi',
                'tax_code' => 'BNCLRA88A41H223P',
                'email' => 'laura.bianchi@aurora.test',
                'status' => 'active',
                'job_roles' => [
                    ['name' => 'Referente amministrativo', 'is_primary' => true],
                    ['name' => 'Impiegato amministrativo', 'is_primary' => false],
                ],
            ],
        ];

        foreach ($workers as $workerData) {
            $jobRoles = $workerData['job_roles'];
            $primarySiteName = $workerData['primary_site_name'];
            unset($workerData['job_roles'], $workerData['primary_site_name']);

            $company = Company::query()->findOrFail($workerData['company_id']);
            $primarySite = CompanySite::query()
                ->where('company_id', $company->id)
                ->where('name', $primarySiteName)
                ->first();

            $worker = Worker::query()->updateOrCreate(
                ['tax_code' => $workerData['tax_code']],
                [
                    ...$workerData,
                    'primary_site_id' => $primarySite?->id,
                ],
            );

            foreach ($jobRoles as $jobRoleAssignmentData) {
                $jobRole = JobRole::query()
                    ->where(function ($query) use ($tenant, $jobRoleAssignmentData) {
                        $query->where('name', $jobRoleAssignmentData['name'])
                            ->where(function ($nested) use ($tenant) {
                                $nested->where('source', JobRole::SOURCE_CORE)
                                    ->orWhere('tenant_id', $tenant->id);
                            });
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
                    ],
                );
            }
        }

        $equipmentAssets = [
            [
                'company_name' => 'Metalnova S.r.l.',
                'site_name' => 'Stabilimento principale',
                'equipment_type_name' => 'Banco assemblaggio attrezzato',
                'asset_code' => 'BNK-01',
                'name' => 'Banco assemblaggio linea A',
                'manufacturer' => 'TecnoBench',
                'model' => 'TB-400',
                'status' => 'active',
            ],
            [
                'company_name' => 'Logiport Services S.r.l.',
                'site_name' => 'Hub logistico',
                'equipment_type_name' => 'Carrello elevatore',
                'asset_code' => 'MUL-01',
                'name' => 'Muletto frontale 25q',
                'manufacturer' => 'LiftPro',
                'model' => 'LP-25',
                'status' => 'active',
            ],
        ];

        foreach ($equipmentAssets as $assetData) {
            $company = Company::query()->where('tenant_id', $tenant->id)->where('name', $assetData['company_name'])->firstOrFail();
            $site = CompanySite::query()->where('company_id', $company->id)->where('name', $assetData['site_name'])->firstOrFail();
            $equipmentType = EquipmentType::query()
                ->where('name', $assetData['equipment_type_name'])
                ->where(fn ($query) => $query->where('source', EquipmentType::SOURCE_CORE)->orWhere('tenant_id', $tenant->id))
                ->firstOrFail();

            unset($assetData['company_name'], $assetData['site_name'], $assetData['equipment_type_name']);

            EquipmentAsset::query()->updateOrCreate(
                ['company_id' => $company->id, 'name' => $assetData['name']],
                [
                    ...$assetData,
                    'company_site_id' => $site->id,
                    'equipment_type_id' => $equipmentType->id,
                ],
            );
        }

        $workplaces = [
            [
                'company_name' => 'Metalnova S.r.l.',
                'site_name' => 'Stabilimento principale',
                'workplace_type_name' => 'Linea produttiva',
                'code' => 'LP-A',
                'name' => 'Linea assemblaggio A',
                'status' => 'active',
            ],
            [
                'company_name' => 'Logiport Services S.r.l.',
                'site_name' => 'Hub logistico',
                'workplace_type_name' => 'Area accettazione merci',
                'code' => 'ACC-01',
                'name' => 'Baia ricevimento centrale',
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
                'worker_tax_code' => 'RSSMRA80A01F257X',
                'equipment_asset_name' => 'Banco assemblaggio linea A',
                'workplace_name' => 'Linea assemblaggio A',
            ],
            [
                'worker_tax_code' => 'BNCLRA88A41H223P',
                'equipment_asset_name' => null,
                'workplace_name' => null,
            ],
        ];

        foreach ($workerExposures as $exposureData) {
            $worker = Worker::query()->where('tax_code', $exposureData['worker_tax_code'])->firstOrFail();

            if ($exposureData['equipment_asset_name']) {
                $asset = EquipmentAsset::query()->where('company_id', $worker->company_id)->where('name', $exposureData['equipment_asset_name'])->firstOrFail();

                WorkerEquipmentExposure::query()->updateOrCreate(
                    [
                        'worker_id' => $worker->id,
                        'equipment_asset_id' => $asset->id,
                    ],
                    [
                        'is_primary' => true,
                        'notes' => 'Baseline locale: esposizione principale collegata al contesto operativo.',
                    ],
                );
            }

            if ($exposureData['workplace_name']) {
                $workplace = Workplace::query()
                    ->whereHas('site', fn ($query) => $query->where('company_id', $worker->company_id))
                    ->where('name', $exposureData['workplace_name'])
                    ->firstOrFail();

                WorkerWorkplaceExposure::query()->updateOrCreate(
                    [
                        'worker_id' => $worker->id,
                        'workplace_id' => $workplace->id,
                    ],
                    [
                        'is_primary' => true,
                        'notes' => 'Baseline locale: luogo operativo principale.',
                    ],
                );
            }
        }

        $riskLinks = [
            [
                'risk_name' => 'Movimentazione manuale dei carichi',
                'sourceable_type' => JobRole::class,
                'sourceable_name' => 'Addetto assemblaggio',
                'relevance' => RiskSourceLink::RELEVANCE_PRIMARY,
            ],
            [
                'risk_name' => 'Schiacciamento e cesoiamento',
                'sourceable_type' => EquipmentType::class,
                'sourceable_name' => 'Banco assemblaggio attrezzato',
                'relevance' => RiskSourceLink::RELEVANCE_SECONDARY,
            ],
            [
                'risk_name' => 'Interferenze interne area ricevimento',
                'sourceable_type' => WorkplaceType::class,
                'sourceable_name' => 'Area accettazione merci',
                'relevance' => RiskSourceLink::RELEVANCE_PRIMARY,
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
        $mario = Worker::query()->where('tax_code', 'RSSMRA80A01F257X')->firstOrFail();

        $metalnovaRisk = RiskCatalogItem::query()->where('name', 'Movimentazione manuale dei carichi')->firstOrFail();
        $marioRisk = RiskCatalogItem::query()->where('name', 'Schiacciamento e cesoiamento')->firstOrFail();

        RiskMeasure::query()->updateOrCreate(
            [
                'profileable_type' => Company::class,
                'profileable_id' => $metalnova->id,
                'risk_catalog_item_id' => $metalnovaRisk->id,
                'title' => 'Procedura di movimentazione materiali area assemblaggio',
            ],
            [
                'family' => RiskMeasure::FAMILY_ORGANIZATIONAL,
                'description' => 'Presidio operativo minimo sulla movimentazione interna dei componenti.',
                'status' => RiskMeasure::STATUS_TO_VERIFY,
                'details' => [
                    'owner' => 'Caporeparto assemblaggio',
                    'verification_method' => 'Checklist settimanale di reparto',
                ],
                'due_date' => now()->addDays(15)->toDateString(),
                'notes' => 'Baseline locale: misura introdotta per rendere leggibile il collegamento rischio -> presidio.',
            ],
        );

        RiskMeasure::query()->updateOrCreate(
            [
                'profileable_type' => Worker::class,
                'profileable_id' => $mario->id,
                'risk_catalog_item_id' => $marioRisk->id,
                'title' => 'Controllo protezioni banco assemblaggio',
            ],
            [
                'family' => RiskMeasure::FAMILY_TECHNICAL,
                'description' => 'Verifica e mantenimento delle protezioni presenti sulla postazione attrezzata.',
                'status' => RiskMeasure::STATUS_IMPLEMENTED,
                'details' => [
                    'owner' => 'Responsabile manutenzione interna',
                    'verification_method' => 'Controllo tecnico mensile',
                ],
                'completed_at' => now(),
                'due_date' => now()->addDays(30)->toDateString(),
                'notes' => 'Baseline locale: presidio tecnico gia\' attivo.',
            ],
        );

        $tenant->companies()->get()->each(fn (Company $company) => $riskProfileBuilder->rebuildCompany($company));
        $tenant->workers()->get()->each(fn (Worker $worker) => $riskProfileBuilder->rebuildWorker($worker));

        app(RiskProfileOverrideManager::class)->upsertManualRisk(
            $aurora,
            RiskCatalogItem::query()->where('name', 'Uso prolungato di videoterminale')->firstOrFail(),
            RiskCatalogItem::PRIORITY_MEDIUM,
            'Rischio mantenuto manualmente nel baseline per verificare il layer consulenziale.',
            now()->addDays(20)->toDateString(),
            $owner,
        );

        $baselineProfileItem = $metalnova->riskProfileItems()
            ->whereHas('riskCatalogItem', fn ($query) => $query->where('name', 'Movimentazione manuale dei carichi'))
            ->first();

        if ($baselineProfileItem !== null) {
            app(RiskProfileOverrideManager::class)->review($baselineProfileItem, [
                'operational_status' => RiskProfileItem::OPERATIONAL_STATUS_ACTIVE,
                'consultant_decision' => RiskProfileItem::DECISION_CONFIRMED,
                'final_priority' => RiskCatalogItem::PRIORITY_MEDIUM,
                'consultant_notes' => 'Baseline locale: rischio confermato e preso in carico con follow-up operativo leggero.',
                'review_due_at' => now()->addDays(12)->toDateString(),
                'operational_owner_user_id' => $owner->id,
                'follow_up_status' => RiskProfileItem::FOLLOW_UP_STATUS_IN_PROGRESS,
                'follow_up_notes' => 'Verificare applicazione procedura movimentazione e feedback del caporeparto.',
                'follow_up_due_at' => now()->addDays(7)->toDateString(),
            ], $owner);
        }
    }

    private function upsertUser(string $email, string $name): User
    {
        $password = env('SC_BASELINE_USER_PASSWORD') ?: Str::password(20);

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
