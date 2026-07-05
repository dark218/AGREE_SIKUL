<?php

namespace Modules\Academique\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;

class Bibliotheque extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = "bibliotheques";

    protected $fillable = [
        'sujet',
        'langue',
        'niveau_id',
        'type_manuel',
        'titre_manuel',
        'auteurs',
        'editeurs',
        'annee_edition',
        'quantite',
        'sorties',
        'disponibles',
        'etat',
        'creation_username',
        'modification_username',
    ];

    protected $casts = [
        'annee_edition' => 'integer',
        'quantite' => 'integer',
        'sorties' => 'integer',
        'disponibles' => 'integer',
    ];

    // Relations
    public function niveau()
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Niveau::class, 'niveau_id');
    }

    /**
     * Mouvements d'entrée de ce livre (nommé ...Livres pour ne pas entrer en
     * conflit avec la colonne "sorties").
     */
    public function entreesLivres()
    {
        return $this->hasMany(EntreeLivre::class, 'bibliotheque_id');
    }

    public function sortiesLivres()
    {
        return $this->hasMany(SortieLivre::class, 'bibliotheque_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    public function scopeInactif($query)
    {
        return $query->where('etat', 'inactif');
    }

    public function isActif(): bool
    {
        return $this->etat === 'actif';
    }
}
