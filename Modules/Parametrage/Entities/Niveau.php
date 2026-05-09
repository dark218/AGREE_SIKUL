<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Niveau extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'niveaux';

    protected $fillable = [
        'ecole_id',
        'code',
        'libelle',
        'ordre',
        'age_min',
        'age_max',
        'cycle',
        'statut',
    ];

    // Relations
    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class, 'ecole_id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class, 'niveau_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeOrdonnes($query)
    {
        return $query->orderBy('ordre');
    }
}
