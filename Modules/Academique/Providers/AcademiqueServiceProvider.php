<?php

namespace Modules\Academique\Providers;

use Illuminate\Support\ServiceProvider;

class AcademiqueServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'academique'
        );
    }

    public function boot(): void
    {
        // Register routes
        $this->app->register(RouteServiceProvider::class);

        // Register views
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'academique');

        $this->publishAssets();
    }

    protected function publishAssets(): void
    {
        // Assets will be auto-discovered
    }
}
