<?php

namespace Modules\RessourcesLogistique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exemplaire extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'exemplaires';

    protected $fillable = [
        'ouvrage_id',
        'code_exemplaire',
        'numero_serie',
        'etat',
        'localisation',
        'date_acquisition',
        'date_derniere_maintenance',
        'statut',
    ];

    // Relations
    public function ouvrage(): BelongsTo
    {
        return $this->belongsTo(Ouvrage::class, 'ouvrage_id');
    }

    public function emprunts(): HasMany
    {
        return $this->hasMany(Emprunt::class, 'exemplaire_id');
    }

    // Scopes
    public function scopeDisponible($query)
    {
        return $query->where('statut', 'disponible');
    }

    public function scopeEmprunte($query)
    {
        return $query->where('statut', 'emprunte');
    }

    public function scopeBonEtat($query)
    {
        return $query->where('etat', 'bon');
    }

    public function scopeMauvaisEtat($query)
    {
        return $query->where('etat', 'mauvais');
    }

    // Méthodes métier
    public function isDisponible(): bool
    {
        return $this->statut === 'disponible';
    }

    public function isEmprunte(): bool
    {
        return $this->statut === 'emprunte';
    }

    public function getEmpruntEnCours(): ?Emprunt
    {
        return $this->emprunts()
            ->where('statut', 'en_cours')
            ->latest()
            ->first();
    }
}
