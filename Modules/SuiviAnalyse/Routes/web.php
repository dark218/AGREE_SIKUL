<?php

use Illuminate\Support\Facades\Route;
use Modules\SuiviAnalyse\Http\Controllers\ModeleRapportController;
use Modules\SuiviAnalyse\Http\Controllers\RapportController;

// Routes préfixées par `suivi-analyse.` pour lever la collision avec
// `rapports.index` déclarée dans Modules/Rapport (dashboard read-only).
// Ici on gère le CRUD des rapports générés + les modèles de rapports.
Route::middleware(['auth:web'])
    ->prefix('suivi-analyse')
    ->name('suivi-analyse.')
    ->group(function () {

        // Modèles de Rapports
        Route::prefix('modeles-rapports')->name('modeles-rapports.')->group(function () {
            Route::get('/', [ModeleRapportController::class, 'index'])->name('index');
            Route::get('/create', [ModeleRapportController::class, 'create'])->name('create');
            Route::post('/', [ModeleRapportController::class, 'store'])->name('store');
            Route::get('/{modele}', [ModeleRapportController::class, 'show'])->name('show');
            Route::get('/{modele}/edit', [ModeleRapportController::class, 'edit'])->name('edit');
            Route::match(['put', 'post'], '/{modele}', [ModeleRapportController::class, 'update'])->name('update');
            Route::delete('/{modele}', [ModeleRapportController::class, 'destroy'])->name('destroy');
            Route::put('/{modele}/statut', [ModeleRapportController::class, 'statut'])->name('statut');
        });

        // Rapports générés
        Route::prefix('rapports')->name('rapports.')->group(function () {
            Route::get('/', [RapportController::class, 'index'])->name('index');
            Route::get('/create', [RapportController::class, 'create'])->name('create');
            Route::post('/', [RapportController::class, 'store'])->name('store');
            Route::get('/{rapport}', [RapportController::class, 'show'])->name('show');
            Route::get('/{rapport}/edit', [RapportController::class, 'edit'])->name('edit');
            Route::match(['put', 'post'], '/{rapport}', [RapportController::class, 'update'])->name('update');
            Route::delete('/{rapport}', [RapportController::class, 'destroy'])->name('destroy');
            Route::put('/{rapport}/statut', [RapportController::class, 'statut'])->name('statut');
        });

    });
