<?php

namespace Modules\Rapport\Entities;

use App\Models\BaseModel;

class StatistiquesEcole extends BaseModel
{

    protected $table = 'statistiques_ecole';

    protected $fillable = [
        'classe_id',
        'ecole_id',
        'institution_id',
        'campus_id',
        'nombre_inscrits',
        'nombre_filles',
        'nombre_garcons',
        'nombre_enseignants',
        'nombre_enseignants_permanent',
        'nombre_enseignants_vacataires',
        'enseignant_referent',
        'produits_ecole',
        'services_offerts',
        'etat',
        'creation_username',
        'modification_username',
    ];

    protected $casts = [
        'nombre_inscrits' => 'integer',
        'nombre_filles' => 'integer',
        'nombre_garcons' => 'integer',
        'nombre_enseignants' => 'integer',
        'nombre_enseignants_permanent' => 'integer',
        'nombre_enseignants_vacataires' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function classe()
    {
        return $this->belongsTo(\Modules\Academique\Entities\Classe::class);
    }

    public function ecole()
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Ecole::class);
    }

    public function institution()
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Institution::class);
    }

    public function campus()
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Campus::class);
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
