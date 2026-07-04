<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\{Classe, Campus, Commune, Departement, Region, Pays, Section, CycleEnseignement, Ecole, Quartier, AnneeScolaire, TypeApprenant, CategorieApprenant};

class Apprenant extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'apprenants';

    protected $fillable = [
        'user_id',
        'matricule',
        'numero_inscription',
        'nom',
        'prenoms',
        'email',
        'telephone',
        'telephone2',
        'whatsapp1',
        'whatsapp2',
        'date_naissance',
        'sexe',
        'nationalite',
        'groupe_sanguin',
        'photo',
        'genre_id',
        'adresse',
        'classe_id',
        'section_id',
        'cycle_id',
        'ecole_id',
        'campus_id',
        'annee_scolaire_id',
        'type_apprenant_id',
        'categorie_apprenant_id',
        'statut',
        'lieu_naissance',
        'commune_naissance_id',
        'departement_naissance_id',
        'region_naissance_id',
        'pays_naissance_id',
        'ecole_precedente',
        'classe_precedente',
        'est_interne',
        'batiment',
        'etage',
        'chambre',
        'numero_lit',
        'nom_pere',
        'nom_mere',
        'nom_tuteur',
        'nom_responsable_legal',
        'quartier_id',
        'commune_residence_id',
        'departement_residence_id',
        'region_residence_id',
        'pays_residence_id',
        'arrondissement',
        'ville',
        'code_postal',
        'boite_postal',
        'date_entree_ecole',
        'date_depart_ecole',
        'motif_depart_ecole',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_entree_ecole' => 'date',
        'date_depart_ecole' => 'date',
        'est_interne' => 'boolean',
    ];

    // Relations - User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relations - Contacts humains (N-N)
    public function parents(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \Modules\Personnel\Entities\StudentParent::class,
            'apprenant_parent',
            'apprenant_id',
            'parent_id'
        )->withPivot('lien_parente', 'est_principal')->withTimestamps();
    }

    public function tuteurs(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            Tuteur::class,
            'apprenant_tuteur',
            'apprenant_id',
            'tuteur_id'
        )->withPivot('relation', 'est_principal')->withTimestamps();
    }

    public function accompagnateurs(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \Modules\Personnel\Entities\Accompagnateur::class,
            'apprenant_accompagnateur',
            'apprenant_id',
            'accompagnateur_id'
        )->withPivot('est_principal')->withTimestamps();
    }

    public function genre(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Genre::class, 'genre_id');
    }

    // Relations - Academic
    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'classe_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(CycleEnseignement::class, 'cycle_id');
    }

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class, 'ecole_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'campus_id');
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function typeApprenant(): BelongsTo
    {
        return $this->belongsTo(TypeApprenant::class, 'type_apprenant_id');
    }

    public function categorieApprenant(): BelongsTo
    {
        return $this->belongsTo(CategorieApprenant::class, 'categorie_apprenant_id');
    }

    // Relations - Birth Location
    public function communeNaissance(): BelongsTo
    {
        return $this->belongsTo(Commune::class, 'commune_naissance_id');
    }

    public function departementNaissance(): BelongsTo
    {
        return $this->belongsTo(Departement::class, 'departement_naissance_id');
    }

    public function regionNaissance(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_naissance_id');
    }

    public function paysNaissance(): BelongsTo
    {
        return $this->belongsTo(Pays::class, 'pays_naissance_id');
    }

    // Relations - Residence Location
    public function quartier(): BelongsTo
    {
        return $this->belongsTo(Quartier::class, 'quartier_id');
    }

    public function communeResidence(): BelongsTo
    {
        return $this->belongsTo(Commune::class, 'commune_residence_id');
    }

    public function departementResidence(): BelongsTo
    {
        return $this->belongsTo(Departement::class, 'departement_residence_id');
    }

    public function regionResidence(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_residence_id');
    }

    public function paysResidence(): BelongsTo
    {
        return $this->belongsTo(Pays::class, 'pays_residence_id');
    }

    // Relations - School Data
    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class, 'apprenant_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'apprenant_id');
    }

    public function bulletins(): HasMany
    {
        return $this->hasMany(Bulletin::class, 'apprenant_id');
    }

    public function absences(): HasMany
    {
        return $this->hasMany(AbsenceApprenant::class, 'apprenant_id');
    }

    public function presences(): HasMany
    {
        return $this->hasMany(Presence::class, 'apprenant_id');
    }

    public function dossiers(): HasMany
    {
        return $this->hasMany(Dossier::class, 'apprenant_id');
    }

    public function rendus(): HasMany
    {
        return $this->hasMany(Rendu::class, 'apprenant_id');
    }

    public function moyennes(): HasMany
    {
        return $this->hasMany(Moyenne::class, 'apprenant_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }
}
