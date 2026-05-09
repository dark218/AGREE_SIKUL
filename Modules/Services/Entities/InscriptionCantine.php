<?php

namespace Modules\Services\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Academique\Entities\Apprenant;

class InscriptionCantine extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'inscriptions_cantine';

    protected $fillable = [
        'service_cantine_id',
        'apprenant_id',
        'annee_scolaire_id',
        'type_formule',
        'date_inscription',
        'date_debut',
        'date_fin',
        'nombre_jours',
        'observations',
        'statut',
    ];

    // Relations
    public function serviceCantine(): BelongsTo
    {
        return $this->belongsTo(ServiceCantine::class, 'service_cantine_id');
    }

    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class, 'apprenant_id');
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function passages(): HasMany
    {
        return $this->hasMany(PassageCantine::class, 'inscription_cantine_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeInactif($query)
    {
        return $query->where('statut', 'inactif');
    }

    public function scopePensionnaire($query)
    {
        return $query->where('type_formule', 'pensionnaire');
    }

    public function scopeDemiPensionnaire($query)
    {
        return $query->where('type_formule', 'demi_pensionnaire');
    }

    // Méthodes métier
    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }

    public function isPensionnaire(): bool
    {
        return $this->type_formule === 'pensionnaire';
    }

    public function isDemiPensionnaire(): bool
    {
        return $this->type_formule === 'demi_pensionnaire';
    }
}
