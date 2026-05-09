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
        'code',
        'nom',
        'sigle',
        'devise',
        'type_etablissement_id',
        'type_cours_id',
        'institution_id',
        'localisation',
        'date_creation',
        'numero_agrement',
        'ministere_tutelle',
        'logo',
        'type_enseignement',
        'directeur_id',
        'capacite_totale',
        'statut',
        // Adresse et localisation
        'adresse_siege',
        'code_postal',
        'boite_postale',
        'ville',
        'quartier',
        'commune',
        'departement',
        'region',
        'pays_id',
        // Contacts - Téléphones
        'telephone_principal',
        'telephone_2',
        'telephone_3',
        // Contacts - WhatsApp
        'whatsapp_1',
        'whatsapp_2',
        // Contacts - Autres
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
}
