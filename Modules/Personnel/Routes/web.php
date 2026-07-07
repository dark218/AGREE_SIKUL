<?php

use Illuminate\Support\Facades\Route;
use Modules\Personnel\Http\Controllers\ParentController;
use Modules\Personnel\Http\Controllers\AccompagnateurController;
use Modules\Personnel\Http\Controllers\TuteurController;
use Modules\Personnel\Http\Controllers\PersonnelAdministratifController;

// Route model binding pour {personnel_administratif} → PersonnelAdministratif.
Route::model('personnel_administratif', \Modules\Personnel\Entities\PersonnelAdministratif::class);


// ============================================
// Routes Gestion des Parents
// ============================================
Route::prefix('parents')->name('parents.')->group(function () {
    Route::get('/', [ParentController::class, 'index'])->name('index');
    Route::get('/create', [ParentController::class, 'create'])->name('create');
    Route::post('/', [ParentController::class, 'store'])->name('store');
    Route::get('/{id}', [ParentController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [ParentController::class, 'edit'])->name('edit');
    Route::match(['put', 'post'], '/{id}', [ParentController::class, 'update'])->name('update');
    Route::put('/{id}/statut', [ParentController::class, 'statut'])->name('statut');
    Route::delete('/{id}', [ParentController::class, 'destroy'])->name('destroy');
});

// ============================================
// Routes Gestion des Accompagnateurs
// ============================================
Route::prefix('accompagnateurs')->name('accompagnateurs.')->group(function () {
    Route::get('/', [AccompagnateurController::class, 'index'])->name('index');
    Route::get('/create', [AccompagnateurController::class, 'create'])->name('create');
    Route::post('/', [AccompagnateurController::class, 'store'])->name('store');
    Route::get('/{id}', [AccompagnateurController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [AccompagnateurController::class, 'edit'])->name('edit');
    Route::match(['put', 'post'], '/{id}', [AccompagnateurController::class, 'update'])->name('update');
    Route::put('/{id}/statut', [AccompagnateurController::class, 'statut'])->name('statut');
    Route::delete('/{id}', [AccompagnateurController::class, 'destroy'])->name('destroy');
});

// ============================================
// Routes Gestion des Tuteurs
// ============================================
Route::prefix('tuteurs')->name('tuteurs.')->group(function () {
    Route::get('/', [TuteurController::class, 'index'])->name('index');
    Route::get('/create', [TuteurController::class, 'create'])->name('create');
    Route::post('/', [TuteurController::class, 'store'])->name('store');
    Route::get('/{tuteur}', [TuteurController::class, 'show'])->name('show');
    Route::get('/{tuteur}/edit', [TuteurController::class, 'edit'])->name('edit');
    Route::put('/{tuteur}', [TuteurController::class, 'update'])->name('update');
    Route::put('/{tuteur}/statut', [TuteurController::class, 'statut'])->name('statut');
    Route::delete('/{tuteur}', [TuteurController::class, 'destroy'])->name('destroy');
});

// ============================================
// Routes Personnel Administratif
// Déplacé depuis Modules/Academique (mauvaise localisation métier).
// ============================================
Route::prefix('personnels-administratifs')->name('personnels_administratifs.')->group(function () {
    Route::get('/', [PersonnelAdministratifController::class, 'index'])->name('index');
    Route::get('/create', [PersonnelAdministratifController::class, 'create'])->name('create');
    Route::post('/', [PersonnelAdministratifController::class, 'store'])->name('store');
    Route::get('/{personnel_administratif}', [PersonnelAdministratifController::class, 'show'])->name('show');
    Route::get('/{personnel_administratif}/edit', [PersonnelAdministratifController::class, 'edit'])->name('edit');
    Route::match(['put', 'post'], '/{personnel_administratif}', [PersonnelAdministratifController::class, 'update'])->name('update');
    Route::put('/{personnel_administratif}/statut', [PersonnelAdministratifController::class, 'statut'])->name('statut');
    Route::delete('/{personnel_administratif}', [PersonnelAdministratifController::class, 'destroy'])->name('destroy');
});
