<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Matiere extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'matieres';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'coefficient',
        'note_max',
        'niveau_id',
        'section_id',
        'cycle_id',
        'annee_scolaire_id',
        'pays_id',
        'ecole_id',
        'statut',
        'creation_username',
        'modification_username',
        'deletion_username',
    ];

    protected $casts = [
        'coefficient' => 'decimal:2',
        'note_max' => 'decimal:2',
    ];

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Niveau::class, 'niveau_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Section::class, 'section_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\CycleEnseignement::class, 'cycle_id');
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function pays(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Pays::class, 'pays_id');
    }

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Ecole::class, 'ecole_id');
    }

    public function cours(): HasMany
    {
        return $this->hasMany(Cours::class, 'matiere_id');
    }

    public function seances(): HasMany
    {
        return $this->hasMany(Seance::class, 'matiere_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'matiere_id');
    }

    public function devoirs(): HasMany
    {
        return $this->hasMany(Devoir::class, 'matiere_id');
    }

    public function moyennes(): HasMany
    {
        return $this->hasMany(Moyenne::class, 'matiere_id');
    }

    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }
}
