<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Parametrage\Entities\Section;
use Modules\Parametrage\Entities\CycleEnseignement;
use Modules\Parametrage\Entities\Niveau;

class Passage extends BaseModel
{
    use HasFactory;

    protected $table = 'passages';

    protected $fillable = [
        'section_id',
        'cycle_enseignement_id',
        'niveau_id',
        'niveau_superieur_id',
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
    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function cycleEnseignement()
    {
        return $this->belongsTo(CycleEnseignement::class, 'cycle_enseignement_id');
    }

    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    public function niveauSuperieur()
    {
        return $this->belongsTo(Niveau::class, 'niveau_superieur_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    public function isActif(): bool
    {
        return $this->etat === 'actif';
    }
}
