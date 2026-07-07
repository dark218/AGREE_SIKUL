<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'notes';

    protected $fillable = [
        'apprenant_id',
        'evaluation_id',
        'annee_scolaire_id',
        'section_id',
        'cycle_id',
        'classe_id',
        'ecole_id',
        'campus_id',
        'periode_id',
        'nature_examen_id',
        'type_examen_id',
        'date_examen',
        'matiere_id',
        'groupe_id',
        'note',
        'note_originale',
        'note_sur',
        'enseignant_id',
        'statut',
        'remarques',
    ];

    protected $casts = [
        'date_examen' => 'date',
        'note' => 'decimal:2',
        'note_originale' => 'decimal:2',
        'note_sur' => 'decimal:2',
    ];

    // Relations
    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class, 'apprenant_id');
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class, 'evaluation_id');
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Section::class, 'section_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\CycleEnseignement::class, 'cycle_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Classe::class, 'classe_id');
    }

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Ecole::class, 'ecole_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Campus::class, 'campus_id');
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\PeriodeColaire::class, 'periode_id');
    }

    public function natureExamen(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\NatureExamen::class, 'nature_examen_id');
    }

    public function typeExamen(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\TypeExamen::class, 'type_examen_id');
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(MatiereUnite::class, 'matiere_id');
    }

    public function groupe(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\GroupeMatiere::class, 'groupe_id');
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(\Modules\Academique\Entities\Enseignant::class, 'enseignant_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('statut', 'validee');
    }

    public function isActif(): bool
    {
        return $this->statut === 'validee';
    }
}
