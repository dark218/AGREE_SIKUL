<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\Campus;

class Ecole extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'ecoles';

    protected $fillable = [
        'campus_id',
        'institution_id',
        'code',
        'nom',
        'sigle',
        'devise',          // legacy
        'devise_slogan',   // nouveau : slogan libre
        'devise_comptabilite_id',
        'type_etablissement_id',
        'type_enseignement',   // legacy (string)
        'type_enseignement_id', // nouveau (FK)
        'type_cours_id',
        'section_id',
        'localisation',
        'date_creation',
        'numero_agrement',
        'ministere_tutelle',
        'logo',
        'logo_id',
        'directeur_id',
        'capacite_totale',
        'capacite_maximale',
        'statut',
        // Adresse et localisation (legacy strings)
        'adresse_siege',
        'code_postal',
        'boite_postale',
        'ville',
        'quartier',
        'commune',
        'departement',
        'region',
        'pays_id',
        // Nouvelles FK localisation
        'quartier_id',
        'commune_id',
        'departement_id',
        'region_id',
        // Contacts
        'telephone_principal',
        'telephone_2',
        'telephone_3',
        'whatsapp_1',
        'whatsapp_2',
        'fax',
        'email_principal',
        'email_1',
        'site_web',
        'facebook',
        'linkedin',
        'twitter',
        // Description
        'description',
        'vision',
        'mission',
    ];

    protected $casts = [
        'date_creation' => 'date',
    ];

    // Relations
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'campus_id');
    }

    public function typeEtablissement(): BelongsTo
    {
        return $this->belongsTo(TypeEtablissement::class, 'type_etablissement_id');
    }

    public function typeCours(): BelongsTo
    {
        return $this->belongsTo(TypeCours::class, 'type_cours_id');
    }

    public function typeEnseignement(): BelongsTo
    {
        return $this->belongsTo(TypeEnseignement::class, 'type_enseignement_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function niveaux(): HasMany
    {
        return $this->hasMany(Niveau::class, 'ecole_id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class, 'ecole_id');
    }

    public function directeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'directeur_id');
    }

    public function dirigeants(): HasMany
    {
        return $this->hasMany(EcoleDirigent::class, 'ecole_id')->ordered();
    }

    public function pays(): BelongsTo
    {
        return $this->belongsTo(Pays::class, 'pays_id');
    }

    public function quartierRef(): BelongsTo
    {
        return $this->belongsTo(Quartier::class, 'quartier_id');
    }

    public function communeRef(): BelongsTo
    {
        return $this->belongsTo(Commune::class, 'commune_id');
    }

    public function departementRef(): BelongsTo
    {
        return $this->belongsTo(Departement::class, 'departement_id');
    }

    public function regionRef(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function deviseComptabilite(): BelongsTo
    {
        return $this->belongsTo(Devises::class, 'devise_comptabilite_id');
    }

    public function logoFile(): BelongsTo
    {
        return $this->belongsTo(Fichier::class, 'logo_id');
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
