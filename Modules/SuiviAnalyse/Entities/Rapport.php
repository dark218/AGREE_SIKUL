<?php

namespace Modules\SuiviAnalyse\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\GestionStock\Entities\Fichier;

class Rapport extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'rapports';

    protected $fillable = [
        'modele_rapport_id',
        'titre',
        'parametres_utilises',
        'fichier_id',
        'genere_par',
        'date_generation',
    ];

    protected $casts = [
        'parametres_utilises' => 'array',
        'date_generation' => 'datetime',
    ];

    // Relations
    public function modeleRapport(): BelongsTo
    {
        return $this->belongsTo(ModeleRapport::class, 'modele_rapport_id');
    }

    public function fichier(): BelongsTo
    {
        return $this->belongsTo(Fichier::class, 'fichier_id');
    }

    public function generePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'genere_par');
    }

    // Scopes
    public function scopeByModele($query, int $modeleId)
    {
        return $query->where('modele_rapport_id', $modeleId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('date_generation', 'desc');
    }

    public function scopeWithFichier($query)
    {
        return $query->whereNotNull('fichier_id');
    }

    // Méthodes métier
    public function hasFichier(): bool
    {
        return $this->fichier_id !== null;
    }

    public function getParametres(): array
    {
        return $this->parametres_utilises ?? [];
    }

    public function getAgeRapport(): string
    {
        return $this->date_generation?->diffForHumans() ?? 'Inconnu';
    }
}
