<?php

namespace Modules\Academique\Entities;

use Illuminate\Database\Eloquent\Model;

class ReponseApprenant extends Model
{
    protected $table = 'reponses_apprenant';
    protected $fillable = [
        'tentative_examen_id',
        'question_examen_id',
        'reponse_choisie_id',
        'reponse_libre',
        'est_correcte',
        'points_obtenus',
    ];

    protected $casts = [
        'est_correcte' => 'boolean',
        'points_obtenus' => 'decimal:2',
    ];

    public function tentative()
    {
        return $this->belongsTo(TentativeExamen::class, 'tentative_examen_id');
    }

    public function question()
    {
        return $this->belongsTo(QuestionExamen::class, 'question_examen_id');
    }

    public function reponseChoisie()
    {
        return $this->belongsTo(ReponseQuestion::class, 'reponse_choisie_id');
    }
}
