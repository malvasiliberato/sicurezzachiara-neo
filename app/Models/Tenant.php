<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'owner_user_id',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_memberships')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function jobRoles(): HasMany
    {
        return $this->hasMany(JobRole::class);
    }

    public function equipmentTypes(): HasMany
    {
        return $this->hasMany(EquipmentType::class);
    }

    public function workplaceTypes(): HasMany
    {
        return $this->hasMany(WorkplaceType::class);
    }

    public function riskCatalogItems(): HasMany
    {
        return $this->hasMany(RiskCatalogItem::class);
    }

    public function workers(): HasManyThrough
    {
        return $this->hasManyThrough(Worker::class, Company::class);
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class);
    }

    public function dvrDocuments(): HasMany
    {
        return $this->hasMany(DvrDocument::class);
    }

    public function equipmentAssets(): HasManyThrough
    {
        return $this->hasManyThrough(EquipmentAsset::class, Company::class);
    }
}
