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
        'code',                 // nouveau
        'nom',                  // legacy
        'libelle',              // nouveau standard
        'libelle_affichage',
        'code_salle',           // legacy
        'salle',                // legacy
        'batiment',             // nouveau
        'capacite_max',
        'capacite_actuelle',    // nouveau
        'statut',
        'etat',
        'creation_username',
        'creation_hostname',
        'modification_username',
        'modification_hostname',
        'deletion_username',
        'deletion_hostname',
    ];

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class, 'ecole_id');
    }

    public function niveau(): BelongsTo
    {
        // niveau_id référence la table niveaux_etudes (NiveauEtude), pas Niveau,
        // car c'est cette liste qui alimente le formulaire Classe.
        return $this->belongsTo(NiveauEtude::class, 'niveau_id');
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

    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }
}
