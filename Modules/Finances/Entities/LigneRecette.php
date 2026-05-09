<?php

namespace Modules\Finances\Entities;

use App\Models\BaseModel;

class LigneRecette extends BaseModel
{

    protected $table = 'lignes_recettes';
    protected $fillable = [
        'code',
        'libelle',
        'compte_comptable',
        'etat',
        'creation_username',
        'modification_username'
    ];

    public function postesRecettes()
    {
        return $this->hasMany(PosteRecette::class, 'ligne_recette_id');
    }

    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    public function isActif(): bool
    {
        return $this->etat === 'actif';
    }
}
