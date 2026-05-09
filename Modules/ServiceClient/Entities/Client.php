<?php

namespace Modules\ServiceClient\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends User
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
     * Boot du modèle Client
     * On force le rôle = client à la création
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->role = 'client';
        });
    }

    /**
     * Scope global :
     * Toutes les requêtes sur Client filtrent role = client
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('client_role', function (Builder $builder) {
            $builder->where('role', 'client');
        });
    }
}
