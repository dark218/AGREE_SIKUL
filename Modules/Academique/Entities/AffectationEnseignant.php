<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Parametrage\Entities\{AnneeScolaire, Classe, Ecole, Institution, Campus, MatiereUnite};

class AffectationEnseignant extends BaseModel
{
    use HasFactory;

    protected $table = 'affectations_enseignants';

    protected $fillable = [
        'annee_scolaire_id',
        'enseignant_id',
        'classe_id',
        'ecole_id',
        'institution_id',
        'campus_id',
        'etat',
        'creation_username',
        'modification_username',
    ];

    protected $casts = [
        'etat' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class, 'enseignant_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'classe_id');
    }

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class, 'ecole_id');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'campus_id');
    }

    /**
     * Matières affectées à cet enseignant sur cette classe.
     * Remplace les 21 colonnes hardcodées `matiere_1_id..matiere_21_id`
     * (anti-pattern historique) par une vraie relation n-n via la pivot
     * `affectation_matieres`.
     */
    public function matieres(): BelongsToMany
    {
        return $this->belongsToMany(
            MatiereUnite::class,
            'affectation_matieres',
            'affectation_enseignant_id',
            'matiere_id'
        )->withTimestamps();
    }

    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    public function isActif(): bool
    {
        return $this->etat === 'actif';
    }

    public function countMatieres(): int
    {
        return $this->matieres()->count();
    }
}
