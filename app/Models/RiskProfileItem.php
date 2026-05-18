<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RiskProfileItem extends Model
{
    use HasFactory;

    public const STATUS_UNCOVERED = 'uncovered';

    public const STATUS_COVERED = 'covered';

    public const OPERATIONAL_STATUS_ACTIVE = 'active';

    public const OPERATIONAL_STATUS_EXCLUDED = 'excluded';

    public const DECISION_CONFIRMED = 'confirmed';

    public const DECISION_CUSTOMIZED = 'customized';

    public const DECISION_EXCLUDED = 'excluded';

    public const DECISION_MANUAL_ADDITION = 'manual_addition';

    public const FOLLOW_UP_STATUS_OPEN = 'open';

    public const FOLLOW_UP_STATUS_IN_PROGRESS = 'in_progress';

    public const FOLLOW_UP_STATUS_BLOCKED = 'blocked';

    public const FOLLOW_UP_STATUS_CLOSED = 'closed';

    public const FOLLOW_UP_OUTCOME_RESOLVED = 'resolved';

    public const FOLLOW_UP_OUTCOME_MONITORED = 'monitored';

    public const FOLLOW_UP_OUTCOME_DEFERRED = 'deferred';

    protected $fillable = [
        'profileable_type',
        'profileable_id',
        'risk_catalog_item_id',
        'status',
        'priority',
        'final_priority',
        'source_count',
        'is_manual',
        'is_currently_derived',
        'operational_status',
        'consultant_decision',
        'consultant_notes',
        'reviewed_at',
        'review_due_at',
        'operational_owner_user_id',
        'follow_up_status',
        'follow_up_notes',
        'follow_up_due_at',
        'follow_up_outcome_status',
        'follow_up_outcome_notes',
        'follow_up_outcome_recorded_at',
        'taken_in_charge_at',
        'last_calculated_at',
    ];

    protected $casts = [
        'is_manual' => 'boolean',
        'is_currently_derived' => 'boolean',
        'reviewed_at' => 'datetime',
        'review_due_at' => 'date',
        'follow_up_due_at' => 'date',
        'follow_up_outcome_recorded_at' => 'datetime',
        'taken_in_charge_at' => 'datetime',
        'last_calculated_at' => 'datetime',
    ];

    public function profileable(): MorphTo
    {
        return $this->morphTo();
    }

    public function riskCatalogItem(): BelongsTo
    {
        return $this->belongsTo(RiskCatalogItem::class);
    }

    public function operationalOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operational_owner_user_id');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(RiskProfileItemSource::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(RiskProfileItemReview::class)
            ->latest('reviewed_at')
            ->latest('id');
    }

    public function effectivePriority(): string
    {
        return $this->final_priority ?: $this->priority;
    }

    public function isOperationallyActive(): bool
    {
        return $this->operational_status === self::OPERATIONAL_STATUS_ACTIVE;
    }

    public function isReviewDue(?CarbonInterface $referenceDate = null): bool
    {
        if ($this->review_due_at === null) {
            return false;
        }

        $referenceDate ??= now();

        return $this->review_due_at->lte($referenceDate->toDateString());
    }

    public function hasOpenFollowUp(): bool
    {
        return in_array($this->follow_up_status, [
            self::FOLLOW_UP_STATUS_OPEN,
            self::FOLLOW_UP_STATUS_IN_PROGRESS,
            self::FOLLOW_UP_STATUS_BLOCKED,
        ], true);
    }

    public function isFollowUpDue(?CarbonInterface $referenceDate = null): bool
    {
        if (! $this->hasOpenFollowUp() || $this->follow_up_due_at === null) {
            return false;
        }

        $referenceDate ??= now();

        return $this->follow_up_due_at->lte($referenceDate->toDateString());
    }

    public function hasRecordedOutcome(): bool
    {
        return $this->follow_up_outcome_status !== null;
    }
}
