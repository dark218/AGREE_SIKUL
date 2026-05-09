<?php

use Illuminate\Support\Facades\Route;
use Modules\Personnel\Http\Controllers\Api\AgentController;

/*
|--------------------------------------------------------------------------
| Personnel API Routes
|--------------------------------------------------------------------------
|
| API routes for the Personnel module
|
*/

Route::prefix('v1')->group(function () {
    // Agent public routes (without auth)
    Route::post("/agents/login", [AgentController::class, "loginAgent"])->name("agents.login");

    // Agent API routes (protected)
    Route::middleware(['auth:api', 'jwt.blacklist'])->group(function () {
        // Agent management routes
        Route::get("/agents/me", [AgentController::class, "getAgent"])->name("agents.get");
        Route::patch("/agents/update", [AgentController::class, "updateAgent"])->name("agents.update");
        Route::get("/agents/connected-devices", [AgentController::class, "listConnectedDevicesAgent"])->name("agents.connected-devices");
        Route::get("/agents/wallets", [AgentController::class, "wallets"])->name("agents.wallets");

        // Agent profile and documents
        Route::post("/agents/update-profile-photo", [AgentController::class, "updateProfilePhoto"])->name("agents.update.profile.photo");
        Route::post("/agents/update-documents", [AgentController::class, "updateDocuments"])->name("agents.update.documents");
        Route::post("/agents/update-password", [AgentController::class, "updatePassword"])->name("agents.update.password");

        // Agent management routes
        Route::post("/agents/create-marchand", [AgentController::class, "createMarchand"])->name("agents.create.marchand");
    });

    // Logout routes - sans middleware blacklist pour permettre l'invalidation
    Route::middleware(['auth:api'])->group(function () {
        Route::post("/agents/logout", [AgentController::class, "logout"])->name("agents.logout");
        Route::post("/agents/logout-all-devices", [AgentController::class, "logoutAllDevicesAgent"])->name("agents.logout.all.devices");
    });
});
