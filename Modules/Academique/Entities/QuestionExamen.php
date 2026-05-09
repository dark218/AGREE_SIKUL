<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;

class QuestionExamen extends BaseModel
{

    protected $table = 'questions_examen';
    protected $fillable = [
        'examen_en_ligne_id',
        'ordre',
        'type',
        'enonce',
        'explication',
        'image',
        'points',
        'obligatoire',
        'etat',
        'creation_username',
        'modification_username'
    ];

    protected $casts = [
        'points' => 'decimal:2',
        'obligatoire' => 'boolean',
    ];

    public function examenEnLigne()
    {
        return $this->belongsTo(ExamenEnLigne::class);
    }

    public function reponses()
    {
        return $this->hasMany(ReponseQuestion::class, 'question_examen_id')->orderBy('ordre');
    }

    public function reponsesApprenant()
    {
        return $this->hasMany(ReponseApprenant::class, 'question_examen_id');
    }

    public function reponseCorrecte()
    {
        return $this->hasOne(ReponseQuestion::class, 'question_examen_id')->where('est_correcte', true);
    }

    public function isQcm(): bool
    {
        return $this->type === 'qcm';
    }

    public function isVraiFaux(): bool
    {
        return $this->type === 'vrai_faux';
    }

    public function isReponseLibre(): bool
    {
        return $this->type === 'reponse_libre';
    }

    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }
}
