<?php

use Illuminate\Support\Facades\Route;

// ============================================
// Routes Gestion des Articles
// ============================================
use Modules\GestionStock\Http\Controllers\ArticleController;
use Modules\GestionStock\Http\Controllers\InventaireController;
use Modules\GestionStock\Http\Controllers\MouvementStockController;
use Modules\GestionStock\Http\Controllers\TransfertStockController;

// Routes pour les articles
Route::prefix('article')->name('article.')->group(function () {
    Route::get('/', [ArticleController::class, 'index'])->name('index');
    Route::get('/create', [ArticleController::class, 'create'])->name('create');
    Route::post('/', [ArticleController::class, 'store'])->name('store');
    Route::get('/{id}', [ArticleController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [ArticleController::class, 'edit'])->name('edit');
    Route::match(['put', 'post'], '/{id}', [ArticleController::class, 'update'])->name('update');
    Route::put('/{id}/statut', [ArticleController::class, 'statut'])->name('statut');
});

// ============================================
// Routes Gestion des Mouvements de Stock
// ============================================
Route::prefix('mouvement-stock')->name('mouvement-stock.')->group(function () {
    Route::get('/', [MouvementStockController::class, 'index'])->name('index');
    Route::get('/create', [MouvementStockController::class, 'create'])->name('create');
    Route::post('/', [MouvementStockController::class, 'store'])->name('store');
    Route::get('/{id}', [MouvementStockController::class, 'show'])->name('show');
});


// ============================================
// Routes Gestion des Transferts de Stock
// ============================================

Route::prefix('transfert-stock')->name('transfert-stock.')->group(function () {
    Route::get('/', [TransfertStockController::class, 'index'])->name('index');
    Route::get('/create', [TransfertStockController::class, 'create'])->name('create');
    Route::post('/', [TransfertStockController::class, 'store'])->name('store');
    Route::get('/{id}', [TransfertStockController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [TransfertStockController::class, 'edit'])->name('edit');
    Route::match(['put', 'post'], '/{id}', [TransfertStockController::class, 'update'])->name('update');
    Route::put('/{id}/validate', [TransfertStockController::class, 'validateTransfert'])->name('validate');
    Route::put('/{id}/reception', [TransfertStockController::class, 'receptionTransfert'])->name('reception');
});

// ============================================
// Routes Gestion des Inventaires
// ============================================

Route::prefix('inventaire')->name('inventaire.')->group(function () {
    Route::get('/', [InventaireController::class, 'index'])->name('index');
    Route::get('/create', [InventaireController::class, 'create'])->name('create');
    Route::post('/', [InventaireController::class, 'store'])->name('store');
    Route::get('/{id}', [InventaireController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [InventaireController::class, 'edit'])->name('edit');
    Route::match(['put', 'post'], '/{id}', [InventaireController::class, 'update'])->name('update');
    Route::put('/{id}/validate', [InventaireController::class, 'validateInventaire'])->name('validate');
    Route::put('/{id}/cancel', [InventaireController::class, 'cancel'])->name('cancel');
});
