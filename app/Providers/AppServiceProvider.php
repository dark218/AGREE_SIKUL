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
        $tunnel = env('APP_TUNNEL', 'prod');
        $appUrl = config('app.url');

        // On force HTTPS UNIQUEMENT si l'URL effective est en https://.
        // Avant, on forçait dès APP_ENV=production, ce qui cassait les déploiements
        // sur des URLs HTTP (ex: nip.io en HTTP via Dokploy sans SSL).
        if (str_starts_with((string) $appUrl, 'https://') || $tunnel === 'ngrok') {
            URL::forceScheme('https');
            URL::forceRootUrl($appUrl);

            // Cookies sécurisés uniquement si HTTPS actif (sinon erreur 419)
            config([
                'session.secure'    => true,
                'session.same_site' => 'lax',
                'session.domain'    => null,
            ]);
        }

        Schema::defaultStringLength(125);
    }
}
