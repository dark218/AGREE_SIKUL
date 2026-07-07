<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Modules\Parametrage\Entities\NatureExamen;
use Modules\Parametrage\Entities\TypeExamen;
use Modules\Parametrage\Entities\Classe;
use Modules\Parametrage\Entities\MatiereUnite;

class PlanificationExamen extends BaseModel
{

    protected $table = 'planifications_examens';
    protected $fillable = [
        'nature_examen_id',
        'type_examen_id',
        'classe_id',
        'matiere_id',
        'enseignant_id',
        'jour',
        'date',
        'heure_debut',
        'heure_fin',
        'duree',
        'etat',
        'creation_username',
        'modification_username'
    ];

    protected $casts = [
        'date' => 'date',
        'heure_debut' => 'datetime:H:i',
        'heure_fin' => 'datetime:H:i',
        'duree' => 'decimal:2'
    ];

    public function natureExamen()
    {
        return $this->belongsTo(NatureExamen::class, 'nature_examen_id');
    }

    public function typeExamen()
    {
        return $this->belongsTo(TypeExamen::class, 'type_examen_id');
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function matiere()
    {
        return $this->belongsTo(MatiereUnite::class);
    }

    // Finance Relations
    public function financingAssociations()
    {
        return $this->hasMany(PlanificationExamenPosteRecette::class, 'exam_id');
    }

    public function posteRecettes()
    {
        return $this->belongsToMany(
            \Modules\Finances\Entities\PosteRecette::class,
            'exam_poste_recette'
        )
        ->withPivot(['montant_finance', 'etat_financement', 'date_facturation', 'date_limite_paiement', 'notes'])
        ->withTimestamps();
    }

    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    public function isActif(): bool
    {
        return $this->etat === 'actif';
    }

    /**
     * Finance Helpers
     */
    public function isFinanced(): bool
    {
        return $this->financingAssociations()
            ->where('etat_financement', 'actif')
            ->exists();
    }

    public function isFundingPending(): bool
    {
        return $this->financingAssociations()
            ->where('etat_financement', 'en-attente')
            ->exists();
    }

    public function getFundingStatus(): string
    {
        $associations = $this->financingAssociations()
            ->whereIn('etat_financement', ['actif', 'en-attente'])
            ->first();

        if (!$associations) {
            return 'non-finance';
        }

        return $associations->etat_financement;
    }

    public function getMontantFinanceTotal(): float
    {
        return (float) $this->financingAssociations()
            ->where('etat_financement', 'actif')
            ->sum('montant_finance');
    }

    public function getTauxCouverture(): float
    {
        // À implémenter avec logique métier du coût estimé
        return 0;
    }
}
