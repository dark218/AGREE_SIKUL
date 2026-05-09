<?php

namespace Modules\Finances\Entities;

use App\Models\BaseModel;

class PosteRecette extends BaseModel
{

    protected $table = 'postes_recettes';
    protected $fillable = [
        'code',
        'libelle',
        'compte_comptable',
        'ligne_recette_id',
        'etat',
        'creation_username',
        'modification_username'
    ];

    public function ligneRecette()
    {
        return $this->belongsTo(LigneRecette::class, 'ligne_recette_id');
    }

    // Exam Finance Relations
    public function planificationExamens()
    {
        return $this->belongsToMany(
            \Modules\Academique\Entities\PlanificationExamen::class,
            'exam_poste_recette'
        )
        ->withPivot(['montant_finance', 'etat_financement', 'date_facturation', 'date_limite_paiement', 'notes'])
        ->withTimestamps();
    }

    public function examFinancings()
    {
        return $this->hasMany(\Modules\Academique\Entities\PlanificationExamenPosteRecette::class, 'recette_id');
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
