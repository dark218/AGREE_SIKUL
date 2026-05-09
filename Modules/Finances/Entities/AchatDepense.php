<?php

namespace Modules\Finances\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\Section;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Campus;

class AchatDepense extends BaseModel
{
    use HasFactory;

    protected $table = 'achats_depenses';

    protected $fillable = [
        'annee_scolaire_id',
        'section_id',
        'ecole_id',
        'campus_id',
        'date_depense',
        'nature_depense',
        'tiers_fournisseur',
        'numero_identifiant',
        'type_piece',
        'reference_piece',
        'intitule_operation',
        'montant',
        'mode_paiement',
        'date_paiement_1',
        'montant_paiement_1',
        'date_paiement_2',
        'montant_paiement_2',
        'date_paiement_3',
        'montant_paiement_3',
        'date_paiement_4',
        'montant_paiement_4',
        'date_paiement_5',
        'montant_paiement_5',
        'date_paiement_6',
        'montant_paiement_6',
        'montant_total_paye',
        'restant_a_payer',
        'etat',
        'creation_username',
        'modification_username',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'montant_paiement_1' => 'decimal:2',
        'montant_paiement_2' => 'decimal:2',
        'montant_paiement_3' => 'decimal:2',
        'montant_paiement_4' => 'decimal:2',
        'montant_paiement_5' => 'decimal:2',
        'montant_paiement_6' => 'decimal:2',
        'montant_total_paye' => 'decimal:2',
        'restant_a_payer' => 'decimal:2',
        'date_depense' => 'date',
        'date_paiement_1' => 'date',
        'date_paiement_2' => 'date',
        'date_paiement_3' => 'date',
        'date_paiement_4' => 'date',
        'date_paiement_5' => 'date',
        'date_paiement_6' => 'date',
    ];

    /**
     * Relations
     */

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

    /**
     * Scopes
     */

    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    public function scopeInactif($query)
    {
        return $query->where('etat', 'inactif');
    }

    /**
     * Helpers
     */

    public function isActif(): bool
    {
        return $this->etat === 'actif';
    }
}
