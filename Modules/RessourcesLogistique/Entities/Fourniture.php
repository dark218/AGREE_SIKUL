<?php

namespace Modules\RessourcesLogistique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fourniture extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'fournitures';

    protected $fillable = [
        'categorie_fourniture_id',
        'libelle',
        'code',
        'quantite',
        'prix_unitaire_cents',
        'fournisseur',
        'date_acquisition',
        'statut',
        'localisation',
    ];

    protected $casts = [
        'date_acquisition' => 'date',
        'prix_unitaire_cents' => 'integer',
        'quantite' => 'integer',
    ];

    // Relations
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieFourniture::class, 'categorie_fourniture_id');
    }

    // Scopes
    public function scopeByCategorie($query, int $categorieId)
    {
        return $query->where('categorie_fourniture_id', $categorieId);
    }

    public function scopeByStatut($query, string $statut)
    {
        return $query->where('statut', $statut);
    }

    public function scopeDisponible($query)
    {
        return $query->where('statut', 'disponible');
    }

    public function scopeRupture($query)
    {
        return $query->where('statut', 'rupture');
    }

    public function scopeCommandee($query)
    {
        return $query->where('statut', 'commandee');
    }

    // Méthodes métier
    public function getPrixEnEuros(): float
    {
        return $this->prix_unitaire_cents / 100;
    }

    public function getValeurTotalEnEuros(): float
    {
        return ($this->prix_unitaire_cents * $this->quantite) / 100;
    }

    public function isDisponible(): bool
    {
        return $this->statut === 'disponible';
    }
}
