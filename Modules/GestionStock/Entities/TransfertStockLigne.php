<?php

namespace Modules\GestionStock\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransfertStockLigne extends BaseModel
{
    use SoftDeletes;

    protected $table = 'transferts_stock_lignes';

    /**
     * =========================
     * Constantes métier
     * =========================
     */
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_PARTIEL    = 'partiel';
    public const STATUT_TRANSFERE  = 'transfere';
    public const STATUT_ANNULE     = 'annule';

    /**
     * =========================
     * Champs modifiables
     * =========================
     */
    protected $fillable = [
        'transfert_stock_id',
        'article_id',
        'quantite_demandee',
        'quantite_transferee',
        'stock_source_avant',
        'stock_source_apres',
        'stock_destination_avant',
        'stock_destination_apres',
        'statut',
        'commentaire',
    ];

    protected $casts = [
        'quantite_demandee' => 'integer',
        'quantite_transferee' => 'integer',
        'stock_source_avant' => 'integer',
        'stock_source_apres' => 'integer',
        'stock_destination_avant' => 'integer',
        'stock_destination_apres' => 'integer',
    ];

    /**
     * =========================
     * Relations
     * =========================
     */

    public function transfert(): BelongsTo
    {
        return $this->belongsTo(
            TransfertStock::class,
            'transfert_stock_id'
        );
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(
            Article::class,
            'article_id'
        );
    }

    /**
     * =========================
     * Helpers métier
     * =========================
     */

    public function isTransferee(): bool
    {
        return $this->statut === self::STATUT_TRANSFERE;
    }

    public function isPartielle(): bool
    {
        return $this->statut === self::STATUT_PARTIEL;
    }

    public function resteAtransferer(): int
    {
        return max(
            0,
            $this->quantite_demandee - $this->quantite_transferee
        );
    }
}
