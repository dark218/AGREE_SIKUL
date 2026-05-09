<?php

namespace Modules\Business\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Database\Factories\PointVenteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\Fichier;
use Modules\Parametrage\Entities\Zone;
use Modules\Pos\Entities\QrCode;

class PointVente extends BaseModel
{
    use SoftDeletes,HasFactory;

    protected $table = 'points_vente';

    protected $fillable = [
        'marchand_id',
        'nom',
        'adresse',
        'telephone',
        'longitude',
        'latitude',
        'param_pos_json',
        'photo_id',
        'zone_id',
        'statut',
        'motif',
        'validated_at',
        'validated_by',
        'blocked_by',
        'suspended_by',
        'create_by',
        'parent_points_vente_id',
    ];

    protected $casts = [
        'param_pos_json' => 'array',
        'validated_at' => 'datetime',
        'longitude' => 'double',
        'latitude' => 'double',
    ];

    protected static function newFactory()
    {
        return PointVenteFactory::new();
    }
    public function marchand(): BelongsTo
    {
        return $this->belongsTo(Marchand::class, 'marchand_id');
    }
    public function parentPointVente(): BelongsTo
    {
        return $this->belongsTo(PointVente::class, 'parent_points_vente_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Fichier::class, 'photo_id');
    }

    public function validatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_by');
    }

    public function employes(): HasMany
    {
        return $this->hasMany(Employe::class, 'points_vente_id');
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class, 'points_vente_id');
    }
    public function isPointDeVente(): bool
    {
        return $this->parent_points_vente_id === null;
    }

    public function isCaisse(): bool
    {
        return $this->parent_points_vente_id !== null
            && ($this->param_pos_json['type'] ?? null) === 'caisse';
    }
}
