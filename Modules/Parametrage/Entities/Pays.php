<?php

namespace Modules\Parametrage\Entities;

use Database\Factories\PaysFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pays extends BaseModel
{
    use HasFactory;

    protected $table = "pays";

    protected $appends = ['phone_code'];

    protected $fillable = [
        'code',
        'code_3_chars',
        'code_2_chars',
        'libelle',
        'capitale',
        'nombre',
        'continent',
        'etat',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function newFactory()
    {
        return PaysFactory::new();
    }

    public function paysDevises(): HasMany
    {
        return $this->hasMany(PaysDevise::class,'pays_id');
    }
//    public function qrDataUsers(): HasMany
//    {
//        return $this->hasMany(QrdataUsers::class);
//    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    public function scopeInactif($query)
    {
        return $query->where('etat', 'inactif');
    }

    public function fullPhoneNumber(string $phone): ?string
    {
        if($this->code && $phone){
            return $this->code.$phone;
        }
        return null;
    }

    // Mappage complet: code ISO 2 chars + libelle (nom du pays) vers codes téléphoniques
    protected static array $isoToPhone = [
        'CI' => '+225', 'FR' => '+33', 'US' => '+1', 'BE' => '+32', 'CH' => '+41',
        'CM' => '+237', 'SN' => '+221', 'BJ' => '+229', 'TG' => '+228', 'BF' => '+226',
        'ML' => '+223', 'NE' => '+227', 'GW' => '+245', 'ST' => '+239', 'CV' => '+238'
    ];

    protected static array $libelleToPhone = [
        'côte d\'ivoire' => '+225', 'cote d\'ivoire' => '+225', 'ivory coast' => '+225',
        'france' => '+33', 'usa' => '+1', 'belgium' => '+32', 'belgique' => '+32',
        'switzerland' => '+41', 'suisse' => '+41', 'cameroun' => '+237', 'cameroon' => '+237',
        'senegal' => '+221', 'sénégal' => '+221', 'benin' => '+229', 'bénin' => '+229',
        'togo' => '+228', 'burkina faso' => '+226', 'mali' => '+223', 'niger' => '+227',
        'guinea-bissau' => '+245', 'sao tome' => '+239', 'cape verde' => '+238'
    ];

    // Accesseur pour obtenir le code téléphonique
    public function getPhoneCodeAttribute(): string
    {
        // Si code commence déjà par '+', l'utiliser
        if ($this->code && str_starts_with($this->code, '+')) {
            return $this->code;
        }

        // Essayer avec code_2_chars d'abord
        if ($this->code_2_chars) {
            $phoneCode = self::$isoToPhone[strtoupper($this->code_2_chars)] ?? null;
            if ($phoneCode) return $phoneCode;
        }

        // Essayer avec le libelle (nom du pays) - convertir en minuscules
        if ($this->libelle) {
            $phoneCode = self::$libelleToPhone[strtolower($this->libelle)] ?? null;
            if ($phoneCode) return $phoneCode;
        }

        // Essayer avec code_3_chars
        if ($this->code_3_chars) {
            $phoneCode = self::$isoToPhone[strtoupper($this->code_3_chars)] ?? null;
            if ($phoneCode) return $phoneCode;
        }

        // Default fallback
        return '+225';
    }
}
