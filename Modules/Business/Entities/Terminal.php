<?php

namespace Modules\Business\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Terminal extends BaseModel
{

    protected $table = 'terminaux';

    protected $fillable = [
        'uuid',
        'type_terminal',
        'fabricant',
        'modele',
        'numero_serie',
        'statut',
        'points_vente_id',
        'marchand_id',
        'version_firmware',
        'pki_cert_id',
        'last_checkin',
        'metadata',
        'deleted_by',
        'motif',
        'validated_at',
        'suspended_at',
        'retired_at',
        'validated_by',
        'suspended_by',
        'retired_by',
    ];

    protected $casts = [
        'last_checkin' => 'datetime',
        'metadata'     => 'array',
    ];
    public function pointsVente():BelongsTo
    {
        return $this->belongsTo(
            PointVente::class,
            'points_vente_id'
        );
    }
    public function marchand(): BelongsTo
    {
        return $this->belongsTo(
            Marchand::class,
            'marchand_id'
        );
    }
    public function deletedByUser()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function validatedByUser()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function suspendedByUser()
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }
    public function retiredByUser()
    {
        return $this->belongsTo(User::class, 'retired_by');
    }
    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }

    public function isDeploye(): bool
    {
        return $this->statut === 'deploye';
    }

    public function isSuspendu(): bool
    {
        return $this->statut === 'suspendu';
    }

    public function isRetire(): bool
    {
        return $this->statut === 'retire';
    }

    /**
     * Met à jour le dernier check-in du terminal
     */
    public function touchCheckin(): void
    {
        $this->update([
            'last_checkin' => now(),
        ]);
    }

}
