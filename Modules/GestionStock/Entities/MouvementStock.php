<?php

namespace Modules\GestionStock\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Business\Entities\Employe;
use Modules\Business\Entities\PointVente;

class MouvementStock extends BaseModel
{
    use SoftDeletes;
    protected $table = 'mouvements_stock';

    protected $fillable = [
        'article_id',
        'type_mouvement',
        'quantite',
        'quantite_stock_avant',
        'quantite_stock_apres',
        'emplacement_source_id',
        'emplacement_destination_id',
        'employe_id',
        'reference',
        'commentaire',
    ];

    /**
     * =========================
     * Constantes métier
     * =========================
     */
    public const TYPE_VENTE          = 'vente';
    public const TYPE_REMBOURSEMENT  = 'remboursement';
    public const TYPE_ENTREE_STOCK   = 'entree_stock';
    public const TYPE_SORTIE_STOCK   = 'sortie_stock';
    public const TYPE_TRANSFERT      = 'transfert';
    public const TYPE_INVENTAIRE     = 'inventaire';
    public const TYPE_CORRECTION     = 'correction';
    /**
     * =========================
     * Casts
     * =========================
     */
    protected $casts = [
        'quantite' => 'integer',
        'quantite_stock_avant' => 'integer',
        'quantite_stock_apres' => 'integer',
    ];
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }
    public function emplacementSource(): BelongsTo
    {
        return $this->belongsTo(PointVente::class, 'emplacement_source_id');
    }

    public function emplacementDestination(): BelongsTo
    {
        return $this->belongsTo(PointVente::class, 'emplacement_destination_id');
    }

    public function employe(): BelongsTo
    {
        return $this->belongsTo(Employe::class, 'employe_id');
    }
    public function isEntree(): bool
    {
        return $this->quantite > 0;
    }
    public function isSortie(): bool
    {
        return $this->quantite < 0;
    }

}
