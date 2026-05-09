<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classe extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'ecole_id',
        'niveau_id',
        'campus_id',
        'section_id',
        'cycle_id',
        'enseignant_titulaire_id',
        'annee_scolaire_id',
        'nom',
        'code_salle',
        'libelle_affichage',
        'capacite_max',
        'salle',
        'statut',
        'etat',
        'creation_username',
        'creation_hostname',
        'modification_username',
        'modification_hostname',
        'deletion_username',
        'deletion_hostname',
    ];

    // Relations
    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class, 'ecole_id');
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class, 'niveau_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'campus_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(CycleEnseignement::class, 'cycle_id');
    }

    public function enseignantTitulaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enseignant_titulaire_id');
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function apprenants(): HasMany
    {
        return $this->hasMany('Modules\Academique\Entities\Apprenant', 'classe_id');
    }

    // Les inscriptions seront définies dans GestionApprenants
    // COMMENTED: Module GestionApprenants not yet available - will be enabled later
    // public function inscriptions()
    // {
    //     return $this->hasMany('Modules\GestionApprenants\Entities\Inscription', 'classe_id');
    // }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    // Méthodes métier
    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }

    // COMMENTED: Depends on inscriptions() relation
    // public function getNombreInscrits(): int
    // {
    //     return $this->inscriptions()
    //         ->where('statut', 'validee')
    //         ->count();
    // }

    // public function getCapaciteDisponible(): int
    // {
    //     return max(0, ($this->capacite_max ?? 0) - $this->getNombreInscrits());
    // }

    // public function isPleineCapacite(): bool
    // {
    //     return $this->capacite_max !== null &&
    //            $this->getNombreInscrits() >= $this->capacite_max;
    // }
}
