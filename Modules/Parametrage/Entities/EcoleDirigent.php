<?php

namespace Modules\Parametrage\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcoleDirigent extends Model
{
    use HasFactory;

    protected $table = 'ecole_dirigeants';

    protected $fillable = [
        'ecole_id',
        'nom',
        'prenom',
        'nom_restituer',
        'fonction',
        'ordre',
    ];

    protected $casts = [
        'ordre' => 'integer',
    ];

    // Relations
    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class, 'ecole_id');
    }

    // Scopes
    public function scopeOrdered($query)
    {
        return $query->orderBy('ordre', 'asc');
    }
}
