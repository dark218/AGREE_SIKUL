<?php

namespace Modules\Finances\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\AnneeScolaire;

class Frais extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'frais';

    protected $fillable = [
        'apprenant_id',
        'annee_scolaire_id',
        'type_frais_id',
        'montant_cents',
        'montant_paye_cents',
        'statut',
    ];

    protected $casts = [
        'montant_cents' => 'integer',
        'montant_paye_cents' => 'integer',
    ];

    // Relations
    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'apprenant_id');
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function typeFrais(): BelongsTo
    {
        return $this->belongsTo(TypeFrais::class, 'type_frais_id');
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class, 'frais_id');
    }

    public function echeanciers(): HasMany
    {
        return $this->hasMany(Echeancier::class, 'frais_id');
    }

    // Scopes
    public function scopePaye($query)
    {
        return $query->where('statut', 'paye');
    }

    public function scopePartielPaye($query)
    {
        return $query->where('statut', 'partiel_paye');
    }

    public function scopeNonPaye($query)
    {
        return $query->where('statut', 'non_paye');
    }

    // Méthodes métier
    public function isComplet(): bool
    {
        return $this->montant_paye_cents >= $this->montant_cents;
    }

    public function getRestant(): int
    {
        return max(0, $this->montant_cents - $this->montant_paye_cents);
    }

    public function getTauxPaiement(): float
    {
        return $this->montant_cents > 0 ? ($this->montant_paye_cents / $this->montant_cents) * 100 : 0;
    }
}
