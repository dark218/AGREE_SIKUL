<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Seance extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'seances';

    protected $fillable = [
        'code',
        'titre',
        'sujet',
        'date',
        'heure_debut',
        'heure_fin',
        'duree',
        'cours_id',
        'classe_id',
        'matiere_id',
        'enseignant_id',
        'salle_id',
        'type_seance',
        'statut',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function classe(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Classe::class, 'classe_id');
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class, 'matiere_id');
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class, 'enseignant_id');
    }

    public function cours(): BelongsTo
    {
        return $this->belongsTo(Cours::class, 'cours_id');
    }

    public function salle(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Salle::class, 'salle_id');
    }

    public function presences(): HasMany
    {
        return $this->hasMany(Presence::class, 'seance_id');
    }

    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }
}
