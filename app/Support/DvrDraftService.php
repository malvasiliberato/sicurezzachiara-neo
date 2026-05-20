<?php

namespace App\Support;

use App\Models\Company;
use App\Models\DvrDocument;
use App\Models\DvrDocumentSection;
use App\Models\RiskMeasure;
use App\Models\RiskProfileItem;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DvrDraftService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function createOrReuseDraft(Tenant $tenant, Company $company, ?User $actor = null): DvrDocument
    {
        if ((int) $company->tenant_id !== (int) $tenant->id) {
            throw new \InvalidArgumentException('L\'azienda non appartiene al tenant indicato.');
        }

        $existingDraft = DvrDocument::query()
            ->with('sections')
            ->where('tenant_id', $tenant->id)
            ->where('company_id', $company->id)
            ->whereIn('status', [
                DvrDocument::STATUS_DRAFT,
                DvrDocument::STATUS_IN_REVIEW,
            ])
            ->orderByDesc('id')
            ->first();

        if ($existingDraft !== null) {
            return $existingDraft;
        }

        return DB::transaction(function () use ($tenant, $company, $actor) {
            $document = DvrDocument::query()->create([
                'tenant_id' => $tenant->id,
                'company_id' => $company->id,
                'status' => DvrDocument::STATUS_DRAFT,
                'version_number' => $this->nextVersionNumber($company),
                'title' => sprintf('DVR %s - bozza', $company->name),
                'generated_from_live_at' => now(),
                'completeness_status' => DvrDocument::COMPLETENESS_INCOMPLETE,
                'snapshot_payload' => null,
                'created_by_user_id' => $actor?->id,
                'updated_by_user_id' => $actor?->id,
            ]);

            foreach ($this->initialSections($company) as $section) {
                $document->sections()->create($section);
            }

            $this->auditLogger->log(
                $tenant,
                $actor,
                'dvr_document.created_draft',
                $document,
                'Creata bozza DVR',
                [
                    'company_id' => $company->id,
                    'document_id' => $document->id,
                    'version_number' => $document->version_number,
                ],
            );

            return $document->fresh(['tenant', 'company', 'sections']);
        });
    }

    private function nextVersionNumber(Company $company): int
    {
        return ((int) DvrDocument::query()
            ->where('company_id', $company->id)
            ->withTrashed()
            ->max('version_number')) + 1;
    }

    private function initialSections(Company $company): array
    {
        $company->loadCount([
            'sites',
            'workers',
            'equipmentAssets',
        ]);
        $workerIds = $company->workers()->pluck('id');
        $riskProfileItemsCount = $this->companyAndWorkerRiskProfileItemsCount($company, $workerIds->all());
        $riskMeasuresCount = $this->companyAndWorkerRiskMeasuresCount($company, $workerIds->all());

        $definitions = [
            ['cover', 'Copertina e metadati DVR', DvrDocumentSection::MODE_AUTOMATIC, true],
            ['company_registry', 'Anagrafica azienda', DvrDocumentSection::MODE_AUTOMATIC, true],
            ['sites', 'Sedi e unita operative', DvrDocumentSection::MODE_AUTOMATIC, $company->sites_count > 0],
            ['safety_roles', 'Organigramma sicurezza', DvrDocumentSection::MODE_MANUAL, false],
            ['operational_context', 'Descrizione attivita e ciclo lavorativo', DvrDocumentSection::MODE_SEMI_MANUAL, filled($company->industry) || filled($company->notes)],
            ['methodology', 'Criteri e metodologia di valutazione', DvrDocumentSection::MODE_TEMPLATE, true],
            ['homogeneous_groups', 'Mansioni e gruppi omogenei', DvrDocumentSection::MODE_AUTOMATIC, $company->workers_count > 0],
            ['exposed_workers', 'Lavoratori esposti', DvrDocumentSection::MODE_AUTOMATIC, $company->workers_count > 0],
            ['workplaces', 'Luoghi di lavoro', DvrDocumentSection::MODE_AUTOMATIC, $company->sites()->whereHas('workplaces')->exists()],
            ['equipment', 'Macchinari e attrezzature', DvrDocumentSection::MODE_AUTOMATIC, $company->equipment_assets_count > 0],
            ['risk_assessment', 'Valutazione rischi', DvrDocumentSection::MODE_AUTOMATIC, $riskProfileItemsCount > 0],
            ['prevention_protection_measures', 'Misure di prevenzione e protezione', DvrDocumentSection::MODE_AUTOMATIC, $riskMeasuresCount > 0],
            ['dpi', 'DPI', DvrDocumentSection::MODE_AUTOMATIC, $this->hasMeasureFamily($company, $workerIds->all(), RiskMeasure::FAMILY_DPI)],
            ['training', 'Formazione', DvrDocumentSection::MODE_AUTOMATIC, $this->hasMeasureFamily($company, $workerIds->all(), RiskMeasure::FAMILY_TRAINING)],
            ['medical_surveillance', 'Sorveglianza sanitaria', DvrDocumentSection::MODE_AUTOMATIC, $this->hasMeasureFamily($company, $workerIds->all(), RiskMeasure::FAMILY_MEDICAL)],
            ['improvement_program', 'Programma di miglioramento', DvrDocumentSection::MODE_AUTOMATIC, $riskMeasuresCount > 0],
            ['organizational_procedures', 'Procedure e misure organizzative', DvrDocumentSection::MODE_SEMI_MANUAL, $this->hasMeasureFamily($company, $workerIds->all(), RiskMeasure::FAMILY_ORGANIZATIONAL)],
            ['validation', 'Firme e validazione', DvrDocumentSection::MODE_MANUAL, false],
            ['versions', 'Storico versioni', DvrDocumentSection::MODE_AUTOMATIC, true],
        ];

        return collect($definitions)
            ->map(fn (array $definition, int $index) => [
                'section_key' => $definition[0],
                'title' => $definition[1],
                'generation_mode' => $definition[2],
                'source_status' => $definition[2] === DvrDocumentSection::MODE_MANUAL
                    ? DvrDocumentSection::SOURCE_MANUAL
                    : DvrDocumentSection::SOURCE_LIVE,
                'status' => $this->initialSectionStatus($definition[2], $definition[3]),
                'sort_order' => ($index + 1) * 10,
            ])
            ->all();
    }

    private function initialSectionStatus(string $generationMode, bool $hasBaseData): string
    {
        if ($generationMode === DvrDocumentSection::MODE_AUTOMATIC) {
            return $hasBaseData
                ? DvrDocumentSection::STATUS_AUTO_READY
                : DvrDocumentSection::STATUS_NEEDS_INPUT;
        }

        if ($generationMode === DvrDocumentSection::MODE_TEMPLATE) {
            return DvrDocumentSection::STATUS_NEEDS_REVIEW;
        }

        if ($generationMode === DvrDocumentSection::MODE_SEMI_MANUAL) {
            return $hasBaseData
                ? DvrDocumentSection::STATUS_NEEDS_REVIEW
                : DvrDocumentSection::STATUS_NEEDS_INPUT;
        }

        return DvrDocumentSection::STATUS_NEEDS_INPUT;
    }

    private function companyAndWorkerRiskProfileItemsCount(Company $company, array $workerIds): int
    {
        return RiskProfileItem::query()
            ->where(function ($query) use ($company, $workerIds) {
                $query
                    ->where(function ($companyQuery) use ($company) {
                        $companyQuery->where('profileable_type', Company::class)
                            ->where('profileable_id', $company->id);
                    })
                    ->orWhere(function ($workerQuery) use ($workerIds) {
                        $workerQuery->where('profileable_type', Worker::class)
                            ->whereIn('profileable_id', $workerIds);
                    });
            })
            ->count();
    }

    private function companyAndWorkerRiskMeasuresCount(Company $company, array $workerIds): int
    {
        return $this->companyAndWorkerRiskMeasuresQuery($company, $workerIds)->count();
    }

    private function hasMeasureFamily(Company $company, array $workerIds, string $family): bool
    {
        return $this->companyAndWorkerRiskMeasuresQuery($company, $workerIds)
            ->where('family', $family)
            ->exists();
    }

    private function companyAndWorkerRiskMeasuresQuery(Company $company, array $workerIds): Builder
    {
        return RiskMeasure::query()
            ->where(function ($query) use ($company, $workerIds) {
                $query
                    ->where(function ($companyQuery) use ($company) {
                        $companyQuery->where('profileable_type', Company::class)
                            ->where('profileable_id', $company->id);
                    })
                    ->orWhere(function ($workerQuery) use ($workerIds) {
                        $workerQuery->where('profileable_type', Worker::class)
                            ->whereIn('profileable_id', $workerIds);
                    });
            });
    }
}
