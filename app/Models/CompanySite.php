<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanySite extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'site_code',
        'is_headquarters',
        'address_line',
        'street_number',
        'postal_code',
        'city',
        'province',
        'notes',
    ];

    protected $casts = [
        'is_headquarters' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function primaryWorkers(): HasMany
    {
        return $this->hasMany(Worker::class, 'primary_site_id');
    }

    public function equipmentAssets(): HasMany
    {
        return $this->hasMany(EquipmentAsset::class, 'company_site_id');
    }

    public function workplaces(): HasMany
    {
        return $this->hasMany(Workplace::class, 'company_site_id');
    }
}
