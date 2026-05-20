<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DvrDocumentSection extends Model
{
    use HasFactory;

    public const STATUS_AUTO_READY = 'auto_ready';

    public const STATUS_NEEDS_INPUT = 'needs_input';

    public const STATUS_NEEDS_REVIEW = 'needs_review';

    public const STATUS_VALIDATED = 'validated';

    public const STATUS_EXCLUDED = 'excluded';

    public const MODE_AUTOMATIC = 'automatic';

    public const MODE_SEMI_MANUAL = 'semi_manual';

    public const MODE_MANUAL = 'manual';

    public const MODE_TEMPLATE = 'template';

    public const SOURCE_LIVE = 'live';

    public const SOURCE_SNAPSHOT = 'snapshot';

    public const SOURCE_MANUAL = 'manual';

    protected $fillable = [
        'dvr_document_id',
        'section_key',
        'title',
        'status',
        'generation_mode',
        'source_status',
        'payload',
        'manual_content',
        'consultant_notes',
        'validated_at',
        'validated_by_user_id',
        'sort_order',
    ];

    protected $casts = [
        'payload' => 'array',
        'validated_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    public function dvrDocument(): BelongsTo
    {
        return $this->belongsTo(DvrDocument::class);
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by_user_id');
    }
}
