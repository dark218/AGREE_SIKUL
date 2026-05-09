<?php

namespace Modules\Administration\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Administration\Entities\Feature;
use App\Models\BaseModel;
class Module extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = "module";
    protected $fillable = [
        "libelle",
        "libelle_en",
        "menu_url",
        "icone",
        "ordre"
    ];

    public $timestamps = true;

    // Scope pour les modules actifs
    public function scopeActif($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function feature()
    {
        $profil = config('app.devtype');
        if ($profil == 1) {
            $menurl = (new MenuUrl())->getMenus();
            return $this->hasMany(Feature::class)->whereIn('feature.menu_url', $menurl)->orderBy("ordre");
        } else {
            return $this->hasMany(Feature::class)->orderBy("ordre");
        }
    }

    public function fonctionnalitesActives()
    {
        return $this->hasMany(Feature::class)
                    ->whereNull('deleted_at')
                    ->orderBy('ordre');
    }


    /**
     * Vérifie si l'utilisateur peut voir ce module
     * Le module est visible si l'utilisateur a accès à au moins une de ses fonctionnalités
     */
    public function peutVoir($user)
    {
        // Récupérer les features actives du module
        $features = $this->fonctionnalitesActives;

        // Si le module n'a pas de features, il n'est pas visible
        if ($features->isEmpty()) {
            return false;
        }

        // Le module est visible si au moins une feature est accessible à l'utilisateur
        foreach ($features as $feature) {
            if ($feature->peutVoir($user)) {
                return true;
            }
        }

        return false;
    }
}
