<?php

namespace Modules\Pos\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Business\Entities\PointVente;
use Modules\Parametrage\Entities\Devises;

class QrCode extends BaseModel
{
    use SoftDeletes;

    protected $table = 'codes_qr';

    protected $fillable = [
        'points_vente_id',
        'type',
        'uuid',
        'payload_json',
        'montant_cents',
        'devise',
        'expire_at',
        'used',
        'actif',
    ];
    protected $casts = [
        'payload_json' => 'array',
        'used'         => 'boolean',
        'actif'        => 'boolean',
        'expire_at'    => 'datetime',
    ];
    public function pointsVente()
    {
        return $this->belongsTo(PointVente::class);
    }

    public function isStatique(): bool
    {
        return $this->type === 'statique';
    }

    public function isDynamique(): bool
    {
        return $this->type === 'dynamique';
    }

    public function isExpired(): bool
    {
        return $this->expire_at !== null && now()->greaterThan($this->expire_at);
    }

    public function canBeUsed(): bool
    {
        if (!$this->actif) {
            return false;
        }

        if ($this->isDynamique() && ($this->used || $this->isExpired())) {
            return false;
        }

        return true;
    }
}
