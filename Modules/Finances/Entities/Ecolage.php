<?php

namespace Modules\Finances\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\NiveauEtude;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Campus;
use Modules\Parametrage\Entities\Section;
use Modules\Parametrage\Entities\CycleEnseignement;

class Ecolage extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'ecolages';

    protected $fillable = [
        'annee_scolaire_id',
        'niveau_id',
        'ecole_id',
        'campus_id',
        'section_id',
        'cycle_id',
        'frais_dossier',
        'frais_inscription',
        'frais_scolarite',
        'etat',
        'creation_username',
        'modification_username',
    ];

    protected $casts = [
        'frais_dossier' => 'decimal:2',
        'frais_inscription' => 'decimal:2',
        'frais_scolarite' => 'decimal:2',
    ];

    // Relations
    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(NiveauEtude::class, 'niveau_id');
    }

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class, 'ecole_id');
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

    public function frais(): HasMany
    {
        return $this->hasMany(EcolageFrais::class, 'ecolage_id')->orderBy('ordre');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    // Méthodes métier
    public function isActif(): bool
    {
        return $this->etat === 'actif';
    }
}
