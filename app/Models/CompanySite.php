<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySite extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'site_code',
        'is_headquarters',
        'address_line',
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
}
