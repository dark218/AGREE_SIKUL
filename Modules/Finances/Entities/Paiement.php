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

    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'apprenant_id');
    }

    public function recuPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recu_par');
    }

    // Scopes
    public function scopeEspeces($query)
    {
        return $query->where('mode_paiement', 'especes');
    }

    public function scopeVirement($query)
    {
        return $query->where('mode_paiement', 'virement');
    }

    public function scopeCartebancaire($query)
    {
        return $query->where('mode_paiement', 'carte_bancaire');
    }

    public function scopeCheque($query)
    {
        return $query->where('mode_paiement', 'cheque');
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
