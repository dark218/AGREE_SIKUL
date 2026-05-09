<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AbsenceEnseignant extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'absences_enseignants';

    protected $fillable = [
        'enseignant_id',
        'matiere_id',
        'classe_id',
        'date_debut',
        'date_fin',
        'nombre_heures',
        'motif',
        'justificatif_path',
        'statut',
        'etat',
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'nombre_heures' => 'decimal:2',
        'justificatif_path' => 'json',
    ];

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class, 'enseignant_id');
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Matiere::class, 'matiere_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Classe::class, 'classe_id');
    }

    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    public function isActif(): bool
    {
        return $this->etat === 'actif';
    }
}
