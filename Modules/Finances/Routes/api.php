<?php

use Illuminate\Support\Facades\Route;
use Modules\Finances\Http\Controllers\TypeFraisController;
use Modules\Finances\Http\Controllers\FraisController;
use Modules\Finances\Http\Controllers\PaiementController;
use Modules\Finances\Http\Controllers\EcheancierController;
use Modules\Finances\Http\Controllers\DepenseController;

Route::middleware(['auth:api', 'api'])->group(function () {

    Route::apiResource('types-frais', TypeFraisController::class);
    Route::put('types-frais/{type_frais}/statut', [TypeFraisController::class, 'statut'])->name('types-frais.statut');

    Route::apiResource('frais', FraisController::class);
    Route::put('frais/{frais}/statut', [FraisController::class, 'statut'])->name('frais.statut');

    Route::apiResource('paiements', PaiementController::class);
    Route::put('paiements/{paiement}/statut', [PaiementController::class, 'statut'])->name('paiements.statut');

    Route::apiResource('echeanciers', EcheancierController::class);
    Route::put('echeanciers/{echeancier}/statut', [EcheancierController::class, 'statut'])->name('echeanciers.statut');

    Route::apiResource('depenses', DepenseController::class);
    Route::put('depenses/{depense}/statut', [DepenseController::class, 'statut'])->name('depenses.statut');

});
