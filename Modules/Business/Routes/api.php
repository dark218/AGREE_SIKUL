<?php

use Illuminate\Support\Facades\Route;
use Modules\Business\Http\Controllers\MarchandController;
use Modules\Business\Http\Controllers\EmployeController;
//use Modules\Business\Http\Controllers\PosController;
use Modules\Business\Http\Controllers\Api\PosController;
use Modules\GestionStock\Http\Controllers\ArticleController;

/*
|--------------------------------------------------------------------------
| API Routes - Module Business
|--------------------------------------------------------------------------
|
| Routes API pour le module Business
|
*/

Route::prefix('business')->group(function () {
    Route::get('/marchand/{id}/pointsvente', [MarchandController::class, 'getPointsVente']);
    Route::get('/employe/pointsvente/{marchandId}', [PosController::class, 'getPointsVenteByMarchand']);
    Route::get('/article/findBySkuAndPointVente', [ArticleController::class, 'findBySkuAndPointVente']);
});
Route::prefix('v1')->group(function () {
    Route::post("/marchand/login-marchand", [MarchandController::class, "loginMarchand"])
        ->name("marchand.login.marchand");
    Route::post("/marchand/update-marchand", [MarchandController::class, "updateMarchand"])
        ->name("marchand.update.marchand");
    Route::middleware(['auth:api', 'jwt.blacklist'])->group(function () {
        // Marchand routes (authentication required)
        Route::get('/marchand/me', [MarchandController::class, 'getMarchand'])->name('marchand.me');
        Route::get('/marchand/wallets', [MarchandController::class, 'wallets'])->name('marchand.wallets');
        Route::get("/marchand/connected-devices", [MarchandController::class, "listConnectedDevicesMarchand"])->name("marchand.connected-devices");
        
        // Marchand employees routes
        Route::post('/marchand/create-employee', [MarchandController::class, 'createEmployee'])->name('marchand.create.employee');
        Route::get('/marchand/employees', [MarchandController::class, 'getEmployees'])->name('marchand.employees');
        Route::get('/marchand/points-vente', [MarchandController::class, 'getPointsVente'])->name('marchand.points.vente');

        // Client profile and documents
        Route::post("/marchand/update-profile-photo", [MarchandController::class, "updateProfilePhoto"])->name("marchand.update.profile.photo");
        Route::post("/marchand/update-documents", [MarchandController::class, "updateDocuments"])->name("marchand.update.documents");
        Route::post("/marchand/update-password", [MarchandController::class, "updatePassword"])->name("marchand.update.password");

    });
    // Logout route - sans middleware blacklist pour permettre l'invalidation
    Route::middleware(['auth:api'])->group(function () {
        Route::post("/marchand/logout", [MarchandController::class, "logout"])->name("marchand.logout");
        Route::post("/marchand/logout-all-devices", [MarchandController::class, "logoutAllDevicesMarchand"])->name("marchand.logout.all.devices");

    });

});
Route::prefix('v1')->group(function () {
    Route::post("/pos/login-pos", [PosController::class, "loginPos"])
        ->name("pos.login.pos");

    // POS routes (protected)
    Route::middleware(['auth:api', 'jwt.blacklist'])->group(function () {
        // POS profile routes
        Route::get("/pos/get-employe", [PosController::class, "getEmploye"])->name("pos.get.employe");
        Route::get("/pos/connected-devices", [PosController::class, "connectedDevices"])->name("pos.connected.devices");
        Route::post("/pos/update-profile-photo", [PosController::class, "updateProfilePhoto"])->name("pos.update.profile.photo");
        Route::post("/pos/update-password", [PosController::class, "updatePassword"])->name("pos.update.password");
        Route::post("/pos/update-user", [PosController::class, "updateUser"])->name("pos.update.user");

        // POS session routes (caissier)
        Route::get("/pos/current-session", [PosController::class, "getCurrentSession"])->name("pos.current.session");
        Route::post("/pos/open-session", [PosController::class, "openSession"])->name("pos.open.session");
        Route::post("/pos/close-session", [PosController::class, "closeSession"])->name("pos.close.session");

        // POS listing routes (manager)
        Route::get("/pos/caisses", [PosController::class, "getCaisses"])->name("pos.caisses");
        Route::get("/pos/terminaux", [PosController::class, "getTerminaux"])->name("pos.terminaux");
        Route::get("/pos/caissiers", [PosController::class, "getCaissiers"])->name("pos.caissiers");

        // POS management routes (manager)
        Route::post("/pos/attribution-session", [PosController::class, "attributionSession"])->name("pos.attribution.session");
        Route::get("/pos/show-session/{id}", [PosController::class, "showSession"])->name("pos.show.session");
        Route::post("/pos/fermerture-session", [PosController::class, "fermertureSession"])->name("pos.fermerture.session");
        
        // POS session validation routes (manager)
        Route::post("/pos/validate-session/{id}", [PosController::class, "validateSession"])->name("pos.validate.session");
        Route::post("/pos/cancel-session/{id}", [PosController::class, "cancelSession"])->name("pos.cancel.session");
    });
    // Logout routes - sans middleware blacklist pour permettre l'invalidation
    Route::middleware(['auth:api'])->group(function () {
        Route::post("/pos/logout", [PosController::class, "logout"])->name("pos.logout");
        Route::post("/pos/logout-all-devices", [PosController::class, "logoutAllDevices"])->name("pos.logout.all.devices");
    });

});
