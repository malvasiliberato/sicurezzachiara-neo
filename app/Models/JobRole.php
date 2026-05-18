<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class JobRole extends Model
{
    use HasFactory;

    public const SOURCE_CORE = 'core';

    public const SOURCE_TENANT = 'tenant';

    protected $fillable = [
        'tenant_id',
        'source',
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function workerAssignments(): HasMany
    {
        return $this->hasMany(WorkerJobRoleAssignment::class);
    }

    public function riskSourceLinks(): MorphMany
    {
        return $this->morphMany(RiskSourceLink::class, 'sourceable');
    }
}
