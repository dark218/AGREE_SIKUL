<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLoginLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_id',
        'device_type',
        'ip_address',
        'user_agent',
        'login_at',
    ];

    protected $casts = [
        'login_at' => 'datetime',
    ];

    /**
     * Obtenir l'utilisateur associé à ce log
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtenir les logs pour un utilisateur spécifique
     */
    public static function forUser(int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return static::where('user_id', $userId);
    }

    /**
     * Obtenir les logs pour un appareil spécifique
     */
    public static function forDevice(string $deviceId): \Illuminate\Database\Eloquent\Builder
    {
        return static::where('device_id', $deviceId);
    }

    /**
     * Obtenir les logs récents (derniers 30 jours)
     */
    public static function recent(): \Illuminate\Database\Eloquent\Builder
    {
        return static::where('login_at', '>=', now()->subDays(30));
    }

    /**
     * Obtenir les logs par type d'appareil
     */
    public static function byDeviceType(string $deviceType): \Illuminate\Database\Eloquent\Builder
    {
        return static::where('device_type', $deviceType);
    }

    /**
     * Vérifier si c'est une connexion mobile
     */
    public function isMobile(): bool
    {
        return $this->device_type === 'mobile';
    }

    /**
     * Vérifier si c'est une connexion web
     */
    public function isWeb(): bool
    {
        return $this->device_type === 'web';
    }

    /**
     * Obtenir l'adresse IP formatée
     */
    public function getFormattedIpAddress(): string
    {
        return long2ip($this->ip_address);
    }

    /**
     * Scope pour les connexions aujourd'hui
     */
    public function scopeToday($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereDate('login_at', today());
    }

    /**
     * Scope pour les connexions cette semaine
     */
    public function scopeThisWeek($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereBetween('login_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    /**
     * Scope pour les connexions ce mois-ci
     */
    public function scopeThisMonth($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereMonth('login_at', now()->month)
                    ->whereYear('login_at', now()->year);
    }
}
