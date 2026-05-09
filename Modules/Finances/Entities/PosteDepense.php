<?php

namespace Modules\Finances\Entities;

use App\Models\BaseModel;

class PosteDepense extends BaseModel
{

    protected $table = 'postes_depenses';
    protected $fillable = [
        'code',
        'libelle',
        'compte_comptable',
        'ligne_depense_id',
        'etat',
        'creation_username',
        'modification_username'
    ];

    public function ligneDepense()
    {
        return $this->belongsTo(LigneDepense::class, 'ligne_depense_id');
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
