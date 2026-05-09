<?php

namespace Modules\Administration\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;

class Feature extends BaseModel
{
    use HasFactory;
    use softDeletes;
    protected $table= "feature";

    protected $fillable =[
        "libelle",
        "libelle_en",
        "module_id",
        "menu_url",
        "icone",
        "ordre"
    ];
    public $timestamps = true;
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(Permissions::class);
    }


    /**
     * Vérifie si l'utilisateur peut voir cette fonctionnalité
     * L'utilisateur doit avoir la permission "list" pour voir la feature dans le menu
     */
    public function peutVoir($user)
    {
        // Récupérer les noms des permissions liées à cette feature
        $permissionNames = $this->permissions()->pluck('name')->toArray();

        // Si aucune permission n'est définie pour cette feature, elle n'est pas visible
        if (empty($permissionNames)) {
            return false;
        }

        // Chercher la permission "list" (pattern: xxx-list)
        $listPermission = collect($permissionNames)->first(function ($name) {
            return str_ends_with($name, '-list');
        });

        // Si une permission "list" existe, l'utilisateur doit l'avoir pour voir la feature
        if ($listPermission) {
            return $user->hasPermissionTo($listPermission);
        }

        // Si pas de permission "list", vérifier au moins une permission
        return $user->hasAnyPermission($permissionNames);
    }
}
