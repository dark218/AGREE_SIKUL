<?php

use Illuminate\Support\Facades\Route;
use Modules\ServiceClient\Http\Controllers\ClientController;

// ============================================
// Routes Gestion des ServiceClient
// ============================================
Route::prefix('client')->name('client.')->group(function () {
    Route::get('/', [ClientController::class, 'index'])->name('index');
    Route::get('/create', [ClientController::class, 'create'])->name('create');
    Route::post('/', [ClientController::class, 'store'])->name('store');
    Route::post('/check-alias-smil', [ClientController::class, 'checkAliasSmil'])->name('check-alias-smil');
    Route::get('/{uuid}', [ClientController::class, 'show'])->name('show');
    Route::get('/{uuid}/moyens-paiement', [ClientController::class, 'moyensPaiement'])->name('moyens-paiement');
    Route::get('/{uuid}/moyens-paiement/create', [ClientController::class, 'createMoyenPaiement'])->name('moyens-paiement.create');
    Route::post('/{uuid}/moyens-paiement', [ClientController::class, 'storeMoyenPaiement'])->name('moyens-paiement.store');
    Route::get('/{uuid}/moyens-paiement/{moyenId}', [ClientController::class, 'showMoyenPaiement'])->name('moyens-paiement.show');
    Route::get('/{uuid}/moyens-paiement/{moyenId}/edit', [ClientController::class, 'editMoyenPaiement'])->name('moyens-paiement.edit');
    Route::match(['put', 'post'], '/{uuid}/moyens-paiement/{moyenId}', [ClientController::class, 'updateMoyenPaiement'])->name('moyens-paiement.update');
    Route::post('/{uuid}/moyens-paiement/{moyenId}/toggle-statut', [ClientController::class, 'toggleStatutMoyenPaiement'])->name('moyens-paiement.toggle-statut');
    Route::post('/{uuid}/moyens-paiement/{moyenId}/toggle-defaut', [ClientController::class, 'toggleDefautMoyenPaiement'])->name('moyens-paiement.toggle-defaut');
    Route::get('/{id}/edit', [ClientController::class, 'edit'])->name('edit');
    Route::match(['put', 'post'], '/{id}', [ClientController::class, 'update'])->name('update');
    Route::put('/{id}/statut', [ClientController::class, 'statut'])->name('statut');
    Route::post('/{id}/validation/{action}', [ClientController::class, 'validation'])->name('validation');
    Route::post('/{id}/kyc-validation/{action}', [ClientController::class, 'kycValidation'])->name('kyc.validation');
    Route::post('/{id}/suspendre', [ClientController::class, 'suspendre'])->name('suspendre');
    Route::post('/{id}/bloquer', [ClientController::class, 'bloquer'])->name('bloquer');
});
