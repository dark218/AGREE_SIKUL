<?php

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

use Modules\Pos\Http\Controllers\SessionCaisseCaissierController;
use Modules\Pos\Http\Controllers\SessionCaisseManagerController;
use Modules\Pos\Http\Controllers\VentePosController;

// Routes pour le manager de session de caisse
Route::prefix('session-caisse-manager')->name('session-caisse-manager.')->group(function () {
    // Routes de consultation (middleware: session-caisse-manager-list)
    Route::get('/', [SessionCaisseManagerController::class, 'index'])->name('index');
    Route::get('/create', [SessionCaisseManagerController::class, 'create'])->name('create');
    Route::get('/{id}', [SessionCaisseManagerController::class, 'show'])->name('show')->whereNumber('id');
    // Routes d'action (middleware: session-caisse-manager-create)
    Route::post('/attribution', [SessionCaisseManagerController::class, 'attribution'])->name('attribution');
    // Route pour la fermeture (middleware: session-caisse-manager-fermerture)
    Route::post('/fermerture', [SessionCaisseManagerController::class, 'fermerture'])->name('fermerture');
    // Route pour valider la cloture d'une session
    Route::post('/{id}/validate', [SessionCaisseManagerController::class, 'validate'])->name('validate')->whereNumber('id');
    // Route pour annuler une session
    Route::post('/{id}/cancel', [SessionCaisseManagerController::class, 'cancel'])->name('cancel')->whereNumber('id');
});

// Routes pour le caissier
Route::prefix('session-caisse-caissier')->name('session-caisse-caissier.')->group(function () {
    // Routes de consultation
    Route::get('/', [SessionCaisseCaissierController::class, 'index'])->name('index');
    Route::get('/current', [SessionCaisseCaissierController::class, 'current'])->name('current');

    // Routes d'action
    Route::post('/ouverture', [SessionCaisseCaissierController::class, 'ouverture'])->name('ouverture');
    Route::post('/fermerture', [SessionCaisseCaissierController::class, 'fermerture'])->name('fermerture');
});
Route::prefix('ventepos')->name('ventepos.')->group(function () {
        /**
         * =========================
         * Consultation
         * =========================
         */
        Route::get('/', [VentePosController::class, 'index'])->name('index');

        /**
         * =========================
         * Recherche d'articles (AJAX)
         * =========================
         */
        Route::get('/search-articles', [VentePosController::class, 'searchArticles'])->name('search-articles');

        /**
         * =========================
         * Création / modification (avant paiement)
         * =========================
         */
        Route::get('/create', [VentePosController::class, 'create'])->name('create');
        Route::post('/', [VentePosController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [VentePosController::class, 'edit'])->name('edit')->whereNumber('id');
        Route::put('/{id}', [VentePosController::class, 'update'])->name('update')->whereNumber('id');
        Route::delete('/{venteId}/ligne/{ligneId}', [VentePosController::class, 'removeLine'])->name('remove-line')->whereNumber(['venteId', 'ligneId']);

        // Route show DOIT être après /create pour éviter le conflit
        Route::get('/{id}', [VentePosController::class, 'show'])->name('show')->whereNumber('id');
        /**
         * =========================
         * Paiement / Validation
         * =========================
         */
        Route::post('/{id}/validate-paiement', [VentePosController::class, 'validatePaiement'])->name('validate-paiement')->whereNumber('id');
        /**
         * =========================
         * Annulation
         * =========================
         */
        Route::post('/{id}/cancel', [VentePosController::class, 'cancelVente'])->name('cancel')->whereNumber('id');
        /**
         * =========================
         * Remboursements (manager)
         * =========================
         */
        // Remboursement total
        Route::post('/{id}/refund', [VentePosController::class, 'refundVente'])->name('refund')->whereNumber('id');
        // Remboursement par lignes
        Route::post('/{id}/refund-lines', [VentePosController::class, 'refundVenteByLines'])->name('refund-lines')->whereNumber('id');
        // Remboursement par quantité
        Route::post('/{id}/refund-quantite', [VentePosController::class, 'refundVenteByQuantite'])->name('refund-quantite')->whereNumber('id');
        // Remboursement mixte (montant + moyen paiement)
        Route::post('/{id}/refund-mixte', [VentePosController::class, 'refundVenteMixte'])->name('refund-mixte')->whereNumber('id');
        // Remboursement mixte + quantité
        Route::post('/{id}/refund-mixte-quantite', [VentePosController::class, 'refundVenteMixteQuantite'])->name('refund-mixte-quantite')->whereNumber('id');
    });

