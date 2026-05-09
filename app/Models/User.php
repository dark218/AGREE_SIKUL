<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Business\Entities\Employe;
use Modules\Business\Entities\Marchand;
use Modules\Business\Entities\PointVente;
use Modules\Personnel\Entities\AffectationAgent;
use Modules\Personnel\Entities\MissionAgent;
use Modules\Pos\Entities\SessionCaisse;
use Modules\ServiceClient\Entities\MoyenPaiement;
use Modules\Wallet\Entities\Wallet;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Traits\HasBaseModelFeatures;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;
    use SoftDeletes, HasBaseModelFeatures;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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



    // Si l'utilisateur est propriétaire d'un marchand (1-to-1)
    public function marchand(): HasOne
    {
        return $this->hasOne(Marchand::class, 'proprietaire_id', 'id');
    }

    // Si l'utilisateur est employé dans un ou plusieurs marchands (1-to-n employes)
    public function employes(): HasMany
    {
        return $this->hasMany(Employe::class, 'users_id', 'id');
    }

    public function employePrincipal(): HasOne
    {
        return $this->hasOne(Employe::class, 'users_id', 'id');
    }

    // Points de vente liés via employes (pratique pivot)
    public function pointsVente(): HasManyThrough
    {
        return $this->hasManyThrough(PointVente::class, Employe::class, 'users_id', 'id', 'id', 'point_vente_id');
    }

    // Sessions de caisse pour les caissiers
    public function sessionsCaisse(): HasMany
    {
        return $this->hasMany(SessionCaisse::class, 'caissier_id', 'id');
    }

    // Notifications de l'utilisateur
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id', 'id');
    }

    // Wallets de l'utilisateur
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class, 'owner_id')
                    ->where('owner_type', Wallet::OWNER_TYPE_CLIENT);
    }

    // Moyens de paiement de l'utilisateur
    public function moyensPaiement(): HasMany
    {
        return $this->hasMany(MoyenPaiement::class, 'users_id', 'id');
    }

    // Missions assignées à l'agent
    public function missions(): HasMany
    {
        return $this->hasMany(MissionAgent::class, 'agent_id', 'id');
    }

    // Affectations de l'agent
    public function affectations(): HasMany
    {
        return $this->hasMany(AffectationAgent::class, 'agent_id', 'id');
    }

    // Affectations actives de l'agent
    public function affectationsActives(): HasMany
    {
        return $this->hasMany(AffectationAgent::class, 'agent_id', 'id')
                    ->where('actif', true)
                    ->where(function ($query) {
                        $query->whereNull('date_desaffectation')
                              ->orWhere('date_desaffectation', '>', now());
                    });
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
