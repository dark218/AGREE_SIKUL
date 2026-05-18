<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Traits\HasBaseModelFeatures;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;
    use SoftDeletes, HasBaseModelFeatures;

    protected $fillable = [
        "id",
        "nom",
        "prenoms",
        "login",
        "full_login",
        "uuid",
        "alias_smil",
        "statut",
        "metadata",
        "qr_data",
        "email",
        "code_owner",
        "code_parrain",
        "password",
        "fcm_token",
        "pays_id",
        "photoprofile_id",
        "piecerecto_id",
        "pieceverso_id",
        "users_creation_id",
        "numero_piece",
        "date_delivrance",
        "date_naissance",
        "lieu_delivrance",
        "lieu_naissance",
        "provider",
        "provider_id",
        "provider_token",
        "type_piece",
        "kyc_status",
        "adresse",
        'deleted_by',
        'motif',
        'validated_at',
        'validated_by',
        'suspended_by',
        'blocked_by',
        'role',
    ];

    protected $guard_name = 'web';

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'metadata' => 'array',
        'date_naissance' => 'date',
        'date_delivrance' => 'date',
        'validated_at' => 'datetime',
    ];

    public function usersCreation(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_creation_id');
    }

    public function photoprofile(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Fichier::class, 'photoprofile_id');
    }

    public function piecerecto(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Fichier::class, 'piecerecto_id');
    }

    public function pieceverso(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Fichier::class, 'pieceverso_id');
    }

    public function pays(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Pays::class);
    }

    public function deletedByUser()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function validatedByUser()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function suspendedByUser()
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    public function blockedByUser()
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id', 'id');
    }

    protected static function boot()
    {
        parent::boot();
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function fullName(): string
    {
        return trim("{$this->nom} {$this->prenoms}");
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
