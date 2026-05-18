<?php

namespace App\Support;

use App\Models\Company;
use App\Models\RiskCatalogItem;
use App\Models\RiskProfileItem;
use App\Models\RiskProfileItemReview;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Support\Carbon;

class RiskProfileOverrideManager
{
    public function __construct(
        private readonly RiskCoverageResolver $riskCoverageResolver,
    ) {}

    public function upsertManualRisk(
        Company|Worker $profileable,
        RiskCatalogItem $riskCatalogItem,
        ?string $finalPriority,
        ?string $consultantNotes,
        ?string $reviewDueAt = null,
        ?User $actor = null,
    ): RiskProfileItem {
        $profileItem = $profileable->riskProfileItems()->firstOrNew([
            'risk_catalog_item_id' => $riskCatalogItem->id,
        ]);

        $profileItem->fill([
            'status' => $this->riskCoverageResolver->resolveForProfileable($profileable, $riskCatalogItem->id),
            'priority' => $profileItem->exists ? $profileItem->priority : $riskCatalogItem->default_priority,
            'final_priority' => $finalPriority ?: null,
            'source_count' => $profileItem->exists ? $profileItem->source_count : 0,
            'is_manual' => true,
            'is_currently_derived' => $profileItem->exists ? $profileItem->is_currently_derived : false,
            'operational_status' => RiskProfileItem::OPERATIONAL_STATUS_ACTIVE,
            'consultant_decision' => RiskProfileItem::DECISION_MANUAL_ADDITION,
            'consultant_notes' => $consultantNotes,
            'reviewed_at' => Carbon::now(),
            'review_due_at' => $reviewDueAt ?: null,
            'follow_up_status' => RiskProfileItem::FOLLOW_UP_STATUS_OPEN,
            'follow_up_outcome_status' => null,
            'follow_up_outcome_notes' => null,
            'follow_up_outcome_recorded_at' => null,
            'last_calculated_at' => Carbon::now(),
        ]);

        $profileable->riskProfileItems()->save($profileItem);
        $this->recordReview($profileItem, RiskProfileItemReview::EVENT_MANUAL_ADDED, $actor);

        return $profileItem->fresh(['riskCatalogItem.category', 'sources']);
    }

    public function review(
        RiskProfileItem $profileItem,
        array $payload,
        ?User $actor = null,
    ): RiskProfileItem {
        $profileItem->fill([
            'operational_status' => $payload['operational_status'],
            'consultant_decision' => $payload['consultant_decision'],
            'consultant_notes' => $payload['consultant_notes'] ?? null,
            'final_priority' => $payload['final_priority'] ?? null,
            'reviewed_at' => Carbon::now(),
            'review_due_at' => $payload['review_due_at'] ?? null,
            'operational_owner_user_id' => $payload['operational_owner_user_id'] ?? null,
            'follow_up_status' => $payload['follow_up_status'] ?? null,
            'follow_up_notes' => $payload['follow_up_notes'] ?? null,
            'follow_up_due_at' => $payload['follow_up_due_at'] ?? null,
            'follow_up_outcome_status' => $payload['follow_up_outcome_status'] ?? null,
            'follow_up_outcome_notes' => $payload['follow_up_outcome_notes'] ?? null,
        ]);

        if ($profileItem->follow_up_status !== null && $profileItem->taken_in_charge_at === null) {
            $profileItem->taken_in_charge_at = Carbon::now();
        }

        if ($profileItem->follow_up_status === RiskProfileItem::FOLLOW_UP_STATUS_CLOSED) {
            $profileItem->follow_up_due_at = null;
            $profileItem->follow_up_outcome_recorded_at = Carbon::now();
        } else {
            $profileItem->follow_up_outcome_status = null;
            $profileItem->follow_up_outcome_notes = null;
            $profileItem->follow_up_outcome_recorded_at = null;
        }

        $profileItem->save();
        $this->recordReview($profileItem, RiskProfileItemReview::EVENT_REVIEW_UPDATED, $actor);

        return $profileItem->fresh(['riskCatalogItem.category', 'sources']);
    }

    public function availableManualRiskOptions(Tenant $tenant, Company|Worker $profileable): array
    {
        $existingRiskIds = $profileable->riskProfileItems()->pluck('risk_catalog_item_id');

        return RiskCatalogItem::query()
            ->where(function ($query) use ($tenant) {
                $query->where('source', RiskCatalogItem::SOURCE_CORE)
                    ->orWhere('tenant_id', $tenant->id);
            })
            ->where('is_active', true)
            ->with('category:id,name')
            ->orderByRaw("case when source = 'tenant' then 0 else 1 end")
            ->orderBy('name')
            ->get(['id', 'risk_category_id', 'tenant_id', 'source', 'name', 'default_priority'])
            ->map(fn (RiskCatalogItem $risk) => [
                'id' => $risk->id,
                'name' => $risk->name,
                'source' => $risk->source,
                'category_name' => $risk->category?->name,
                'default_priority' => $risk->default_priority,
                'already_present' => $existingRiskIds->contains($risk->id),
            ])
            ->values()
            ->all();
    }

    public function riskBelongsToTenant(Tenant $tenant, RiskCatalogItem $riskCatalogItem): bool
    {
        return $riskCatalogItem->source === RiskCatalogItem::SOURCE_CORE
            || (int) $riskCatalogItem->tenant_id === (int) $tenant->id;
    }

    public function profileItemBelongsToProfileable(Company|Worker $profileable, RiskProfileItem $profileItem): bool
    {
        return $profileItem->profileable_type === $profileable::class
            && (int) $profileItem->profileable_id === (int) $profileable->getKey();
    }

    public function userCanOwnFollowUp(Tenant $tenant, ?int $userId): bool
    {
        if ($userId === null) {
            return true;
        }

        return TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $userId)
            ->exists();
    }

    private function recordReview(RiskProfileItem $profileItem, string $eventType, ?User $actor): void
    {
        $profileItem->reviews()->create([
            'actor_user_id' => $actor?->id,
            'event_type' => $eventType,
            'operational_status' => $profileItem->operational_status,
            'consultant_decision' => $profileItem->consultant_decision,
            'final_priority' => $profileItem->final_priority,
            'consultant_notes' => $profileItem->consultant_notes,
            'review_due_at' => $profileItem->review_due_at,
            'operational_owner_user_id' => $profileItem->operational_owner_user_id,
            'follow_up_status' => $profileItem->follow_up_status,
            'follow_up_notes' => $profileItem->follow_up_notes,
            'follow_up_due_at' => $profileItem->follow_up_due_at,
            'follow_up_outcome_status' => $profileItem->follow_up_outcome_status,
            'follow_up_outcome_notes' => $profileItem->follow_up_outcome_notes,
            'follow_up_outcome_recorded_at' => $profileItem->follow_up_outcome_recorded_at,
            'reviewed_at' => $profileItem->reviewed_at ?? Carbon::now(),
        ]);
    }
}
