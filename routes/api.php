<?php

use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\SmsTestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes v1 — AGREE SIKUL
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Note: l'endpoint /test peut être retiré en production
    Route::get("/test", [SmsTestController::class, "testSms"]);
});
