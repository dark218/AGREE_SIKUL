<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CycleEnseignement extends BaseModel
{
    use HasFactory;

    protected $table = 'cycles_enseignement';

    protected $fillable = [
        'code',
        'libelle',
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
    public function niveauxEtudes(): HasMany
    {
        return $this->hasMany(NiveauEtude::class, 'cycle_id');
    }

    public function typeCours(): HasMany
    {
        return $this->hasMany(TypeCours::class, 'cycle_id');
    }

    public function typeExamens(): HasMany
    {
        return $this->hasMany(TypeExamen::class, 'cycle_id');
    }

    public function matieresUnites(): HasMany
    {
        return $this->hasMany(MatiereUnite::class, 'cycle_id');
    }

    public function groupesMatieres(): HasMany
    {
        return $this->hasMany(GroupeMatiere::class, 'cycle_id');
    }

    public function typeApprenants(): HasMany
    {
        return $this->hasMany(TypeApprenant::class, 'cycle_id');
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
