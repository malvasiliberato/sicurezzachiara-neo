<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workplace extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_site_id',
        'workplace_type_id',
        'code',
        'name',
        'description',
        'status',
        'notes',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function workplaceType(): BelongsTo
    {
        return $this->belongsTo(WorkplaceType::class);
    }

    public function workerExposures(): HasMany
    {
        return $this->hasMany(WorkerWorkplaceExposure::class);
    }
}
