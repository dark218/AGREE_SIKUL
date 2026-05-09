<?php

namespace Modules\Personnel\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Admin extends User
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
     * Boot du modèle Admin
     * On force le rôle = admin à la création
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->role = 'admin';
        });
    }

    /**
     * Scope global :
     * Toutes les requêtes sur Admin filtrent role = admin
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('admin_role', function (Builder $builder) {
            $builder->where('role', 'admin');
        });
    }

}
