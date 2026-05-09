<?php

namespace Modules\Personnel\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServiceValidateur extends User
{
    protected $table = 'users';

    /**
     * Retourne le nom de classe morphologique pour Spatie Permission.
     * Cela garantit que les rôles sont assignés avec le model_type = App\Models\User
     */
    public function getMorphClass()
    {
        return User::class;
    }

    /**
     * Boot du modèle ServiceValidateur
     * On force le rôle = service_validateur à la création
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->role = 'service_validateur';
        });
    }

    /**
     * Scope global :
     * Toutes les requêtes sur ServiceValidateur filtrent role = service_validateur
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('service_validateur_role', function (Builder $builder) {
            $builder->where('role', 'service_validateur');
        });
    }

}
