<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NiveauEtude extends BaseModel
{
    use HasFactory;

    protected $table = 'niveaux_etudes';

    protected $fillable = [
        'code',
        'sigle',
        'libelle',
        'cycle_id',
        'pays_id',
        'annee_scolaire_id',
        'etat',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relations
    public function pays(): BelongsTo
    {
        return $this->belongsTo(Pays::class, 'pays_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(CycleEnseignement::class, 'cycle_id');
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function natureExamens(): HasMany
    {
        return $this->hasMany(NatureExamen::class, 'niveau_id');
    }

    public function typeExamens(): HasMany
    {
        return $this->hasMany(TypeExamen::class, 'niveau_id');
    }

    public function matieresUnites(): HasMany
    {
        return $this->hasMany(MatiereUnite::class, 'niveau_id');
    }

    public function groupesMatieres(): HasMany
    {
        return $this->hasMany(GroupeMatiere::class, 'niveau_id');
    }

    public function typeApprenants(): HasMany
    {
        return $this->hasMany(TypeApprenant::class, 'niveau_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    public function scopeInactif($query)
    {
        return $query->where('etat', 'inactif');
    }

    // Méthodes utiles
    public function isActif(): bool
    {
        return $this->etat === 'actif';
    }

    public function activate(): self
    {
        $this->update(['etat' => 'actif']);
        return $this;
    }

    public function deactivate(): self
    {
        $this->update(['etat' => 'inactif']);
        return $this;
    }
}
