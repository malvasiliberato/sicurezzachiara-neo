<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Worker extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'primary_site_id',
        'first_name',
        'last_name',
        'tax_code',
        'email',
        'phone',
        'birth_date',
        'hire_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
    ];

    protected $appends = [
        'full_name',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function primarySite(): BelongsTo
    {
        return $this->belongsTo(CompanySite::class, 'primary_site_id');
    }

    public function jobRoleAssignments(): HasMany
    {
        return $this->hasMany(WorkerJobRoleAssignment::class)
            ->orderByDesc('is_primary')
            ->orderBy('assigned_on')
            ->orderBy('id');
    }

    public function jobRoles(): BelongsToMany
    {
        return $this->belongsToMany(JobRole::class, 'worker_job_role_assignments')
            ->withPivot(['id', 'is_primary', 'assigned_on', 'notes'])
            ->withTimestamps();
    }

    public function equipmentExposures(): HasMany
    {
        return $this->hasMany(WorkerEquipmentExposure::class)
            ->orderByDesc('is_primary')
            ->orderBy('id');
    }

    public function workplacesExposures(): HasMany
    {
        return $this->hasMany(WorkerWorkplaceExposure::class)
            ->orderByDesc('is_primary')
            ->orderBy('id');
    }

    public function workplaceExposures(): HasMany
    {
        return $this->workplacesExposures();
    }

    public function riskProfileItems(): MorphMany
    {
        return $this->morphMany(RiskProfileItem::class, 'profileable');
    }

    public function riskMeasures(): MorphMany
    {
        return $this->morphMany(RiskMeasure::class, 'profileable');
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->first_name} {$this->last_name}"));
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}
