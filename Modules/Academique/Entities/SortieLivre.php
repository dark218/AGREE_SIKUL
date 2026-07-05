<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SortieLivre extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'sorties_livres';

    protected $fillable = [
        'bibliotheque_id',
        'bibliotheque_structure_id',
        'type_sortie',
        'date_sortie',
        'quantite',
        'date_retour',
        'tiers',
        'etat_physique',
        'etat',
        'creation_username',
        'modification_username',
    ];

    protected $casts = [
        'date_sortie' => 'date',
        'date_retour' => 'date',
        'quantite' => 'integer',
    ];

    public function livre(): BelongsTo
    {
        return $this->belongsTo(Bibliotheque::class, 'bibliotheque_id');
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(BibliothequeStructure::class, 'bibliotheque_structure_id');
    }
}
