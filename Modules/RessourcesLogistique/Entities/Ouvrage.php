<?php

namespace Modules\RessourcesLogistique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ouvrage extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'ouvrages';

    protected $fillable = [
        'bibliotheque_id',
        'titre',
        'auteur',
        'isbn',
        'editeur',
        'langue',
        'annee_publication',
        'categorie',
        'nombre_exemplaires',
        'statut',
        'description',
    ];

    protected $casts = [
        'annee_publication' => 'integer',
        'nombre_exemplaires' => 'integer',
    ];

    // Helper scope for soft deleted records
    public function scopeWithTrashed($query)
    {
        return $query->withTrashed();
    }

    // Relations
    public function bibliotheque(): BelongsTo
    {
        return $this->belongsTo(Bibliotheque::class, 'bibliotheque_id');
    }

    public function exemplaires(): HasMany
    {
        return $this->hasMany(Exemplaire::class, 'ouvrage_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'ouvrage_id');
    }

    // Scopes
    public function scopeByCategorie($query, string $categorie)
    {
        return $query->where('categorie', $categorie);
    }

    public function scopeByAuteur($query, string $auteur)
    {
        return $query->where('auteur', 'like', "%{$auteur}%");
    }

    // Méthodes métier
    public function getNombreDisponibles(): int
    {
        return $this->exemplaires()
            ->where('statut', 'disponible')
            ->count();
    }

    public function getNombreEmpruntesEnCours(): int
    {
        return $this->exemplaires()
            ->whereHas('emprunts', function ($q) {
                $q->where('statut', 'en_cours');
            })
            ->count();
    }
}
