<?php

namespace App\Services;

use App\Models\TauxdeChange;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Tools
{
    public static function getTaux($devise_source, $devise_cible)
    {
        $devise_source = strtoupper($devise_source);
        $devise_cible = strtoupper($devise_cible);

        // 1. Chercher taux direct
        $taux = TauxdeChange::where('devise_source', $devise_source)
            ->where('devise_cible', $devise_cible)
            ->latest('fetched_at')
            ->first();

        if ($taux && $taux->taux > 0) {
            return $taux;
        }

        // 2. Sinon, chercher taux inverse
        $tauxInverse = TauxdeChange::where('devise_source', $devise_cible)
            ->where('devise_cible', $devise_source)
            ->latest('fetched_at')
            ->first();

        if ($tauxInverse && $tauxInverse->taux > 0) {
            return (object)[
                'taux' => 1 / $tauxInverse->taux,
                'devise_source' => $devise_source,
                'devise_cible' => $devise_cible,
            ];
        }

        // 3. Aucun taux trouvé, retourner un taux par défaut
        return (object)[
            'taux' => 1,
            'devise_source' => $devise_source,
            'devise_cible' => $devise_cible,
        ];
    }

    function roundToNearestFive($amount) {
        return round($amount / 5) * 5;
    }

    public static function getLocale()
    {
        return App::currentLocale() ?? config('app.locale');
    }

}
