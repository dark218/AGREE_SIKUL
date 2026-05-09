<?php

namespace Modules\Finances\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Echeancier extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'echeanciers';

    protected $fillable = [
        'frais_id',
        'numero_echeance',
        'montant_cents',
        'date_echeance',
        'date_paiement',
        'statut',
    ];

    protected $casts = [
        'numero_echeance' => 'integer',
        'montant_cents' => 'integer',
        'date_echeance' => 'date',
        'date_paiement' => 'date',
    ];

    // Relations
    public function frais(): BelongsTo
    {
        return $this->belongsTo(Frais::class, 'frais_id');
    }

    // Scopes
    public function scopePaye($query)
    {
        return $query->where('statut', 'paye');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopeEnRetard($query)
    {
        return $query->where('statut', 'en_retard')
            ->orWhere(function ($q) {
                $q->where('statut', 'en_attente')
                  ->where('date_echeance', '<', now()->toDateString());
            });
    }

    // Méthodes métier
    public function isPaye(): bool
    {
        return $this->statut === 'paye';
    }

    public function isEnRetard(): bool
    {
        return $this->statut === 'en_retard' ||
               ($this->statut === 'en_attente' && $this->date_echeance < now()->toDateString());
    }

    public function getMontantEnEuros(): float
    {
        return $this->montant_cents / 100;
    }
}
