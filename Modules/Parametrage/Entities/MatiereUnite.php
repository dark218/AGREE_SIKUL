<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatiereUnite extends BaseModel
{
    use HasFactory;

    protected $table = 'matieres_unites';

    protected $fillable = [
        'code',
        'libelle',
        'ecole_id',
        'niveau_id',
        'section_id',
        'cycle_id',
        'note_max',
        'coefficient',
        'type_matiere',
        'volume_horaire',
        'est_obligatoire',
        'etat',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'note_max' => 'decimal:2',
        'coefficient' => 'decimal:2',
        'est_obligatoire' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relations
    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class, 'ecole_id');
    }

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

    public function groupesMatieres(): HasMany
    {
        return $this->hasMany(GroupeMatiere::class, 'matiere1_id');
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
