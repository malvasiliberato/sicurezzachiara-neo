<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ateco2025 extends Model
{
    use HasFactory;

    protected $table = 'ateco_2025';

    protected $fillable = [
        'codice',
        'titolo_it',
        'titolo_en',
        'livello',
        'codice_padre',
        'livello_padre',
        'ordine',
    ];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'ateco_2025_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'codice_padre', 'codice');
    }
}
