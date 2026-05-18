<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerJobRoleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_id',
        'job_role_id',
        'is_primary',
        'assigned_on',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'assigned_on' => 'date',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function jobRole(): BelongsTo
    {
        return $this->belongsTo(JobRole::class);
    }
}
