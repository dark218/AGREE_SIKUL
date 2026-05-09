<?php

use Illuminate\Support\Facades\Route;
use Modules\Services\Http\Controllers\ServiceCantineController;
use Modules\Services\Http\Controllers\MenuController;
use Modules\Services\Http\Controllers\InscriptionCantineController;
use Modules\Services\Http\Controllers\PassageCantineController;
use Modules\Services\Http\Controllers\ServiceTransportController;
use Modules\Services\Http\Controllers\InscriptionTransportController;
use Modules\Services\Http\Controllers\ConsultationInfirmerieController;

Route::middleware(['auth:api', 'api'])->group(function () {

    Route::apiResource('services-cantine', ServiceCantineController::class);
    Route::put('services-cantine/{service}/statut', [ServiceCantineController::class, 'statut'])->name('services-cantine.statut');

    Route::apiResource('menus', MenuController::class);
    Route::put('menus/{menu}/statut', [MenuController::class, 'statut'])->name('menus.statut');

    Route::apiResource('inscriptions-cantine', InscriptionCantineController::class);
    Route::put('inscriptions-cantine/{inscription}/statut', [InscriptionCantineController::class, 'statut'])->name('inscriptions-cantine.statut');

    Route::apiResource('passages-cantine', PassageCantineController::class);
    Route::put('passages-cantine/{passage}/statut', [PassageCantineController::class, 'statut'])->name('passages-cantine.statut');

    Route::apiResource('services-transport', ServiceTransportController::class);
    Route::put('services-transport/{service}/statut', [ServiceTransportController::class, 'statut'])->name('services-transport.statut');

    Route::apiResource('inscriptions-transport', InscriptionTransportController::class);
    Route::put('inscriptions-transport/{inscription}/statut', [InscriptionTransportController::class, 'statut'])->name('inscriptions-transport.statut');

    Route::apiResource('consultations-infirmerie', ConsultationInfirmerieController::class);
    Route::put('consultations-infirmerie/{consultation}/statut', [ConsultationInfirmerieController::class, 'statut'])->name('consultations-infirmerie.statut');

});
