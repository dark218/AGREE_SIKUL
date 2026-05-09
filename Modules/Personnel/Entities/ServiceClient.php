<?php

namespace Modules\Personnel\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServiceClient extends User
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
     * Boot du modèle ServiceClient
     * On force le rôle = service_client à la création
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->role = 'service_client';
        });
    }

    /**
     * Scope global :
     * Toutes les requêtes sur ServiceClient filtrent role = service_client
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('service_client_role', function (Builder $builder) {
            $builder->where('role', 'service_client');
        });
    }

}
