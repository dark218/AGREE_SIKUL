<?php

namespace Modules\Wallet\Entities;

use App\Models\BaseModel;
use App\Models\User;
use App\Services\MoneyService;
use Database\Factories\WalletMouvementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Business\Entities\PointVente;

class WalletMouvement extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'wallet_id',
        'users_id',
        'type_mouvement',
        'montant_cents',
        'solde_avant_cents',
        'solde_apres_cents',
        'emplacement_id',
        'reference',
        'source_type',
        'source_id',
        'meta_json',
    ];

    protected $casts = [
        'meta_json' => 'array',
        'montant_cents' => 'integer',
        'solde_avant_cents' => 'integer',
        'solde_apres_cents' => 'integer',
    ];

    public const TYPE_CREDIT = 'credit';
    public const TYPE_DEBIT = 'debit';
    public const TYPE_BLOCAGE = 'blocage';
    public const TYPE_DEBLOCAGE = 'deblocage';
    public const TYPE_COMMISSION = 'commission';
    public const TYPE_REMBOURSEMENT = 'remboursement';
    public const TYPE_AJUSTEMENT = 'ajustement';

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    public function emplacement(): BelongsTo
    {
        return $this->belongsTo(PointVente::class, 'emplacement_id');
    }

    public function getMontantAttribute(): string
    {
        return MoneyService::toDisplay(
            $this->montant_cents,
            $this->wallet?->paysDevise?->devise?->code ?? 'XOF'
        );
    }

    public function estCredit(): bool
    {
        return $this->type_mouvement === self::TYPE_CREDIT;
    }

    public function estDebit(): bool
    {
        return $this->type_mouvement === self::TYPE_DEBIT;
    }

    public function estBlocage(): bool
    {
        return $this->type_mouvement === self::TYPE_BLOCAGE;
    }

    public function estDeblocage(): bool
    {
        return $this->type_mouvement === self::TYPE_DEBLOCAGE;
    }

    protected static function newFactory()
    {
        return WalletMouvementFactory::new();
    }
}
