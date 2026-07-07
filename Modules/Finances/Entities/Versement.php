<?php

namespace Modules\Finances\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\NiveauEtude;
use Modules\Parametrage\Entities\Classe;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Campus;
use Modules\Academique\Entities\Apprenant;

class Versement extends BaseModel
{
    use SoftDeletes;

    protected $table = 'versements';

    protected $fillable = [
        'annee_scolaire_id', 'apprenant_id', 'niveau_id', 'classe_id',
        'ecole_id', 'campus_id',
        'frais_dossier', 'frais_inscription', 'frais_scolarite',
        'total_paye', 'restant_a_payer',
        'nature_versement_1', 'montant_versement_1',
        'nature_versement_2', 'montant_versement_2',
        'nature_versement_3', 'montant_versement_3',
        'nature_versement_4', 'montant_versement_4',
        'nature_versement_5', 'montant_versement_5',
        'nature_versement_6', 'montant_versement_6',
        'nature_versement_7', 'montant_versement_7',
        'nature_versement_8', 'montant_versement_8',
        'nature_versement_9', 'montant_versement_9',
        'nature_versement_10', 'montant_versement_10',
        'nature_versement_11', 'montant_versement_11',
        'nature_versement_12', 'montant_versement_12',
        'etat', 'creation_username', 'modification_username',
    ];

    protected $casts = [
        'frais_dossier' => 'decimal:2',
        'frais_inscription' => 'decimal:2',
        'frais_scolarite' => 'decimal:2',
        'total_paye' => 'decimal:2',
        'restant_a_payer' => 'decimal:2',
        'montant_versement_1' => 'decimal:2',
        'montant_versement_2' => 'decimal:2',
        'montant_versement_3' => 'decimal:2',
        'montant_versement_4' => 'decimal:2',
        'montant_versement_5' => 'decimal:2',
        'montant_versement_6' => 'decimal:2',
        'montant_versement_7' => 'decimal:2',
        'montant_versement_8' => 'decimal:2',
        'montant_versement_9' => 'decimal:2',
        'montant_versement_10' => 'decimal:2',
        'montant_versement_11' => 'decimal:2',
        'montant_versement_12' => 'decimal:2',
    ];

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class, 'apprenant_id');
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(NiveauEtude::class, 'niveau_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'classe_id');
    }

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class, 'ecole_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'campus_id');
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
