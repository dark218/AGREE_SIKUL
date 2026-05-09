<?php

namespace Modules\Services\Providers;

use Illuminate\Support\ServiceProvider;

class ServicesServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Services';
    protected string $moduleNameLower = 'services';

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            $this->moduleNameLower
        );
    }

    public function boot(): void
    {
        \Log::info('=== ServicesServiceProvider::boot called ===');

        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        // Register routes
        \Log::info('Registering RouteServiceProvider for Services module');
        $this->app->register(RouteServiceProvider::class);
        \Log::info('✓ RouteServiceProvider registered');
    }

    public function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'Resources/views');
        $this->publishes([$sourcePath => $viewPath], ['views', $this->moduleNameLower . '-views']);
    }

    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->publishes([$langPath => lang_path('modules/' . $this->moduleNameLower)], $this->moduleNameLower . '-lang');
        }
    }

    public function provides(): array
    {
        return [];
    }
}
