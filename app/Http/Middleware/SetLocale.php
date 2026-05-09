<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        // Récupérer la locale depuis param URL, session ou préférence utilisateur
        $locale = $request->get('locale');

        if ($locale && in_array($locale, ['fr', 'en'])) {
            Session::put('locale', $locale);

            if (auth()->check()) {
                auth()->user()->update(['langue_preferee' => $locale]);
            }
        }

        // Forcer la valeur finale
        $locale = (string) (
            $locale ??
            Session::get('locale') ??
            auth()->user()?->langue_preferee ??
            config('app.locale')
        );

        // Appliquer à Laravel et Carbon
        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
