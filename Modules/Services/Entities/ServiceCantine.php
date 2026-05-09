<?php

namespace Modules\Services\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceCantine extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'services_cantines';

    protected $fillable = [
        'code',
        'nom',
        'prix_cents',
        'description',
        'capacite',
        'responsable_id',
        'annee_scolaire_id',
        'niveau_id',
        'cycle_enseignement_id',
        'ecole_id',
        'campus_id',
        'tarif_mensuel',
        'tarif_trimestriel',
        'tarif_semestriel',
        'tarif_annuel',
        'date_debut',
        'date_fin',
        'statut',
    ];

    protected $casts = [
        'capacite' => 'integer',
        'prix_cents' => 'integer',
        'tarif_mensuel' => 'integer',
        'tarif_trimestriel' => 'integer',
        'tarif_semestriel' => 'integer',
        'tarif_annuel' => 'integer',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    // Relations
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Niveau::class, 'niveau_id');
    }

    public function cycleEnseignement(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\CycleEnseignement::class, 'cycle_enseignement_id');
    }

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Ecole::class, 'ecole_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Campus::class, 'campus_id');
    }

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class, 'service_cantine_id');
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(InscriptionCantine::class, 'service_cantine_id');
    }

    public function passages(): HasMany
    {
        return $this->hasMany(PassageCantine::class, 'service_cantine_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->whereNull('deleted_at');
    }

    // Méthodes métier
    public function getNombreInscrits(): int
    {
        return $this->inscriptions()
            ->where('statut', 'actif')
            ->count();
    }

    public function getTauxOccupation(): float
    {
        return $this->capacite > 0 ? ($this->getNombreInscrits() / $this->capacite) * 100 : 0;
    }
}
