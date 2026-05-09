<?php

namespace Modules\Services\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The module namespace to assume when generating URLs to actions.
     *
     * @var string
     */
    protected $moduleNamespace = 'Modules\Services\Http\Controllers';

    /**
     * Called before routes are registered.
     *
     * @return void
     */
    public function boot()
    {
        \Log::info('=== Services RouteServiceProvider::boot called ===');

        // Explicit route binding with callback for better control
        Route::bind('service', function ($value) {
            \Log::info("🔗 Route::bind('service') called with value: " . $value);
            $model = \Modules\Services\Entities\ServicesTransport::find($value);
            \Log::info("🔗 Binding result - Model exists: " . ($model ? 'YES' : 'NO') . ", ID: " . ($model?->id ?? 'NULL'));
            return $model;
        });

        Route::bind('serviceCantine', function ($value) {
            return \Modules\Services\Entities\ServiceCantine::find($value);
        });

        Route::bind('inscriptionCantine', function ($value) {
            return \Modules\Services\Entities\InscriptionCantine::find($value);
        });

        Route::bind('passageCantine', function ($value) {
            return \Modules\Services\Entities\PassageCantine::find($value);
        });

        \Log::info('✓ Registered route binding callbacks: service, serviceCantine, inscriptionCantine, passageCantine');

        parent::boot();
        \Log::info('✓ Services routes registered successfully');
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Services', '/Routes/web.php'));
    }

}
