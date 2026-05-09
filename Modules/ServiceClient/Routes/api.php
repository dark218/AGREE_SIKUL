<?php

use Illuminate\Support\Facades\Route;
use Modules\ServiceClient\Http\Controllers\Api\ClientController;

/*
|--------------------------------------------------------------------------
| ServiceClient API Routes
|--------------------------------------------------------------------------
|
| API routes for the ServiceClient module
|
*/

Route::prefix('v1')->group(function () {
    Route::post("/clients/phone-verification", [ClientController::class, "phoneVerification"])
        ->name("clients.phone.verification");
    Route::post("/clients/check-otp", [ClientController::class, "checkOTP"])
        ->name("clients.check.otp");
    Route::post("/clients/login-client", [ClientController::class, "loginClient"])
        ->name("clients.login.client");
    Route::post("/clients/register-client", [ClientController::class, "registerClient"])
        ->name("clients.register.client");
    Route::post("/clients/update-client", [ClientController::class, "updateClient"])
        ->name("clients.update.client");
    // Client API routes (protected)
    Route::middleware(['auth:api', 'jwt.blacklist'])->group(function () {
        // Client management routes
        Route::get("/clients/me", [ClientController::class, "getClient"])->name("clients.getClient");
        Route::get("/clients/connected-devices", [ClientController::class, "listConnectedDevices"])->name("clients.connected-devices");

        // Client profile and documents
        Route::post("/clients/update-profile-photo", [ClientController::class, "updateProfilePhoto"])->name("clients.update.profile.photo");
        Route::post("/clients/update-documents", [ClientController::class, "updateDocuments"])->name("clients.update.documents");
        Route::post("/clients/update-password", [ClientController::class, "updatePassword"])->name("clients.update.password");

        // Client wallets
        Route::get("/clients/me/wallets", [ClientController::class, "wallets"])->name("clients.wallets");


        // Client payment methods
        Route::get("/clients/me/moyens-paiement", [ClientController::class, "moyensPaiement"])->name("clients.moyens-paiement");
        Route::post("/clients/moyens-paiement", [ClientController::class, "addMoyenPaiement"])->name("clients.moyens-paiement.add");
        Route::get("/clients/moyens-paiement/{id}", [ClientController::class, "showMoyenPaiement"])->name("clients.moyens-paiement.show");
        Route::put("/clients/moyens-paiement/{id}", [ClientController::class, "updateMoyenPaiement"])->name("clients.moyens-paiement.update");
        Route::put("/clients/moyens-paiement/{id}/toggle-statut", [ClientController::class, "toggleStatutMoyenPaiement"])->name("clients.moyens-paiement.toggle-statut");
        Route::put("/clients/moyens-paiement/{id}/toggle-defaut", [ClientController::class, "toggleDefautMoyenPaiement"])->name("clients.moyens-paiement.toggle-defaut");
        Route::delete("/clients/moyens-paiement/{id}", [ClientController::class, "deleteMoyenPaiement"])->name("clients.moyens-paiement.delete");
    });

    // Logout routes - sans middleware blacklist pour permettre l'invalidation
    Route::middleware(['auth:api'])->group(function () {
        Route::post("/clients/logout", [ClientController::class, "logout"])->name("clients.logout");
        Route::post("/clients/logout-all-devices", [ClientController::class, "logoutAllDevices"])->name("clients.logout.all.devices");
    });
});
