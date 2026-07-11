<?php

namespace Modules\RessourcesLogistique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\Campus;

/**
 * Bibliothèque (lieu) — sous-fonctionnalité "Liste" du menu Bibliothèque.
 * Champs (spec) : Code, Libellé, Localisation, Campus, Responsable, Statut.
 */
class BibliothequeStructure extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'bibliotheque_structures';

    protected $fillable = [
        'code',
        'libelle',
        'localisation',
        'campus_id',
        'responsable',
        'statut_disponibilite',
        'etat',
    ];

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'campus_id');
    }
}
