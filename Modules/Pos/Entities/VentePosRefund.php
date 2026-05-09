<?php

namespace Modules\Pos\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VentePosRefund extends Model
{

    protected $table = 'vente_pos_refunds';

    protected $fillable = [
        'vente_pos_id',
        'mode_paiement',
        'montant_cents',
        'motif',
        'refunded_by',
        'refunded_at',
    ];

    public function ventePos()
    {
        return $this->belongsTo(VentePos::class, 'vente_pos_id');
    }

    public function refundedBy()
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }
}
