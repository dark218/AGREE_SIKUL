<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;

class ReponseQuestion extends BaseModel
{

    protected $table = 'reponses_question';
    protected $fillable = [
        'question_examen_id',
        'ordre',
        'texte',
        'est_correcte',
        'explication',
    ];

    protected $casts = [
        'est_correcte' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(QuestionExamen::class, 'question_examen_id');
    }
}
