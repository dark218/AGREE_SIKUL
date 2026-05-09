<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Session extends Model
{
    use HasFactory;
    protected $table = 'sessions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'user_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
    ];

    // Casts automatiques
    protected $casts = [
        'last_activity' => 'integer',
    ];

    /* =========================
     |  Relations Eloquent
     ========================= */

    /**
     * Utilisateur propriétaire de la session
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /* =========================
     |  Scopes utiles (optionnels)
     ========================= */

    /**
     * Sessions actives (non expirées)
     */
    public function scopeActives($query, $minutes = 120)
    {
        return $query->where('last_activity', '>=', now()->subMinutes($minutes)->timestamp);
    }

    /**
     * Sessions d'un utilisateur précis
     */
    public function scopeParUtilisateur($query, $idUsers)
    {
        return $query->where('user_id', $idUsers);
    }
}
