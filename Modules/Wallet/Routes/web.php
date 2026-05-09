<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\Http\Controllers\WalletController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('wallet')->name('wallet.')->group(function() {
    Route::get('/', function() {
        return redirect()->route('wallet.index');
    });
});

Route::middleware(["auth:web"])->group(function () {
    // ============================================
    // Routes Gestion des Wallets
    // ============================================
    Route::prefix('wallets')->name('wallet.')->group(function () {
        // Routes accessibles avec la permission wallet-list
        Route::middleware(['permission.check:wallet-list'])->group(function () {
            Route::get('/', [WalletController::class, 'index'])->name('index');
            Route::get('/{id}', [WalletController::class, 'show'])->name('show');
            
            // Route pour afficher le portefeuille d'un propriétaire spécifique
            Route::get('/owner/{ownerType}/{ownerId}', [WalletController::class, 'showByOwner'])
                ->where('ownerType', 'client|marchand|agent|plateforme')
                ->name('show-by-owner');
        });

        // Routes accessibles avec la permission wallet-edit
        Route::middleware(['permission.check:wallet-edit'])->group(function () {
            Route::get('/{id}/edit', [WalletController::class, 'edit'])->name('edit');
            Route::put('/{id}', [WalletController::class, 'update'])->name('update');
        });

        // Route pour le changement de statut (suppression/restauration)
        Route::middleware(['permission.check:wallet-statut'])->group(function () {
            Route::put('/{id}/statut', [WalletController::class, 'statut'])->name('statut');
        });
    });
});
