<?php

namespace Modules\Finances\Entities;

use App\Models\BaseModel;

class LigneDepense extends BaseModel
{

    protected $table = 'lignes_depenses';
    protected $fillable = [
        'code',
        'libelle',
        'compte_comptable',
        'etat',
        'creation_username',
        'modification_username'
    ];

    public function postesDepenses()
    {
        return $this->hasMany(PosteDepense::class, 'ligne_depense_id');
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
