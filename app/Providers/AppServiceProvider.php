<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $tunnel = env('APP_TUNNEL', 'local');

        // En production OU quand on passe via ngrok, on force le schéma HTTPS
        // pour que les URL générées (assets Vite, routes nommées, redirections)
        // ne soient pas en http:// et cassent derrière le tunnel.
        if (app()->environment('production') || $tunnel === 'ngrok') {
            URL::forceScheme('https');
            URL::forceRootUrl(config('app.url'));

            // Cookies sécurisés obligatoires derrière HTTPS
            config([
                'session.secure'    => true,
                'session.same_site' => 'lax',
                'session.domain'    => null, // null = auto-détection, évite les conflits de sous-domaine
            ]);
        }

        Schema::defaultStringLength(125);
    }
}
