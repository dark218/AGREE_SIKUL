<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoyenPaiement extends BaseModel
{
    use HasFactory;

    protected $table = 'moyens_paiement';

    protected $fillable = [
        'code',
        'libelle',
        'type_mode',
        'necessite_reference',
        'delai_compensation_jours',
        'frais_pourcentage',
        'frais_fixe',
        'montant_min',
        'montant_max',
        'fournisseur_paiement_id',
        'etat',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'frais_pourcentage' => 'decimal:2',
        'frais_fixe' => 'decimal:2',
        'montant_min' => 'decimal:2',
        'montant_max' => 'decimal:2',
        'necessite_reference' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relations
    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\FournisseurPaiement::class, 'fournisseur_paiement_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    public function scopeInactif($query)
    {
        return $query->where('etat', 'inactif');
    }

    // Méthodes utiles
    public function isActif(): bool
    {
        return $this->etat === 'actif';
    }

    public function activate(): self
    {
        $this->update(['etat' => 'actif']);
        return $this;
    }

    public function deactivate(): self
    {
        $this->update(['etat' => 'inactif']);
        return $this;
    }
}
