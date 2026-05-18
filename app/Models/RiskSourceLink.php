<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RiskSourceLink extends Model
{
    use HasFactory;

    public const RELEVANCE_PRIMARY = 'primary';

    public const RELEVANCE_SECONDARY = 'secondary';

    protected $fillable = [
        'risk_catalog_item_id',
        'sourceable_type',
        'sourceable_id',
        'relevance',
        'notes',
    ];

    public function riskCatalogItem(): BelongsTo
    {
        return $this->belongsTo(RiskCatalogItem::class);
    }

    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }
}
