<?php

namespace Modules\Finances\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paiement extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'paiements';

    protected $fillable = [
        'frais_id',
        'payable_type',
        'payable_id',
        'apprenant_id',
        'montant_cents',
        'mode_paiement',
        'reference',
        'date_paiement',
        'recu_par',
    ];

    protected $casts = [
        'montant_cents' => 'integer',
        'date_paiement' => 'date',
    ];

    // Relations
    public function frais(): BelongsTo
    {
        return $this->belongsTo(Frais::class, 'frais_id');
    }

    /**
     * Cible polymorphe du paiement — peut être un Versement,
     * AchatDepense, AutreRevenu, Salaire (avance), ou Frais.
     * Voir §10.4 : consolidation des 4 modélisations concurrentes.
     */
    public function payable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'apprenant_id');
    }

    public function recuPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recu_par');
    }

    // Scopes — enum DB : espece, cheque, virement, mobile_money, carte
    public function scopeEspeces($query)
    {
        return $query->where('mode_paiement', 'espece');
    }

    public function scopeVirement($query)
    {
        return $query->where('mode_paiement', 'virement');
    }

    public function scopeCarte($query)
    {
        return $query->where('mode_paiement', 'carte');
    }

    public function scopeCheque($query)
    {
        return $query->where('mode_paiement', 'cheque');
    }

    public function scopeMobileMoney($query)
    {
        return $query->where('mode_paiement', 'mobile_money');
    }

    // Méthodes métier
    public function getMontantEnEuros(): float
    {
        return $this->montant_cents / 100;
    }

    public function getModePaiement(): string
    {
        return ucfirst(str_replace('_', ' ', $this->mode_paiement));
    }
}
