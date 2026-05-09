<?php

namespace Modules\Personnel\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class Agent extends User
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
     * Boot du modèle Agent
     * On force le rôle = agent à la création
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->role = 'agent';
        });
    }

    /**
     * Scope global :
     * Toutes les requêtes sur Agent filtrent role = agent
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('agent_role', function (Builder $builder) {
            $builder->where('role', 'agent');
        });
    }

}
