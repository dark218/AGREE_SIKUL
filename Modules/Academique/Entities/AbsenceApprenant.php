<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AbsenceApprenant extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'absences_apprenants';

    protected $fillable = [
        'apprenant_id',
        'annee_scolaire_id',
        'classe_id',
        'ecole_id',
        'campus_id',
        'matiere_id',
        'enseignant_id',
        'date_debut',
        'date_fin',
        'nombre_heures',
        'motif',
        'commentaire',
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

    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class, 'apprenant_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Classe::class, 'classe_id');
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\MatiereUnite::class, 'matiere_id');
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class, 'enseignant_id');
    }

    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }
}
