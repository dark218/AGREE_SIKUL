<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\Campus;
use Modules\Parametrage\Entities\Fichier;
use Modules\Parametrage\Entities\Pays;

class Institution extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'institutions';

    protected $fillable = [
        'code',
        'nom',
        'sigle',
        'type',
        'statut_juridique',
        'numero_autorisation',
        'numero_agrement_2',
        'numero_agrement_3',
        'numero_agrement_4',
        'date_creation',
        'logo_id',
        'cachet_id',
        'directeur_general_id',
        'promoteur',
        'gerant',
        'email_principal',
        'telephone_principal',
        'site_web',
        'adresse_siege',
        // Legacy strings (compat ancienne data)
        'code_postal',
        'boite_postale',
        'quartier',
        'commune',
        'ville',
        'departement',
        'region',
        // Nouvelles FK localisation
        'quartier_id',
        'commune_id',
        'departement_id',
        'region_id',
        'pays_id',
        // Devises
        'devise_principale',     // legacy (varchar 3 — XOF/EUR...)
        'devise_slogan',         // nouveau : slogan libre
        'devise_comptabilite_id',// nouveau : FK devises
        // Ministères
        'ministere_tutelle_1',
        'ministere_tutelle_2',
        'ministere_tutelle_3',
        'ministere_tutelle_4',
        // Contacts
        'telephone_1',
        'telephone_2',
        'telephone_3',
        'whatsapp_1',
        'whatsapp_2',
        'fax',
        'email_1',
        'email_2',
        'facebook',
        'linkedin',
        'twitter',
        'fuseau_horaire',
        'langue_principale',
        'langues_secondaires',
        'description',
        'vision',
        'mission',
        'valeurs',
        'statut',
    ];

    protected $casts = [
        'date_creation' => 'date',
        'langues_secondaires' => 'array',
        'valeurs' => 'array',
    ];

    // Relations
    public function directeurGeneral(): BelongsTo
    {
        return $this->belongsTo(User::class, 'directeur_general_id');
    }

    public function campus(): HasMany
    {
        return $this->hasMany(Campus::class, 'institution_id');
    }

    public function logo(): BelongsTo
    {
        return $this->belongsTo(Fichier::class, 'logo_id');
    }

    public function cachet(): BelongsTo
    {
        return $this->belongsTo(Fichier::class, 'cachet_id');
    }

    public function pays(): BelongsTo
    {
        return $this->belongsTo(Pays::class, 'pays_id');
    }

    public function quartier(): BelongsTo
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

    public function getNombreCampuses(): int
    {
        return $this->campus()->count();
    }
}
