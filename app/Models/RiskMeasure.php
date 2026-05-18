<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RiskMeasure extends Model
{
    use HasFactory;

    public const FAMILY_ORGANIZATIONAL = 'organizational';

    public const FAMILY_TECHNICAL = 'technical';

    public const FAMILY_DPI = 'dpi';

    public const FAMILY_TRAINING = 'training';

    public const FAMILY_MEDICAL = 'medical';

    public const STATUS_IMPLEMENTED = 'implemented';

    public const STATUS_NOT_IMPLEMENTED = 'not_implemented';

    public const STATUS_TO_VERIFY = 'to_verify';

    protected $fillable = [
        'profileable_type',
        'profileable_id',
        'risk_catalog_item_id',
        'expected_measure_code',
        'family',
        'title',
        'description',
        'status',
        'details',
        'completed_at',
        'due_date',
        'notes',
    ];

    protected $casts = [
        'details' => 'array',
        'completed_at' => 'datetime',
        'due_date' => 'date',
    ];

    public function profileable(): MorphTo
    {
        return $this->morphTo();
    }

    public function riskCatalogItem(): BelongsTo
    {
        return $this->belongsTo(RiskCatalogItem::class);
    }
}
