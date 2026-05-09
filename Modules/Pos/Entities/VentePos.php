<?php

namespace Modules\Pos\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Database\Factories\VentePosFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Business\Entities\Employe;
use Modules\Business\Entities\PointVente;

class VentePos extends BaseModel
{
    use SoftDeletes,HasFactory;

    protected $table = 'ventes_pos';

    protected $fillable = [
        'uuid',
        'points_vente_id',
        'sessions_caisse_id',
        'employe_id',
        'devise',
        'total_cents',
        'montant_espece_cents',
        'montant_non_espece_cents',
        'paid_at',
        'mode_paiement',
        'statut',
        'reference',
        'meta_json',
        'cancelled_at',
        'cancelled_by',
        'cancel_motif',
        'refund_at',
        'refund_by',
        'refund_motif',
        'refund_cents',
        'total_rembourse_cents',
        'rembourse_espece_cents',
        'rembourse_non_espece_cents',
        'rembourse_libre_cents',
        'motif_remboursement',
    ];

    protected $casts = [
        'meta_json' => 'array',
    ];

    protected static function newFactory()
    {
        return VentePosFactory::new();
    }
    public function lignes(): HasMany
    {
        return $this->hasMany(VentePosLigne::class, 'ventes_pos_id');
    }
    public function pointsVente(): BelongsTo
    {
        return $this->belongsTo(PointVente::class, 'points_vente_id');
    }
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
    public function refundBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refund_by');
    }
    public function sessionCaisse(): BelongsTo
    {
        return $this->belongsTo(SessionCaisse::class, 'sessions_caisse_id');
    }
    public function employe(): BelongsTo
    {
        return $this->belongsTo(Employe::class, 'employe_id');
    }
    public function isPayee(): bool
    {
        return $this->statut === 'payee';
    }

    public function isEnAttente(): bool
    {
        return $this->statut === 'en_attente';
    }
}
