<?php

namespace Modules\Rapport\Providers;

use Illuminate\Support\ServiceProvider;

class RapportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'rapport'
        );
    }

    public function boot(): void
    {
        // Register routes
        $this->app->register(RouteServiceProvider::class);

        $this->publishAssets();
    }

    protected function publishAssets(): void
    {
        // Assets will be auto-discovered
    }
}
