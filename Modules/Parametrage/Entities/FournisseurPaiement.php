<?php

namespace Modules\Parametrage\Entities;

use Database\Factories\FournisseurPaiementFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class FournisseurPaiement extends BaseModel
{
    use HasFactory;
    
    // Constantes pour le type de fournisseur de paiement
    public const TYPE_MOBILE_MONEY = 'mm';
    public const TYPE_BANQUE = 'bank';
    public const TYPE_MICROFINANCE = 'microfinance';
    public const TYPE_AGGREGATEUR = 'aggregator';
    public const TYPE_CARTE = 'card';
    
    // Constantes pour le statut
    public const STATUT_ACTIF = 'actif';
    public const STATUT_INACTIF = 'inactif';
    
    protected $table = 'fournisseurs_paiement';

    protected $fillable = [
        'code',
        'libelle',
        'etat',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'settlement_config' => 'array', // stocké en clair (si sensible -> chiffrer)
    ];

    protected static function newFactory()
    {
        return FournisseurPaiementFactory::new();
    }

//    public function moyens(): HasMany
//    {
//        return $this->hasMany(MoyenPaiement::class, 'fournisseur_id');
//    }

    public function paysDevise(): BelongsTo
    {
        return $this->belongsTo(PaysDevise::class, 'pays_devise_id');
    }
    public function logo(): BelongsTo
    {
        return $this->belongsTo(Fichier::class, 'logo_id');
    }


    /**
     * Mutator : on chiffre en base le json de config
     * On accepte array|string|null
     */
    public function setConfigAttribute($value): void
    {
        if (is_null($value) || $value === '') {
            $this->attributes['config'] = null;
            return;
        }

        // normalize to JSON string then encrypt
        $json = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        $this->attributes['config'] = Crypt::encryptString($json);
    }
    /**
     * Accessor : retourne le config déchiffré (array) ou null
     */
    public function getConfigAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            $json = Crypt::decryptString($value);
            $decoded = json_decode($json, true);
            return is_array($decoded) ? $decoded : $json;
        } catch (\Throwable $e) {
            // En cas d'erreur, retourne la valeur brute masquée pour debug minimal
            return null;
        }
    }

    /**
     * Helper : retourne une version masquée / safe du config pour UI (ex: nonce/token seulement)
     */
    public function getConfigMaskedAttribute()
    {
        $cfg = $this->config;
        if (!$cfg || !is_array($cfg)) {
            return null;
        }

        $masked = $cfg;
        foreach ($masked as $k => $v) {
            // Masque les valeurs sensibles
            if (in_array(strtolower($k), ['client_secret', 'secret', 'password', 'api_key', 'private_key', 'token'])) {
                $masked[$k] = '****' . substr($v, -4);
            }
        }

        return $masked;
    }

    public function toggleActive()
    {
        $this->statut = $this->statut === 'actif' ? 'inactif' : 'actif';
        $this->save();
        return $this;
    }
}
