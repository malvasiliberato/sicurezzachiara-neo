<?php

use App\Actions\Tenant\CreateTenantWorkspace;
use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\CompanySite;
use App\Models\DvrDocument;
use App\Models\DvrDocumentSection;
use App\Models\RiskCatalogItem;
use App\Models\RiskCategory;
use App\Models\RiskMeasure;
use App\Models\RiskProfileItem;
use App\Models\User;
use App\Models\Worker;
use App\Support\DvrDraftService;

function createDvrDraftFixture(): array
{
    $user = User::factory()->create();
    $tenant = app(CreateTenantWorkspace::class)->handle($user, 'Studio DVR');

    $company = Company::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Metalnova S.r.l.',
        'industry' => 'Metalmeccanica',
        'notes' => 'Produzione e assemblaggio componenti metallici.',
    ]);

    $site = CompanySite::query()->create([
        'company_id' => $company->id,
        'name' => 'Stabilimento principale',
        'is_headquarters' => true,
    ]);

    Worker::query()->create([
        'company_id' => $company->id,
        'primary_site_id' => $site->id,
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'status' => 'active',
    ]);

    $category = RiskCategory::query()->create([
        'name' => 'Rischi meccanici',
        'description' => 'Categoria test',
    ]);

    $risk = RiskCatalogItem::query()->create([
        'risk_category_id' => $category->id,
        'source' => RiskCatalogItem::SOURCE_CORE,
        'code' => 'mechanical-crushing',
        'name' => 'Schiacciamento e cesoiamento',
        'default_priority' => RiskCatalogItem::PRIORITY_HIGH,
        'is_active' => true,
    ]);

    RiskProfileItem::query()->create([
        'profileable_type' => Company::class,
        'profileable_id' => $company->id,
        'risk_catalog_item_id' => $risk->id,
        'status' => RiskProfileItem::STATUS_UNCOVERED,
        'priority' => RiskCatalogItem::PRIORITY_HIGH,
        'source_count' => 1,
        'is_currently_derived' => true,
        'last_calculated_at' => now(),
    ]);

    RiskMeasure::query()->create([
        'profileable_type' => Company::class,
        'profileable_id' => $company->id,
        'risk_catalog_item_id' => $risk->id,
        'family' => RiskMeasure::FAMILY_DPI,
        'title' => 'Guanti antitaglio',
        'status' => RiskMeasure::STATUS_TO_VERIFY,
        'due_date' => now()->addMonth()->toDateString(),
    ]);

    return compact('user', 'tenant', 'company');
}

test('dvr draft service creates a minimal document draft with initial sections', function () {
    ['user' => $user, 'tenant' => $tenant, 'company' => $company] = createDvrDraftFixture();

    $document = app(DvrDraftService::class)->createOrReuseDraft($tenant, $company, $user);

    expect($document)
        ->toBeInstanceOf(DvrDocument::class)
        ->and($document->tenant_id)->toBe($tenant->id)
        ->and($document->company_id)->toBe($company->id)
        ->and($document->status)->toBe(DvrDocument::STATUS_DRAFT)
        ->and($document->version_number)->toBe(1)
        ->and($document->completeness_status)->toBe(DvrDocument::COMPLETENESS_INCOMPLETE)
        ->and($document->snapshot_payload)->toBeNull()
        ->and($document->created_by_user_id)->toBe($user->id)
        ->and($document->sections)->toHaveCount(19);

    expect($document->tenant->is($tenant))->toBeTrue()
        ->and($document->company->is($company))->toBeTrue()
        ->and($document->createdBy->is($user))->toBeTrue()
        ->and($document->updatedBy->is($user))->toBeTrue();

    $sections = $document->sections->keyBy('section_key');

    expect($sections->keys()->all())->toBe([
        'cover',
        'company_registry',
        'sites',
        'safety_roles',
        'operational_context',
        'methodology',
        'homogeneous_groups',
        'exposed_workers',
        'workplaces',
        'equipment',
        'risk_assessment',
        'prevention_protection_measures',
        'dpi',
        'training',
        'medical_surveillance',
        'improvement_program',
        'organizational_procedures',
        'validation',
        'versions',
    ]);

    expect($sections->get('cover')->status)->toBe(DvrDocumentSection::STATUS_AUTO_READY)
        ->and($sections->get('sites')->status)->toBe(DvrDocumentSection::STATUS_AUTO_READY)
        ->and($sections->get('safety_roles')->status)->toBe(DvrDocumentSection::STATUS_NEEDS_INPUT)
        ->and($sections->get('operational_context')->status)->toBe(DvrDocumentSection::STATUS_NEEDS_REVIEW)
        ->and($sections->get('methodology')->status)->toBe(DvrDocumentSection::STATUS_NEEDS_REVIEW)
        ->and($sections->get('risk_assessment')->status)->toBe(DvrDocumentSection::STATUS_AUTO_READY)
        ->and($sections->get('dpi')->status)->toBe(DvrDocumentSection::STATUS_AUTO_READY)
        ->and($sections->get('training')->status)->toBe(DvrDocumentSection::STATUS_NEEDS_INPUT)
        ->and($sections->get('validation')->source_status)->toBe(DvrDocumentSection::SOURCE_MANUAL);
});

test('dvr draft service reuses an open draft and does not duplicate sections', function () {
    ['user' => $user, 'tenant' => $tenant, 'company' => $company] = createDvrDraftFixture();

    $firstDraft = app(DvrDraftService::class)->createOrReuseDraft($tenant, $company, $user);
    $secondDraft = app(DvrDraftService::class)->createOrReuseDraft($tenant, $company, $user);

    expect($secondDraft->id)->toBe($firstDraft->id)
        ->and(DvrDocument::query()->where('company_id', $company->id)->count())->toBe(1)
        ->and(DvrDocumentSection::query()->where('dvr_document_id', $firstDraft->id)->count())->toBe(19);
});

test('dvr draft creation writes an audit event in the current tenant', function () {
    ['user' => $user, 'tenant' => $tenant, 'company' => $company] = createDvrDraftFixture();

    $document = app(DvrDraftService::class)->createOrReuseDraft($tenant, $company, $user);

    $event = AuditEvent::query()
        ->where('tenant_id', $tenant->id)
        ->where('action', 'dvr_document.created_draft')
        ->where('auditable_type', $document->getMorphClass())
        ->where('auditable_id', $document->id)
        ->firstOrFail();

    expect($event->actor_user_id)->toBe($user->id)
        ->and($event->metadata['company_id'])->toBe($company->id)
        ->and($event->metadata['document_id'])->toBe($document->id)
        ->and($event->metadata['version_number'])->toBe(1);
});

test('dvr draft service refuses companies from another tenant', function () {
    ['tenant' => $tenant] = createDvrDraftFixture();

    $otherUser = User::factory()->create();
    $otherTenant = app(CreateTenantWorkspace::class)->handle($otherUser, 'Studio Esterno');
    $otherCompany = Company::query()->create([
        'tenant_id' => $otherTenant->id,
        'name' => 'Societa esterna',
    ]);

    app(DvrDraftService::class)->createOrReuseDraft($tenant, $otherCompany);
})->throws(InvalidArgumentException::class);
