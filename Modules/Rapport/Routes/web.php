<?php

use Illuminate\Support\Facades\Route;
use Modules\Rapport\Http\Controllers\RapportController;
use Modules\Rapport\Http\Controllers\StatistiquesEcoleController;
use Modules\Rapport\Http\Controllers\StatistiquesClassesController;

Route::middleware(['auth:web'])->group(function () {

    // Rapports - Main reports dashboard
    Route::get('/rapports', [RapportController::class, 'index'])->name('rapports.index');

    // Statistiques Ecole - READ-ONLY
    Route::prefix('statistiques-ecole')->name('statistiques-ecole.')->group(function () {
        Route::get('/', [StatistiquesEcoleController::class, 'index'])->name('index');
    });

    // Statistiques Classes - READ-ONLY
    Route::prefix('statistiques-classes')->name('statistiques-classes.')->group(function () {
        Route::get('/', [StatistiquesClassesController::class, 'index'])->name('index');
    });

});
