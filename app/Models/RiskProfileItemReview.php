<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskProfileItemReview extends Model
{
    use HasFactory;

    public const EVENT_REVIEW_UPDATED = 'review_updated';

    public const EVENT_MANUAL_ADDED = 'manual_added';

    protected $fillable = [
        'risk_profile_item_id',
        'actor_user_id',
        'event_type',
        'operational_status',
        'consultant_decision',
        'final_priority',
        'consultant_notes',
        'review_due_at',
        'operational_owner_user_id',
        'follow_up_status',
        'follow_up_notes',
        'follow_up_due_at',
        'follow_up_outcome_status',
        'follow_up_outcome_notes',
        'follow_up_outcome_recorded_at',
        'reviewed_at',
    ];

    protected $casts = [
        'review_due_at' => 'date',
        'follow_up_due_at' => 'date',
        'follow_up_outcome_recorded_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function riskProfileItem(): BelongsTo
    {
        return $this->belongsTo(RiskProfileItem::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function operationalOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operational_owner_user_id');
    }
}
