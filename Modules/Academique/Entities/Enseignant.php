<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\{Commune, Departement, Region, Pays, CategorieEnseignant, CycleEnseignement, Classe, MatiereUnite, NiveauEtude};

class Enseignant extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'enseignants';

    protected $fillable = [
        // Identifiant
        'user_id', 'num_enseignant', 'matricule',
        // Identité personnelle
        'nom', 'prenoms', 'nom_restituer', 'nom_jeune_fille',
        'gender', 'genre_id', 'marital_status', 'date_of_birth', 'place_of_birth',
        'commune_id', 'department_id', 'region_id', 'country_id', 'nationalite',
        // Formation
        'highest_diploma', 'speciality', 'year_obtained', 'languages', 'teaching_speciality',
        // Emploi
        'type_contrat', 'nature_contrat_id', 'date_embauche', 'teacher_category', 'categorie_enseignant_id', 'fonction_id',
        // Contact
        'email', 'telephone',
        // Photo & statut
        'photo', 'statut',
    ];

    protected $casts = [
        'date_embauche' => 'date',
        'date_of_birth' => 'date',
        'languages' => 'array',
    ];

    public function genre(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Genre::class, 'genre_id');
    }

    public function fonction(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Fonction::class, 'fonction_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cours(): HasMany
    {
        return $this->hasMany(Cours::class, 'enseignant_id');
    }

    public function seances(): HasMany
    {
        return $this->hasMany(Seance::class, 'enseignant_id');
    }

    public function emploisDuTemps(): HasMany
    {
        return $this->hasMany(EmploiTemps::class, 'enseignant_id');
    }

    public function absences(): HasMany
    {
        return $this->hasMany(AbsenceEnseignant::class, 'enseignant_id');
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class, 'commune_id');
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class, 'department_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function pays(): BelongsTo
    {
        return $this->belongsTo(Pays::class, 'country_id');
    }

    public function categorieEnseignant(): BelongsTo
    {
        return $this->belongsTo(CategorieEnseignant::class, 'categorie_enseignant_id');
    }

    public function matieres(): BelongsToMany
    {
        // Pointe vers les MatiereUnite paramétrées (matieres_unites)
        return $this->belongsToMany(MatiereUnite::class, 'enseignant_matieres', 'enseignant_id', 'matiere_id');
    }

    public function cycles(): BelongsToMany
    {
        return $this->belongsToMany(CycleEnseignement::class, 'enseignant_cycles_enseignement');
    }

    public function niveaux(): BelongsToMany
    {
        // Pointe vers les NiveauEtude paramétrés (niveaux_etudes)
        return $this->belongsToMany(NiveauEtude::class, 'enseignant_niveaux', 'enseignant_id', 'niveau_id');
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(Classe::class, 'enseignant_classes');
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
