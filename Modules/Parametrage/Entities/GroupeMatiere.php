<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupeMatiere extends BaseModel
{
    use HasFactory;

    protected $table = 'groupes_matieres';

    protected $fillable = [
        'code',
        'libelle',
        'niveau_id',
        'section_id',
        'cycle_id',
        'matiere1_id',
        'matiere2_id',
        'matiere3_id',
        'matiere4_id',
        'matiere5_id',
        'matiere6_id',
        'matiere7_id',
        'matiere8_id',
        'matiere9_id',
        'matiere10_id',
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

    // Relations
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

    public function matiere1(): BelongsTo
    {
        return $this->belongsTo(MatiereUnite::class, 'matiere1_id');
    }

    public function matiere2(): BelongsTo
    {
        return $this->belongsTo(MatiereUnite::class, 'matiere2_id');
    }

    public function matiere3(): BelongsTo
    {
        return $this->belongsTo(MatiereUnite::class, 'matiere3_id');
    }

    public function matiere4(): BelongsTo
    {
        return $this->belongsTo(MatiereUnite::class, 'matiere4_id');
    }

    public function matiere5(): BelongsTo
    {
        return $this->belongsTo(MatiereUnite::class, 'matiere5_id');
    }

    public function matiere6(): BelongsTo
    {
        return $this->belongsTo(MatiereUnite::class, 'matiere6_id');
    }

    public function matiere7(): BelongsTo
    {
        return $this->belongsTo(MatiereUnite::class, 'matiere7_id');
    }

    public function matiere8(): BelongsTo
    {
        return $this->belongsTo(MatiereUnite::class, 'matiere8_id');
    }

    public function matiere9(): BelongsTo
    {
        return $this->belongsTo(MatiereUnite::class, 'matiere9_id');
    }

    public function matiere10(): BelongsTo
    {
        return $this->belongsTo(MatiereUnite::class, 'matiere10_id');
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function pays(): BelongsTo
    {
        return $this->belongsTo(Pays::class, 'pays_id');
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

    public function getMatieres(): array
    {
        $matieres = [];
        for ($i = 1; $i <= 10; $i++) {
            $field = 'matiere' . $i . '_id';
            $method = 'matiere' . $i;
            if ($this->$field) {
                $matieres[] = $this->$method;
            }
        }
        return $matieres;
    }
}
