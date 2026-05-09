<?php

namespace Modules\Finances\Entities;

use App\Models\BaseModel;

class GroupeCompte extends BaseModel
{

    protected $table = 'groupes_comptes';
    protected $fillable = [
        'code_groupe',
        'libelle_groupes',
        'nombre_comptes',
        'liste_comptes',
        'description',
        'etat',
        'creation_username',
        'modification_username'
    ];

    /**
     * Relations
     */
    public function planComptes()
    {
        return $this->hasMany(PlanCompte::class, 'groupe_comptes_id');
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
