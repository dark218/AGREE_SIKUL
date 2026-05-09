<?php

namespace Modules\Business\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Database\Factories\EmployeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Pos\Entities\SessionCaisse;

class Employe extends BaseModel
{
    use  SoftDeletes,HasFactory;

    // Types d'employé constants
    const TYPE_CAISSIER = 'caissier';
    const TYPE_MANAGER = 'manager';

    protected $table = 'employes';

    protected $fillable = [
        'points_vente_id',
        'users_id',
        'code_employe',
        'date_embauche',
        'type_employe',
        'shift_info',
        'validated_at',
        'validated_by',
        'create_by',
    ];

    protected $casts = [
        'shift_info' => 'array',
        'validated_at' => 'datetime',
        'date_embauche' => 'date',
    ];

    protected static function newFactory()
    {
        return EmployeFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    public function pointVente(): BelongsTo
    {
        return $this->belongsTo(PointVente::class, 'points_vente_id');
    }

    public function marchand(): BelongsTo
    {
        return $this->belongsTo(Marchand::class, 'marchand_id');
    }

    public function validatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_by');
    }
    public function sessionsCaisse()
    {
        return $this->hasMany(SessionCaisse::class, 'caissier_id');
    }

    /**
     * Obtenir les types d'employé disponibles
     *
     * @return array
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_CAISSIER => 'Caissier',
            self::TYPE_MANAGER => 'Manager',
        ];
    }

    /**
     * Obtenir le libellé du type d'employé
     *
     * @param string $type
     * @return string|null
     */
    public static function getLibelleType(string $type): ?string
    {
        return self::getTypes()[$type] ?? null;
    }
}
