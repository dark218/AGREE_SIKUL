<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inscription extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'inscriptions';

    protected $fillable = [
        'apprenant_id', 'classe_id', 'annee_scolaire_id',
        'ecole_id', 'campus_id', 'institution_id',
        'numero_inscription', 'date_inscription',
        'type_inscription', 'statut',
        'premiere_inscription',
        'frais_dossier', 'frais_inscription', 'frais_scolarite',
        'frais_dossier_paye', 'frais_inscription_paye', 'frais_scolarite_paye',
        'dossier_complet',
        'fiche_inscription', 'carnet_vaccination', 'photos_4x4',
        'copie_acte_naissance', 'piece1', 'piece2', 'piece3', 'piece4',
    ];

    protected $casts = [
        'date_inscription' => 'date',
        'premiere_inscription' => 'boolean',
        'dossier_complet' => 'boolean',
        'frais_dossier' => 'decimal:2',
        'frais_inscription' => 'decimal:2',
        'frais_scolarite' => 'decimal:2',
        'frais_dossier_paye' => 'decimal:2',
        'frais_inscription_paye' => 'decimal:2',
        'frais_scolarite_paye' => 'decimal:2',
    ];

    protected $appends = [
        'frais_dossier_restant', 'frais_inscription_restant', 'frais_scolarite_restant',
        'total_paye', 'total_restant',
    ];

    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class, 'apprenant_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Classe::class, 'classe_id');
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Ecole::class, 'ecole_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Campus::class, 'campus_id');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Institution::class, 'institution_id');
    }

    public function getFraisDossierRestantAttribute(): float
    {
        return max(0, (float)$this->frais_dossier - (float)$this->frais_dossier_paye);
    }

    public function getFraisInscriptionRestantAttribute(): float
    {
        return max(0, (float)$this->frais_inscription - (float)$this->frais_inscription_paye);
    }

    public function getFraisScolariteRestantAttribute(): float
    {
        return max(0, (float)$this->frais_scolarite - (float)$this->frais_scolarite_paye);
    }

    public function getTotalPayeAttribute(): float
    {
        return (float)$this->frais_dossier_paye + (float)$this->frais_inscription_paye + (float)$this->frais_scolarite_paye;
    }

    public function getTotalRestantAttribute(): float
    {
        return $this->frais_dossier_restant + $this->frais_inscription_restant + $this->frais_scolarite_restant;
    }

    public function scopeActif($query)
    {
        return $query->where('statut', 'validee');
    }

    public function isActif(): bool
    {
        return $this->statut === 'validee';
    }
}
