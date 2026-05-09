<?php

namespace Modules\Finances\Entities;

use App\Models\BaseModel;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\CycleEnseignement;
use Modules\Parametrage\Entities\Niveau;
use Modules\Parametrage\Entities\Section;
use Modules\Parametrage\Entities\Campus;
use Modules\Parametrage\Entities\Ecole;

class FacturationApprenant extends BaseModel
{

    protected $table = 'facturation_apprenants';

    protected $fillable = [
        'annee_scolaire_id',
        'section_id',
        'ecole_id',
        'campus_id',
        'cycle_id',
        'niveau_id',
        'code',
        'libelle',
        'ligne_recette',
        'unite_facturation',
        'quantite',
        'montant',
        'date_debut_exigibilite',
        'date_fin_exigibilite',
        'compte_comptable',
        'etat',
        'creation_username',
        'modification_username',
    ];

    protected $casts = [
        'quantite' => 'decimal:2',
        'montant' => 'decimal:2',
        'date_debut_exigibilite' => 'date',
        'date_fin_exigibilite' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'ecole_id');
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class, 'campus_id');
    }

    public function cycle()
    {
        return $this->belongsTo(CycleEnseignement::class, 'cycle_id');
    }

    public function niveau()
    {
        return $this->belongsTo(Niveau::class, 'niveau_id');
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
