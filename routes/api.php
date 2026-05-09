<?php

use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\SmsTestController;
use App\Http\Controllers\Webhook\PispiWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes v1
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->group(function () {
    Route::get("/test", [SmsTestController::class, "testSms"]);
    Route::post("/webhook/pispi", [PispiWebhookController::class, "handle"])->name("webhook.pispi");


});

