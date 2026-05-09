<?php

namespace Modules\RessourcesLogistique\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Emprunt extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'emprunts';

    protected $fillable = [
        'exemplaire_id',
        'apprenant_id',
        'date_emprunt',
        'date_retour_prevue',
        'date_retour_reelle',
        'statut',
    ];

    protected $casts = [
        'date_emprunt' => 'date',
        'date_retour_prevue' => 'date',
        'date_retour_reelle' => 'date',
    ];

    // Relations
    public function exemplaire(): BelongsTo
    {
        return $this->belongsTo(Exemplaire::class, 'exemplaire_id');
    }

    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(\Modules\Academique\Entities\Apprenant::class, 'apprenant_id');
    }

    // Scopes
    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    public function scopeRetourne($query)
    {
        return $query->where('statut', 'retourne');
    }

    public function scopeEnRetard($query)
    {
        return $query->where('statut', 'en_retard')
            ->orWhere(function ($q) {
                $q->where('statut', 'en_cours')
                  ->where('date_retour_prevue', '<', now()->toDateString());
            });
    }

    // Méthodes métier
    public function isEnCours(): bool
    {
        return $this->statut === 'en_cours';
    }

    public function isRetourne(): bool
    {
        return $this->statut === 'retourne';
    }

    public function isEnRetard(): bool
    {
        return $this->statut === 'en_retard' ||
               ($this->statut === 'en_cours' && $this->date_retour_prevue < now()->toDateString());
    }

    public function getNombreJoursRetard(): int
    {
        if (!$this->isEnRetard()) {
            return 0;
        }
        $dateReference = $this->date_retour_reelle ?? now()->toDateString();
        return \Carbon\Carbon::parse($dateReference)
            ->diffInDays(\Carbon\Carbon::parse($this->date_retour_prevue), false);
    }
}
