<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiskCatalogItem extends Model
{
    use HasFactory;

    public const SOURCE_CORE = 'core';

    public const SOURCE_TENANT = 'tenant';

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    protected $fillable = [
        'tenant_id',
        'risk_category_id',
        'source',
        'code',
        'name',
        'description',
        'expected_measures',
        'default_priority',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expected_measures' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RiskCategory::class, 'risk_category_id');
    }

    public function sourceLinks(): HasMany
    {
        return $this->hasMany(RiskSourceLink::class);
    }

    public function profileItems(): HasMany
    {
        return $this->hasMany(RiskProfileItem::class);
    }
}
