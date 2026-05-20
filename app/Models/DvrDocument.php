<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DvrDocument extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_VALIDATED = 'validated';

    public const STATUS_FROZEN = 'frozen';

    public const STATUS_EXPORTED = 'exported';

    public const STATUS_ARCHIVED = 'archived';

    public const COMPLETENESS_INCOMPLETE = 'incomplete';

    public const COMPLETENESS_REVIEW_REQUIRED = 'review_required';

    public const COMPLETENESS_COMPLETE = 'complete';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'status',
        'version_number',
        'title',
        'generated_from_live_at',
        'validated_at',
        'validated_by_user_id',
        'frozen_at',
        'frozen_by_user_id',
        'supersedes_document_id',
        'revision_reason',
        'completeness_status',
        'snapshot_payload',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'generated_from_live_at' => 'datetime',
        'validated_at' => 'datetime',
        'frozen_at' => 'datetime',
        'snapshot_payload' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(DvrDocumentSection::class)->orderBy('sort_order')->orderBy('id');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by_user_id');
    }

    public function frozenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'frozen_by_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function supersedesDocument(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_document_id');
    }
}
