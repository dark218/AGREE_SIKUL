<?php

namespace Modules\GestionStock\Entities;

use App\Models\BaseModel;
use Database\Factories\TransfertStockFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Business\Entities\Employe;
use Modules\Business\Entities\PointVente;

class TransfertStock extends BaseModel
{
    use SoftDeletes, HasFactory;

    protected $table = 'transferts_stock';

    /**
     * =========================
     * Constantes métier
     * =========================
     */
    protected static function newFactory()
    {
        return TransfertStockFactory::new();
    }
    public const STATUT_EN_COURS  = 'en_cours';
    public const STATUT_PARTIEL   = 'partiel';
    public const STATUT_CONFIRME  = 'confirme';
    public const STATUT_RECEPTIONNE  = 'receptionne';
    public const STATUT_ANNULE    = 'annule';

    /**
     * =========================
     * Champs modifiables
     * =========================
     */
    protected $fillable = [
        'reference',
        'emplacement_source_id',
        'emplacement_destination_id',
        'statut',
        'date_demande',
        'date_confirmation',
        'demande_par',
        'confirme_par',
        'recu_par',
        'commentaire',
    ];

    protected $casts = [
        'date_demande' => 'date',
        'date_confirmation' => 'date',
    ];

    public function lignes(): HasMany
    {
        return $this->hasMany(
            TransfertStockLigne::class,
            'transfert_stock_id'
        );
    }
    public function emplacementSource(): BelongsTo
    {
        return $this->belongsTo(
            PointVente::class,
            'emplacement_source_id'
        );
    }

    public function emplacementDestination(): BelongsTo
    {
        return $this->belongsTo(
            PointVente::class,
            'emplacement_destination_id'
        );
    }

    public function demandePar(): BelongsTo
    {
        return $this->belongsTo(
            Employe::class,
            'demande_par'
        );
    }

    public function confirmePar(): BelongsTo
    {
        return $this->belongsTo(
            Employe::class,
            'confirme_par'
        );
    }
    public function recuPar(): BelongsTo
    {
        return $this->belongsTo(
            Employe::class,
            'recu_par'
        );
    }


    /**
     * =========================
     * Helpers métier
     * =========================
     */

    public function isEnCours(): bool
    {
        return $this->statut === self::STATUT_EN_COURS;
    }

    public function isConfirme(): bool
    {
        return $this->statut === self::STATUT_CONFIRME;
    }

    public function isAnnule(): bool
    {
        return $this->statut === self::STATUT_ANNULE;
    }

}
