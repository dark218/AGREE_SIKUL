<?php

namespace Modules\Business\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Database\Factories\MarchandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\Fichier;

class Marchand extends BaseModel
{
    use  SoftDeletes,HasFactory;

    protected $table = 'marchands';

    protected $fillable = [
        'raison_sociale',
        'identifiant_fiscal',
        'type',
        'proprietaire_id',
        'rccm_id',
        'dfe_id',
        'validated_at',
        'validated_by',
        'create_by',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
    ];

    public const TYPE_INFORMEL = 'informel';
    public const TYPE_BOUTIQUE = 'boutique';
    public const TYPE_GRANDE_SURFACE = 'grande_surface';

    public static function getTypes(): array
    {
        return [
            self::TYPE_INFORMEL => 'Informel',
            self::TYPE_BOUTIQUE => 'Boutique',
            self::TYPE_GRANDE_SURFACE => 'Grande Surface',
        ];
    }

    public static function getTypesForSelect(): array
    {
        return [
            ['value' => self::TYPE_INFORMEL, 'label' => 'Informel'],
            ['value' => self::TYPE_BOUTIQUE, 'label' => 'Boutique'],
            ['value' => self::TYPE_GRANDE_SURFACE, 'label' => 'Grande Surface'],
        ];
    }

    public function getTypeLabel(): string
    {
        return self::getTypes()[$this->type] ?? 'Inconnu';
    }


    protected static function newFactory()
    {
        return MarchandFactory::new();
    }
    public function proprietaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proprietaire_id');
    }

    public function validatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_by');
    }

    public function rccm(): BelongsTo
    {
        return $this->belongsTo(Fichier::class, 'rccm_id');
    }

    public function dfe(): BelongsTo
    {
        return $this->belongsTo(Fichier::class, 'dfe_id');
    }

    public function pointsVente(): HasMany
    {
        return $this->hasMany(PointVente::class, 'marchand_id');
    }

    public function employes(): \Illuminate\Database\Eloquent\Builder|HasManyThrough|Marchand
    {
        return $this->hasManyThrough(
            Employe::class,
            PointVente::class,
            'marchand_id', // Clé étrangère sur points_vente
            'points_vente_id', // Clé étrangère sur employes
            'id', // Clé locale sur marchands
            'id' // Clé locale sur points_vente
        );
    }

    // Wallets du marchand
    public function wallets(): HasMany
    {
        return $this->hasMany(\Modules\Wallet\Entities\Wallet::class, 'owner_id')
                    ->where('owner_type', \Modules\Wallet\Entities\Wallet::OWNER_TYPE_MARCHAND);
    }
}
