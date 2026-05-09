<?php

namespace Modules\Rapport\Entities;

use App\Models\BaseModel;

class StatistiquesClasses extends BaseModel
{

    protected $table = 'statistiques_classes';

    protected $fillable = [
        'annee_scolaire_id',
        'classe_id',
        'ecole_id',
        'institution_id',
        'campus_id',
        'enseignant_referent',
        'effectif_total',
        'nombre_filles',
        'nombre_garcons',
        'total_frais_scolarite',
        'total_scolarite_paye',
        'total_scolarite_non_paye',
        'total_achats_depenses',
        'total_salaires',
        'etat',
        'creation_username',
        'modification_username',
    ];

    protected $casts = [
        'effectif_total' => 'integer',
        'nombre_filles' => 'integer',
        'nombre_garcons' => 'integer',
        'total_frais_scolarite' => 'decimal:2',
        'total_scolarite_paye' => 'decimal:2',
        'total_scolarite_non_paye' => 'decimal:2',
        'total_achats_depenses' => 'decimal:2',
        'total_salaires' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function anneeScolaire()
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\AnneeScolaire::class);
    }

    public function classe()
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Classe::class);
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
