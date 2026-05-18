<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'company_site_id',
        'equipment_type_id',
        'asset_code',
        'name',
        'manufacturer',
        'model',
        'status',
        'notes',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'company_site_id');
    }

    public function equipmentType(): BelongsTo
    {
        return $this->belongsTo(EquipmentType::class);
    }

    public function workerExposures(): HasMany
    {
        return $this->hasMany(WorkerEquipmentExposure::class);
    }
}
