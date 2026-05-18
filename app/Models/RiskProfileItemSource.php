<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RiskProfileItemSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'risk_profile_item_id',
        'sourceable_type',
        'sourceable_id',
        'source_family',
        'source_label',
        'relevance',
    ];

    public function riskProfileItem(): BelongsTo
    {
        return $this->belongsTo(RiskProfileItem::class);
    }

    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }
}
