<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;

class TentativeExamen extends BaseModel
{

    protected $table = 'tentatives_examen';
    protected $fillable = [
        'examen_en_ligne_id',
        'apprenant_id',
        'numero_tentative',
        'debut_composition',
        'fin_composition',
        'temps_passe_minutes',
        'score',
        'pourcentage',
        'questions_repondues',
        'reponses_correctes',
        'statut',
        'ip_address',
        'nb_changements_onglet',
        'nb_tentatives_copie',
        'nb_sorties_fullscreen',
        'nb_incidents_total',
        'suspicion_triche',
        'navigateur',
        'creation_username',
    ];

    protected $casts = [
        'debut_composition' => 'datetime',
        'fin_composition' => 'datetime',
        'score' => 'decimal:2',
        'pourcentage' => 'decimal:2',
        'temps_passe_minutes' => 'decimal:2',
    ];

    public function examenEnLigne()
    {
        return $this->belongsTo(ExamenEnLigne::class);
    }

    public function apprenant()
    {
        return $this->belongsTo(Apprenant::class);
    }

    public function logsSurveillance()
    {
        return $this->hasMany(LogSurveillanceExamen::class, 'tentative_examen_id');
    }

    public function reponses()
    {
        return $this->hasMany(ReponseApprenant::class, 'tentative_examen_id');
    }

    public function isEnCours(): bool
    {
        return $this->statut === 'en_cours';
    }

    public function isSoumise(): bool
    {
        return $this->statut === 'soumise';
    }

    public function isCorrigee(): bool
    {
        return $this->statut === 'corrigee';
    }
}
