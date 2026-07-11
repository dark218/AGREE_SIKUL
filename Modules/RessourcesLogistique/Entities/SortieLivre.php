<?php

namespace Modules\RessourcesLogistique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sortie de livres (prêt / vente / don) d'une bibliothèque.
 * Livre référencé via `ouvrage_id` → infos livre en auto (anti-redondance).
 */
class SortieLivre extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'sorties_livres';

    protected $fillable = [
        'bibliotheque_structure_id',
        'ouvrage_id',
        'type_sortie',
        'date_sortie',
        'quantite',
        'date_retour',
        'tiers',
        'etat_physique',
        'etat',
    ];

    protected $casts = [
        'date_sortie' => 'date',
        'date_retour' => 'date',
        'quantite'    => 'integer',
    ];

    public function bibliothequeStructure(): BelongsTo
    {
        return $this->belongsTo(BibliothequeStructure::class, 'bibliotheque_structure_id');
    }

    public function ouvrage(): BelongsTo
    {
        return $this->belongsTo(Ouvrage::class, 'ouvrage_id');
    }
}
