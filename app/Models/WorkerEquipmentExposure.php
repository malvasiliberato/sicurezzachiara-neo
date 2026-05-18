<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerEquipmentExposure extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_id',
        'equipment_asset_id',
        'is_primary',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function equipmentAsset(): BelongsTo
    {
        return $this->belongsTo(EquipmentAsset::class);
    }
}
