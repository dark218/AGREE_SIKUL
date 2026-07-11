<?php

namespace Modules\RessourcesLogistique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Entrée de livres (emprunt / achat / don) dans une bibliothèque.
 * Le livre est référencé via `ouvrage_id` (catalogue) → titre/auteur/… remontent
 * automatiquement, pas de re-saisie.
 */
class EntreeLivre extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'entrees_livres';

    protected $fillable = [
        'bibliotheque_structure_id',
        'ouvrage_id',
        'type_entree',
        'date_entree',
        'quantite',
        'date_retour',
        'tiers',
        'etat_physique',
        'etat',
    ];

    protected $casts = [
        'date_entree' => 'date',
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
