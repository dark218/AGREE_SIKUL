<?php

namespace Modules\Wallet\Entities;

use App\Models\BaseModel;
use App\Models\User;
use App\Services\MoneyService;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Business\Entities\Marchand;
use Modules\Business\Entities\PointVente;
use Modules\Parametrage\Entities\FournisseurPaiement;

class Transactions extends BaseModel
{
    use HasFactory, SoftDeletes;
    // Types de source
    public const SOURCE_VENTE_POS = 'vente_pos';
    public const SOURCE_REMBOURSEMENT = 'remboursement';
    public const SOURCE_LIEN = 'lien';
    public const SOURCE_ABONNEMENT = 'abonnement';

    // Statuts de transaction
    public const STATUT_INITIEE = 'initiee';
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_REUSSIE = 'reussie';
    public const STATUT_ECHOUEE = 'echouee';
    public const STATUT_ANNULEE = 'annulee';
    public const STATUT_REMBOURSEE = 'remboursee';

    // Liste des types de source valides
    public static array $sourceTypes = [
        self::SOURCE_VENTE_POS,
        self::SOURCE_REMBOURSEMENT,
        self::SOURCE_LIEN,
        self::SOURCE_ABONNEMENT,
    ];

    // Liste des statuts valides
    public static array $statuts = [
        self::STATUT_INITIEE,
        self::STATUT_EN_ATTENTE,
        self::STATUT_REUSSIE,
        self::STATUT_ECHOUEE,
        self::STATUT_ANNULEE,
        self::STATUT_REMBOURSEE,
    ];

    protected $table = 'transactions';

    protected $fillable = [
        'uuid',
        'payer_id',
        'marchand_id',
        'points_vente_id',
        'source_type',
        'source_id',
        'fournisseur_paiement_id',
        'montant_cents',
        'devise',
        'statut',
        'reference_externe',
        'meta_json',
        'initiated_at',
        'confirmed_at',
        'failed_at',
    ];

    protected $casts = [
        'montant_cents' => 'integer',
        'devise' => 'string',
        'meta_json' => 'array',
        'initiated_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'failed_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'statut' => self::STATUT_INITIEE,
    ];

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function marchand(): BelongsTo
    {
        return $this->belongsTo(Marchand::class, 'marchand_id');
    }

    public function pointVente(): BelongsTo
    {
        return $this->belongsTo(PointVente::class, 'points_vente_id');
    }

    public function fournisseurPaiement(): BelongsTo
    {
        return $this->belongsTo(FournisseurPaiement::class, 'fournisseur_paiement_id');
    }

    public function getMontantAttribute(): string
    {
        return MoneyService::toDisplay($this->montant_cents, $this->devise);
    }

    public function setMontantAttribute($value): void
    {
        if (is_numeric($value)) {
            $this->attributes['montant_cents'] = (int) round($value * 100);
        } else {
            $this->attributes['montant_cents'] = MoneyService::toDatabase($value, $this->devise);
        }
    }

    public function scopeInitiees($query)
    {
        return $query->where('statut', self::STATUT_INITIEE);
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut', self::STATUT_EN_ATTENTE);
    }

    public function scopeReussies($query)
    {
        return $query->where('statut', self::STATUT_REUSSIE);
    }

    public function scopeEchouees($query)
    {
        return $query->where('statut', self::STATUT_ECHOUEE);
    }

    public function scopeAnnulees($query)
    {
        return $query->where('statut', self::STATUT_ANNULEE);
    }

    public function scopeRemboursees($query)
    {
        return $query->where('statut', self::STATUT_REMBOURSEE);
    }

    public static function booted()
    {
        static::saving(function ($transaction) {
            // Logique de sauvegarde si nécessaire
        });
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Database\Factories\TransactionFactory
     */
    protected static function newFactory()
    {
        return TransactionFactory::new();
    }

    public function markAsInitiee(): void
    {
        $this->update([
            'statut' => self::STATUT_INITIEE,
            'initiated_at' => now(),
        ]);
    }

    public function markAsEnAttente(): void
    {
        $this->update(['statut' => self::STATUT_EN_ATTENTE]);
    }

    public function markAsReussie(): void
    {
        $this->update([
            'statut' => self::STATUT_REUSSIE,
            'confirmed_at' => now(),
        ]);
    }

    public function markAsEchouee(): void
    {
        $this->update([
            'statut' => self::STATUT_ECHOUEE,
            'failed_at' => now(),
        ]);
    }

    public function markAsAnnulee(): void
    {
        $this->update(['statut' => self::STATUT_ANNULEE]);
    }

    public function markAsRemboursee(): void
    {
        $this->update(['statut' => self::STATUT_REMBOURSEE]);
    }
}
