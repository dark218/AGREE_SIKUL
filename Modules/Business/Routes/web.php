<?php

use Illuminate\Support\Facades\Route;
use Modules\Business\Http\Controllers\CaisseController;
use Modules\Business\Http\Controllers\CompteBancaireMarchandController;
use Modules\Business\Http\Controllers\EmployeController;
use Modules\Business\Http\Controllers\MarchandController;
use Modules\Business\Http\Controllers\PointVenteController;
use Modules\Business\Http\Controllers\TerminalController;
use Modules\GestionStock\Http\Controllers\ArticleController;

/*
|--------------------------------------------------------------------------
| Web Routes - Module Business
|--------------------------------------------------------------------------
|
| Routes pour la gestion des Marchands, Points de Vente et Employés
|
*/

Route::prefix('business')->group(function() {
    Route::get('/', function() {
        return redirect()->route('marchand.index');
    });
});

Route::middleware(["auth:web"])->group(function () {

    // ============================================
    // Routes Gestion des Marchands
    // ============================================
    Route::prefix('marchand')->name('marchand.')->group(function () {
        Route::get('/', [MarchandController::class, 'index'])->name('index');
        Route::get('/create', [MarchandController::class, 'create'])->name('create');
        Route::post('/', [MarchandController::class, 'store'])->name('store');
        Route::get('/{id}', [MarchandController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [MarchandController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{id}', [MarchandController::class, 'update'])->name('update');
        Route::put('/{id}/statut', [MarchandController::class, 'statut'])->name('statut');
        Route::post('/{id}/validation/{action}', [MarchandController::class, 'validation'])->name('validation');
        Route::post('/{id}/kyc-validation/{action}', [MarchandController::class, 'kycValidation'])->name('kyc.validation');
        Route::post('/{id}/suspendre', [MarchandController::class, 'suspendre'])->name('suspendre');
        Route::post('/{id}/bloquer', [MarchandController::class, 'bloquer'])->name('bloquer');
        Route::get('/{id}/pointsvente', [MarchandController::class, 'getPointsVente'])->name('pointsvente');
    });

    // ============================================
    // Routes Gestion des Points de Vente
    // ============================================
    Route::prefix('pointvente')->name('pointvente.')->group(function () {
        Route::get('/', [PointVenteController::class, 'index'])->name('index');
        Route::get('/create', [PointVenteController::class, 'create'])->name('create');
        Route::post('/', [PointVenteController::class, 'store'])->name('store');
        Route::get('/{id}', [PointVenteController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [PointVenteController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{id}', [PointVenteController::class, 'update'])->name('update');
        Route::put('/{id}/statut', [PointVenteController::class, 'statut'])->name('statut');
        Route::post('/{id}/validation/{action}', [PointVenteController::class, 'validation'])->name('validation');
        Route::post('/{id}/suspendre', [PointVenteController::class, 'suspendre'])->name('suspendre');
        Route::post('/{id}/bloquer', [PointVenteController::class, 'bloquer'])->name('bloquer');
    });

    // ============================================
    // Routes Gestion des Employés
    // ============================================
    Route::prefix('employe')->name('employe.')->group(function () {
        Route::get('/', [EmployeController::class, 'index'])->name('index');
        Route::get('/create', [EmployeController::class, 'create'])->name('create');
        Route::post('/', [EmployeController::class, 'store'])->name('store');
        Route::get('/{id}', [EmployeController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [EmployeController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{id}', [EmployeController::class, 'update'])->name('update');
        Route::put('/{id}/statut', [EmployeController::class, 'statut'])->name('statut');
        Route::post('/{id}/validation/{action}', [EmployeController::class, 'validation'])->name('validation');
        Route::post('/{id}/kyc-validation/{action}', [EmployeController::class, 'kycValidation'])->name('kyc.validation');
        Route::post('/{id}/suspendre', [EmployeController::class, 'suspendre'])->name('suspendre');
        Route::post('/{id}/bloquer', [EmployeController::class, 'bloquer'])->name('bloquer');
    });

    // ============================================
    // Routes Gestion des caisses
    // ============================================
    Route::prefix('caisse')->name('caisse.')->group(function () {
        Route::get('/', [CaisseController::class, 'index'])->name('index');
        Route::get('/create', [CaisseController::class, 'create'])->name('create');
        Route::post('/', [CaisseController::class, 'store'])->name('store');
        Route::get('/{id}', [CaisseController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [CaisseController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{id}', [CaisseController::class, 'update'])->name('update');
        Route::put('/{id}/bloquer', [CaisseController::class, 'bloquer'])->name('bloquer');
        Route::put('/{id}/statut', [CaisseController::class, 'statut'])->name('statut');
    });

    // ============================================
    // Routes Gestion des Terminaux
    // ============================================
    Route::prefix('terminal')->name('terminal.')->group(function () {
        Route::get('/', [TerminalController::class, 'index'])->name('index');
        Route::get('/create', [TerminalController::class, 'create'])->name('create');
        Route::post('/', [TerminalController::class, 'store'])->name('store');
        Route::get('/{id}', [TerminalController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [TerminalController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{id}', [TerminalController::class, 'update'])->name('update');
        Route::put('/{id}/statut', [TerminalController::class, 'statut'])->name('statut');
        Route::post('/{id}/valider', [TerminalController::class, 'valider'])->name('valider');
        Route::post('/{id}/suspendre', [TerminalController::class, 'suspendre'])->name('suspendre');
        Route::post('/{id}/retirer', [TerminalController::class, 'retirer'])->name('retirer');

    });

    // ============================================
    // Routes Gestion des Comptes Bancaires Marchand
    // ============================================
    Route::prefix('compte-bancaire-marchand')->name('compte-bancaire-marchand.')->group(function () {
        Route::get('/', [CompteBancaireMarchandController::class, 'index'])->name('index');
        Route::get('/create', [CompteBancaireMarchandController::class, 'create'])->name('create');
        Route::post('/', [CompteBancaireMarchandController::class, 'store'])->name('store');
        Route::get('/{id}', [CompteBancaireMarchandController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [CompteBancaireMarchandController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CompteBancaireMarchandController::class, 'update'])->name('update');
        Route::put('/{id}/statut', [CompteBancaireMarchandController::class, 'statut'])->name('statut');
        Route::put('/{id}/toggle-active', [CompteBancaireMarchandController::class, 'toggleActive'])->name('toggle-active');
    });

});
