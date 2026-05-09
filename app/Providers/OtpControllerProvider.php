<?php

namespace App\Providers;

use App\Http\Controllers\OtpController;
use Illuminate\Support\ServiceProvider;

class OtpControllerProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(OtpController::class, function ($app) {
            return new OtpController();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
