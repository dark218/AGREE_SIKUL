<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * §UX Phase 2 : catégorie de participation d'un apprenant (Régulier,
 * Irrégulier, Libre, Auditeur, …). Concept distinct de :
 *   - TypeApprenant  (nouveau/redoublant/transfert — flux d'inscription)
 *   - StatutApprenant (actif/suspendu/exclu — état administratif)
 */
class CategorieApprenant extends BaseModel
{
    use SoftDeletes;

    protected $table = 'categorie_apprenants';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'etat',
    ];

    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }
}
