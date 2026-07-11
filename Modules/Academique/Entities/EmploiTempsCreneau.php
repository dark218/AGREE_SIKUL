<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\MatiereUnite;

/**
 * Un créneau d'emploi du temps : jour × heure → matière + enseignant (+ salle).
 * Rattaché à un cadre EmploiTemps.
 */
class EmploiTempsCreneau extends BaseModel
{
    use SoftDeletes;

    protected $table = 'emploi_temps_creneaux';

    protected $fillable = [
        'emploi_temps_id',
        'jour',
        'heure_debut',
        'heure_fin',
        'matiere_id',
        'enseignant_id',
        'salle',
        'ordre',
    ];

    protected $defaultOrderBy = 'ordre';
    protected $defaultOrderDir = 'asc';

    public function emploiTemps(): BelongsTo
    {
        return $this->belongsTo(EmploiTemps::class, 'emploi_temps_id');
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(MatiereUnite::class, 'matiere_id');
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class, 'enseignant_id');
    }
}
