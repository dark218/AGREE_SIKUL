<?php

namespace Modules\Finances\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Fichier;

class Depense extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'depenses';

    protected $fillable = [
        'ecole_id',
        'categorie',
        'libelle',
        'montant_cents',
        'date_depense',
        'facture_id',
        'auteur_id',
    ];

    protected $casts = [
        'montant_cents' => 'integer',
        'date_depense' => 'date',
    ];

    // Relations
    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class, 'ecole_id');
    }

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Fichier::class, 'facture_id');
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    // Scopes
    public function scopeByCategorie($query, string $categorie)
    {
        return $query->where('categorie', $categorie);
    }

    public function scopeByEcole($query, int $ecoleId)
    {
        return $query->where('ecole_id', $ecoleId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('date_depense', 'desc');
    }

    // Méthodes métier
    public function getMontantEnEuros(): float
    {
        return $this->montant_cents / 100;
    }

    public function hasFacture(): bool
    {
        return $this->facture_id !== null;
    }
}
