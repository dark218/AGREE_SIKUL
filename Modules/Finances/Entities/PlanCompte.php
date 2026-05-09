<?php

namespace Modules\Finances\Entities;

use App\Models\BaseModel;

class PlanCompte extends BaseModel
{

    protected $table = 'plan_comptes';

    protected $fillable = [
        'groupe_comptes_id',
        'numero_compte',
        'libelle_compte',
        'libelle_court',
        'compte_parent_id',
        'etat',
        'creation_username',
        'modification_username',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function groupeCompte()
    {
        return $this->belongsTo(GroupeCompte::class, 'groupe_comptes_id');
    }

    public function compteParent()
    {
        return $this->belongsTo(PlanCompte::class, 'compte_parent_id');
    }

    public function comptesEnfants()
    {
        return $this->hasMany(PlanCompte::class, 'compte_parent_id');
    }

    /**
     * Scopes
     */
    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    /**
     * Helpers
     */
    public function isActif()
    {
        return $this->etat === 'actif';
    }
}
