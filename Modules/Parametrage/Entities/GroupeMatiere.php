<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GroupeMatiere extends BaseModel
{
    use HasFactory;

    protected $table = 'groupes_matieres';

    protected $fillable = [
        'code',
        'libelle',
        'ecole_id',
        'institution_id',
        'niveau_id',
        'section_id',
        'cycle_id',
        'annee_scolaire_id',
        'pays_id',
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

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(NiveauEtude::class, 'niveau_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(CycleEnseignement::class, 'cycle_id');
    }

    /**
     * Matières du groupe (relation n-n via pivot groupe_matiere_items).
     * Remplace les 10 colonnes hardcodées matiere1_id..matiere10_id
     * (anti-pattern historique).
     */
    public function matieres(): BelongsToMany
    {
        return $this->belongsToMany(
            MatiereUnite::class,
            'groupe_matiere_items',
            'groupe_matiere_id',
            'matiere_id'
        )->withTimestamps();
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function pays(): BelongsTo
    {
        return $this->belongsTo(Pays::class, 'pays_id');
    }

    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    public function scopeInactif($query)
    {
        return $query->where('etat', 'inactif');
    }

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
