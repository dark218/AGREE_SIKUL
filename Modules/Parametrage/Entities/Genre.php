<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Genre — table de référentiel pour le sexe/genre paramétrable.
 *
 * L'admin peut créer/modifier/désactiver des genres depuis
 * `/parametrage/genres`. Utilisé par Apprenant et Enseignant via
 * `genre_id`.
 */
class Genre extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'genres';

    // Tri par ordre custom (pas created_at desc)
    protected $defaultOrderBy = 'ordre';
    protected $defaultOrderDir = 'asc';

    protected $fillable = [
        'code',
        'libelle',
        'symbole',
        'couleur',
        'ordre',
        'etat',
    ];

    public function apprenants(): HasMany
    {
        return $this->hasMany(\Modules\Academique\Entities\Apprenant::class, 'genre_id');
    }

    public function enseignants(): HasMany
    {
        return $this->hasMany(\Modules\Academique\Entities\Enseignant::class, 'genre_id');
    }

    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }
}
