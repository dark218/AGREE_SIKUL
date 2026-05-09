<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AffectationEnseignant extends BaseModel
{
    use HasFactory;

    protected $table = 'affectations_enseignants';

    protected $fillable = [
        'annee_scolaire_id',
        'enseignant_id',
        'classe_id',
        'ecole_id',
        'institution_id',
        'campus_id',
        'matiere_1_id',
        'matiere_2_id',
        'matiere_3_id',
        'matiere_4_id',
        'matiere_5_id',
        'matiere_6_id',
        'matiere_7_id',
        'matiere_8_id',
        'matiere_9_id',
        'matiere_10_id',
        'matiere_11_id',
        'matiere_12_id',
        'matiere_13_id',
        'matiere_14_id',
        'matiere_15_id',
        'matiere_16_id',
        'matiere_17_id',
        'matiere_18_id',
        'matiere_19_id',
        'matiere_20_id',
        'matiere_21_id',
        'etat',
        'creation_username',
        'modification_username',
    ];

    protected $casts = [
        'etat' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function anneeScolaire()
    {
        return $this->belongsTo('Modules\Parametrage\Entities\AnneeScolaire', 'annee_scolaire_id');
    }

    public function enseignant()
    {
        return $this->belongsTo('Modules\Academique\Entities\Enseignant', 'enseignant_id');
    }

    public function classe()
    {
        return $this->belongsTo('Modules\Parametrage\Entities\Classe', 'classe_id');
    }

    public function ecole()
    {
        return $this->belongsTo('Modules\Parametrage\Entities\Ecole', 'ecole_id');
    }

    public function institution()
    {
        return $this->belongsTo('Modules\Parametrage\Entities\Institution', 'institution_id');
    }

    public function campus()
    {
        return $this->belongsTo('Modules\Parametrage\Entities\Campus', 'campus_id');
    }

    // Matière relationships
    public function matiere1()
    {
        return $this->belongsTo('Modules\Academique\Entities\Matiere', 'matiere_1_id');
    }

    public function matiere2()
    {
        return $this->belongsTo('Modules\Academique\Entities\Matiere', 'matiere_2_id');
    }

    public function matiere3()
    {
        return $this->belongsTo('Modules\Academique\Entities\Matiere', 'matiere_3_id');
    }

    public function matiere4()
    {
        return $this->belongsTo('Modules\Academique\Entities\Matiere', 'matiere_4_id');
    }

    public function matiere5()
    {
        return $this->belongsTo('Modules\Academique\Entities\Matiere', 'matiere_5_id');
    }

    public function matiere6()
    {
        return $this->belongsTo('Modules\Academique\Entities\Matiere', 'matiere_6_id');
    }

    public function matiere7()
    {
        return $this->belongsTo('Modules\Academique\Entities\Matiere', 'matiere_7_id');
    }

    public function matiere8()
    {
        return $this->belongsTo('Modules\Academique\Entities\Matiere', 'matiere_8_id');
    }

    public function matiere9()
    {
        return $this->belongsTo('Modules\Academique\Entities\Matiere', 'matiere_9_id');
    }

    public function matiere10()
    {
        return $this->belongsTo('Modules\Academique\Entities\Matiere', 'matiere_10_id');
    }

    public function matiere11()
    {
        return $this->belongsTo('Modules\Academique\Entities\Matiere', 'matiere_11_id');
    }

    public function matiere12()
    {
        return $this->belongsTo('Modules\Academique\Entities\Matiere', 'matiere_12_id');
    }

    public function matiere13()
    {
        return $this->belongsTo('Modules\Academique\Entities\Matiere', 'matiere_13_id');
    }

    public function matiere14()
    {
        return $this->belongsTo('Modules\Academique\Entities\Matiere', 'matiere_14_id');
    }

    public function matiere15()
    {
        return $this->belongsTo('Modules\Academique\Entities\Matiere', 'matiere_15_id');
    }

    public function matiere16()
    {
        return $this->belongsTo('Modules\Academique\Entities\Matiere', 'matiere_16_id');
    }

    public function matiere17()
    {
        return $this->belongsTo('Modules\Academique\Entities\Matiere', 'matiere_17_id');
    }

    public function matiere18()
    {
        return $this->belongsTo('Modules\Academique\Entities\Matiere', 'matiere_18_id');
    }

    public function matiere19()
    {
        return $this->belongsTo('Modules\Academique\Entities\Matiere', 'matiere_19_id');
    }

    public function matiere20()
    {
        return $this->belongsTo('Modules\Academique\Entities\Matiere', 'matiere_20_id');
    }

    public function matiere21()
    {
        return $this->belongsTo('Modules\Academique\Entities\Matiere', 'matiere_21_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    // Helper methods
    public function isActif(): bool
    {
        return $this->etat === 'actif';
    }

    public function countMatieres(): int
    {
        $count = 0;
        for ($i = 1; $i <= 21; $i++) {
            $field = 'matiere_' . $i . '_id';
            if ($this->$field !== null) {
                $count++;
            }
        }
        return $count;
    }
}
