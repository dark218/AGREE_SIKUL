<?php

namespace Modules\Finances\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Campus;

class Salaire extends BaseModel
{
    use SoftDeletes;

    protected $table = 'salaires';

    protected $fillable = [
        'annee_scolaire_id', 'ecole_id', 'campus_id',
        'nom', 'institution', 'mois_paie', 'intitule',
        'noms', 'prenoms', 'nom_restituer',
        'matricule_interne', 'matricule_cnps', 'numero_identifiant',
        'salaire_base', 'primes', 'indemnites', 'salaire_brut',
        'retenues_fiscales', 'retenues_sociales', 'autres_retenues',
        'saisies_oppositions', 'salaire_net',
        'paiement_integral', 'date_paiement_integral',
        'avance1', 'date_avance1', 'avance2', 'date_avance2',
        'avance3', 'date_avance3', 'avance4', 'date_avance4',
        'total_paye', 'restant_a_payer',
        'etat', 'creation_username', 'modification_username',
    ];

    protected $casts = [
        'salaire_base' => 'decimal:2',
        'primes' => 'decimal:2',
        'indemnites' => 'decimal:2',
        'salaire_brut' => 'decimal:2',
        'retenues_fiscales' => 'decimal:2',
        'retenues_sociales' => 'decimal:2',
        'autres_retenues' => 'decimal:2',
        'saisies_oppositions' => 'decimal:2',
        'salaire_net' => 'decimal:2',
        'paiement_integral' => 'decimal:2',
        'date_paiement_integral' => 'date',
        'avance1' => 'decimal:2',
        'date_avance1' => 'date',
        'avance2' => 'decimal:2',
        'date_avance2' => 'date',
        'avance3' => 'decimal:2',
        'date_avance3' => 'date',
        'avance4' => 'decimal:2',
        'date_avance4' => 'date',
        'total_paye' => 'decimal:2',
        'restant_a_payer' => 'decimal:2',
    ];

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
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
