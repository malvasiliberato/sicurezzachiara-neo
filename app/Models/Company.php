<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'legal_name',
        'vat_number',
        'tax_code',
        'ateco_2025_id',
        'industry',
        'contact_email',
        'contact_pec',
        'contact_phone',
        'address_line',
        'street_number',
        'city',
        'province',
        'postal_code',
        'notes',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function atecoEntry(): BelongsTo
    {
        return $this->belongsTo(Ateco2025::class, 'ateco_2025_id');
    }

    public function sites(): HasMany
    {
        return $this->hasMany(CompanySite::class);
    }

    public function workers(): HasMany
    {
        return $this->hasMany(Worker::class);
    }

    public function jobRoleAssignments(): HasManyThrough
    {
        return $this->hasManyThrough(WorkerJobRoleAssignment::class, Worker::class);
    }

    public function equipmentAssets(): HasMany
    {
        return $this->hasMany(EquipmentAsset::class);
    }

    public function riskProfileItems(): MorphMany
    {
        return $this->morphMany(RiskProfileItem::class, 'profileable');
    }

    public function riskMeasures(): MorphMany
    {
        return $this->morphMany(RiskMeasure::class, 'profileable');
    }

    public function dvrDocuments(): HasMany
    {
        return $this->hasMany(DvrDocument::class);
    }
}
