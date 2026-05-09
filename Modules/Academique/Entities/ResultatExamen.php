<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Modules\Parametrage\Entities\Classe;

class ResultatExamen extends BaseModel
{

    protected $table = 'resultats_examens';
    protected $fillable = [
        'examen_en_ligne_id',
        'apprenant_id',
        'classe_id',
        'note_obtenue',
        'pourcentage',
        'statut',
        'date_composition',
        'nombre_questions_repondues',
        'nombre_questions_correctes',
        'temps_passe_minutes',
        'creation_username',
        'modification_username'
    ];

    protected $casts = [
        'date_composition' => 'datetime',
        'note_obtenue' => 'decimal:2',
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

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function isReussi(): bool
    {
        return $this->statut === 'reussi';
    }

    public function isEchoue(): bool
    {
        return $this->statut === 'echoue';
    }
}
