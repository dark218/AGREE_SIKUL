<?php

namespace Modules\ServiceClient\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Database\Factories\MoyenPaiementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Modules\Parametrage\Entities\FournisseurPaiement;

class MoyenPaiement extends BaseModel
{
    use HasFactory;

    // Constantes pour le type de moyen de paiement
    public const TYPE_MOBILE_MONEY = 'mm';
    public const TYPE_IBAN = 'iban';
    public const TYPE_CARD = 'card';
    public const TYPE_WALLET = 'wallet';

    // Constantes pour le statut
    public const STATUT_ACTIF = 'actif';
    public const STATUT_DESACTIVE = 'desactive';

    protected $table = 'moyen_paiement';

    protected $fillable = [
        'users_id',
        'fournisseur_id',
        'type',
        'label',
        'identifiant_chiffre',
        'token_provider',
        'is_defaut',
        'statut',
        'metadata',
    ];

    protected $casts = [
        'is_defaut' => 'boolean',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'identifiant_chiffre',
        'token_provider',
    ];

    protected static function newFactory()
    {
        return MoyenPaiementFactory::new();
    }

    /**
     * Relation avec l'utilisateur propriétaire
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    /**
     * Relation avec le fournisseur de paiement
     */
    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(FournisseurPaiement::class, 'fournisseur_id');
    }

    /**
     * Mutator : chiffre l'identifiant sensible avant stockage
     */
    public function setIdentifiantChiffreAttribute($value): void
    {
        if (is_null($value) || $value === '') {
            $this->attributes['identifiant_chiffre'] = null;
            return;
        }

        $this->attributes['identifiant_chiffre'] = Crypt::encryptString($value);
    }

    /**
     * Accessor : retourne l'identifiant déchiffré
     */
    public function getIdentifiantChiffreAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            // En cas d'erreur de déchiffrement, retourne null
            return null;
        }
    }

    /**
     * Mutator : chiffre le token provider avant stockage
     */
    public function setTokenProviderAttribute($value): void
    {
        if (is_null($value) || $value === '') {
            $this->attributes['token_provider'] = null;
            return;
        }

        $this->attributes['token_provider'] = Crypt::encryptString($value);
    }

    /**
     * Accessor : retourne le token provider déchiffré
     */
    public function getTokenProviderAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Retourne une version masquée de l'identifiant pour l'affichage
     */
    public function getIdentifiantMasqueAttribute(): string
    {
        $identifiant = $this->identifiant_chiffre;

        if (!$identifiant) {
            return '';
        }

        // Masquage selon le type
        switch ($this->type) {
            case self::TYPE_MOBILE_MONEY:
                // Format: 0701****1234
                if (strlen($identifiant) >= 8) {
                    return substr($identifiant, 0, 4) . '****' . substr($identifiant, -4);
                }
                break;

            case self::TYPE_IBAN:
                // Format: ****1234
                if (strlen($identifiant) >= 4) {
                    return '****' . substr($identifiant, -4);
                }
                break;

            case self::TYPE_CARD:
                // Format: ****-****-****-1234
                if (strlen($identifiant) >= 16) {
                    return '****-****-****-' . substr($identifiant, -4);
                }
                break;

            case self::TYPE_WALLET:
                // Format: WAL****1234
                if (strlen($identifiant) >= 8) {
                    return substr($identifiant, 0, 3) . '****' . substr($identifiant, -4);
                }
                break;
        }

        return '****' . substr($identifiant, -4);
    }

    /**
     * Scope pour les moyens de paiement actifs
     */
    public function scopeActif($query)
    {
        return $query->where('statut', self::STATUT_ACTIF);
    }

    /**
     * Scope pour les moyens de paiement par défaut
     */
    public function scopeDefaut($query)
    {
        return $query->where('is_defaut', true);
    }

    /**
     * Scope pour les moyens de paiement par type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope pour les moyens de paiement par fournisseur
     */
    public function scopeByFournisseur($query, int $fournisseurId)
    {
        return $query->where('fournisseur_id', $fournisseurId);
    }

    /**
     * Définit ce moyen de paiement comme par défaut
     * et désactive les autres pour le même utilisateur
     */
    public function setAsDefault(): bool
    {
        // Désactiver les autres moyens par défaut de l'utilisateur
        static::where('users_id', $this->users_id)
            ->where('id', '!=', $this->id)
            ->update(['is_defaut' => false]);

        // Activer celui-ci
        $this->is_defaut = true;
        return $this->save();
    }

    /**
     * Active/Désactive le moyen de paiement
     */
    public function toggleStatut(): bool
    {
        $this->statut = $this->statut === self::STATUT_ACTIF ? self::STATUT_DESACTIVE : self::STATUT_ACTIF;
        return $this->save();
    }

    /**
     * Vérifie si le moyen de paiement est utilisable
     */
    public function isUtilisable(): bool
    {
        return $this->statut === self::STATUT_ACTIF;
    }

    /**
     * Retourne le libellé formaté pour l'affichage
     */
    public function getLibelleCompletAttribute(): string
    {
        $fournisseurNom = $this->fournisseur?->nom ?? 'Inconnu';
        $identifiantMasque = $this->identifiant_masque;

        return "{$fournisseurNom} - {$identifiantMasque}";
    }

    /**
     * Obtenir le libellé du type de moyen de paiement
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_MOBILE_MONEY => 'Mobile Money',
            self::TYPE_IBAN => 'IBAN',
            self::TYPE_CARD => 'Carte',
            self::TYPE_WALLET => 'Wallet',
            default => 'Inconnu',
        };
    }

    /**
     * Obtenir le libellé du statut
     */
    public function getStatutLabel(): string
    {
        return match($this->statut) {
            self::STATUT_ACTIF => 'Actif',
            self::STATUT_DESACTIVE => 'Désactivé',
            default => 'Inconnu',
        };
    }

    /**
     * Boot du modèle
     */
    protected static function boot()
    {
        parent::boot();

        // Avant création : s'assurer qu'il n'y a qu'un seul moyen par défaut
        static::creating(function ($model) {
            if ($model->is_defaut) {
                static::where('users_id', $model->users_id)
                    ->update(['is_defaut' => false]);
            }
        });
        // Avant mise à jour : gérer le changement de moyen par défaut
        static::updating(function ($model) {
            if ($model->isDirty('is_defaut') && $model->is_defaut) {
                static::where('users_id', $model->users_id)
                    ->where('id', '!=', $model->id)
                    ->update(['is_defaut' => false]);
            }
        });
    }
}
