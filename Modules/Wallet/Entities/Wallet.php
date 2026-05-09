<?php

namespace Modules\Wallet\Entities;

use App\Models\BaseModel;
use App\Models\User;
use App\Services\MoneyService;
use Database\Factories\WalletFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Business\Entities\Marchand;
use Modules\Parametrage\Entities\PaysDevise;
use Modules\Personnel\Entities\Agent;

class Wallet extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'owner_type',
        'pays_devise_id',
        'solde_cents',
        'solde_bloque_cents',
        'solde_commission_cents',
        'solde_attente_cents',
        'statut',
        'meta_json',
    ];

    protected $casts = [
        'meta_json' => 'array',
        'solde_cents' => 'integer',
        'solde_bloque_cents' => 'integer',
        'solde_commission_cents' => 'integer',
        'solde_attente_cents' => 'integer',
    ];

    public const OWNER_TYPE_UTILISATEUR = 'utilisateur';
    public const OWNER_TYPE_MARCHAND = 'marchand';
    public const OWNER_TYPE_AGENT = 'agent';
    public const OWNER_TYPE_PLATEFORME = 'plateforme';
    public const OWNER_TYPE_CLIENT = 'client';

    public const STATUT_ACTIF = 'actif';
    public const STATUT_SUSPENDU = 'suspendu';
    public const STATUT_FERME = 'ferme';

    public function owner()
    {
        return match ($this->owner_type) {
            self::OWNER_TYPE_CLIENT =>
            $this->belongsTo(User::class, 'owner_id'),

            self::OWNER_TYPE_MARCHAND =>
            $this->belongsTo(Marchand::class, 'owner_id'),

            self::OWNER_TYPE_AGENT =>
            $this->belongsTo(User::class, 'owner_id'),

            self::OWNER_TYPE_UTILISATEUR =>
            $this->belongsTo(User::class, 'owner_id'),

            default => $this->belongsTo(User::class, 'owner_id')->whereNull('id'),
        };
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'owner_id')
                    ->where('role', 'agent');
    }

    public function paysDevise(): BelongsTo
    {
        return $this->belongsTo(PaysDevise::class);
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(WalletMouvement::class);
    }

    public function getSoldeAttribute(): string
    {
        return MoneyService::toDisplay($this->solde_cents, $this->paysDevise->devise->code ?? 'XOF');
    }

    public function getSoldeBloqueAttribute(): string
    {
        return MoneyService::toDisplay(
            $this->solde_bloque_cents,
            $this->paysDevise?->devise?->code ?? 'XOF'
        );
    }

    public function getSoldeCommissionAttribute(): string
    {
        return MoneyService::toDisplay(
            $this->solde_commission_cents,
            $this->paysDevise?->devise?->code ?? 'XOF'
        );
    }

    public function getSoldeAttenteAttribute(): string
    {
        return MoneyService::toDisplay(
            $this->solde_attente_cents,
            $this->paysDevise?->devise?->code ?? 'XOF'
        );
    }

    public function getStatutLabel(): string
    {
        return match ($this->statut) {
            self::STATUT_ACTIF => 'Actif',
            self::STATUT_SUSPENDU => 'Suspendu',
            self::STATUT_FERME => 'Fermé',
            default => 'Inconnu',
        };
    }


    public function estActif(): bool
    {
        return $this->statut === self::STATUT_ACTIF;
    }

    public function estSuspendu(): bool
    {
        return $this->statut === self::STATUT_SUSPENDU;
    }

    public function estFerme(): bool
    {
        return $this->statut === self::STATUT_FERME;
    }

    public static function peutAvoirWallet(string $role): bool
    {
        return in_array($role, ['marchand', 'client', 'agent']);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($wallet) {
            if ($wallet->solde_cents < 0 ||
                $wallet->solde_bloque_cents < 0 ||
                $wallet->solde_commission_cents < 0 ||
                $wallet->solde_attente_cents < 0) {
                throw new \Exception("Les soldes ne peuvent pas être négatifs");
            }
        });
    }

    protected static function newFactory()
    {
        return WalletFactory::new();
    }
}
