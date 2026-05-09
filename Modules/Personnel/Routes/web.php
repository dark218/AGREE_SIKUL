<?php

use Illuminate\Support\Facades\Route;
use Modules\Personnel\Http\Controllers\AdminController;
use Modules\Personnel\Http\Controllers\AgentController;
use Modules\Personnel\Http\Controllers\ServiceClientController;
use Modules\Personnel\Http\Controllers\ServiceValidateurController;
use Modules\Personnel\Http\Controllers\ParentController;
use Modules\Personnel\Http\Controllers\AccompagnateurController;
use Modules\Personnel\Http\Controllers\TuteurController;


// ============================================
// Routes Gestion des Administrateur
// ============================================
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('/create', [AdminController::class, 'create'])->name('create');
    Route::post('/', [AdminController::class, 'store'])->name('store');
    Route::post('/check-alias-smil', [AdminController::class, 'checkAliasSmil'])->name('check-alias-smil');
    Route::get('/{id}', [AdminController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [AdminController::class, 'edit'])->name('edit');
    Route::match(['put', 'post'], '/{id}', [AdminController::class, 'update'])->name('update');
    Route::put('/{id}/statut', [AdminController::class, 'statut'])->name('statut');
    Route::post('/{id}/validation/{action}', [AdminController::class, 'validation'])->name('validation');
    Route::post('/{id}/kyc-validation/{action}', [AdminController::class, 'kycValidation'])->name('kyc.validation');
    Route::post('/{id}/suspendre', [AdminController::class, 'suspendre'])->name('suspendre');
    Route::post('/{id}/bloquer', [AdminController::class, 'bloquer'])->name('bloquer');
});

// ============================================
// Routes Gestion des Agent
// ============================================
Route::prefix('agent')->name('agent.')->group(function () {
    Route::get('/', [AgentController::class, 'index'])->name('index');
    Route::get('/create', [AgentController::class, 'create'])->name('create');
    Route::post('/', [AgentController::class, 'store'])->name('store');
    Route::post('/check-alias-smil', [AgentController::class, 'checkAliasSmil'])->name('check-alias-smil');
    Route::get('/{id}', [AgentController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [AgentController::class, 'edit'])->name('edit');
    Route::match(['put', 'post'], '/{id}', [AgentController::class, 'update'])->name('update');
    Route::put('/{id}/statut', [AgentController::class, 'statut'])->name('statut');
    Route::post('/{id}/validation/{action}', [AgentController::class, 'validation'])->name('validation');
    Route::post('/{id}/kyc-validation/{action}', [AgentController::class, 'kycValidation'])->name('kyc.validation');
    Route::post('/{id}/suspendre', [AgentController::class, 'suspendre'])->name('suspendre');
    Route::post('/{id}/bloquer', [AgentController::class, 'bloquer'])->name('bloquer');
});

// ============================================
// Routes Gestion des ServiceClient
// ============================================
Route::prefix('service_client')->name('service_client.')->group(function () {
    Route::get('/', [ServiceClientController::class, 'index'])->name('index');
    Route::get('/create', [ServiceClientController::class, 'create'])->name('create');
    Route::post('/', [ServiceClientController::class, 'store'])->name('store');
    Route::post('/check-alias-smil', [ServiceClientController::class, 'checkAliasSmil'])->name('check-alias-smil');
    Route::get('/{id}', [ServiceClientController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [ServiceClientController::class, 'edit'])->name('edit');
    Route::match(['put', 'post'], '/{id}', [ServiceClientController::class, 'update'])->name('update');
    Route::put('/{id}/statut', [ServiceClientController::class, 'statut'])->name('statut');
    Route::post('/{id}/validation/{action}', [ServiceClientController::class, 'validation'])->name('validation');
    Route::post('/{id}/kyc-validation/{action}', [ServiceClientController::class, 'kycValidation'])->name('kyc.validation');
    Route::post('/{id}/suspendre', [ServiceClientController::class, 'suspendre'])->name('suspendre');
    Route::post('/{id}/bloquer', [ServiceClientController::class, 'bloquer'])->name('bloquer');
});

// ============================================
// Routes Gestion des ServiceValidateur
// ============================================
Route::prefix('service_validateur')->name('service_validateur.')->group(function () {
    Route::get('/', [ServiceValidateurController::class, 'index'])->name('index');
    Route::get('/create', [ServiceValidateurController::class, 'create'])->name('create');
    Route::post('/', [ServiceValidateurController::class, 'store'])->name('store');
    Route::post('/check-alias-smil', [ServiceValidateurController::class, 'checkAliasSmil'])->name('check-alias-smil');
    Route::get('/{id}', [ServiceValidateurController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [ServiceValidateurController::class, 'edit'])->name('edit');
    Route::match(['put', 'post'], '/{id}', [ServiceValidateurController::class, 'update'])->name('update');
    Route::put('/{id}/statut', [ServiceValidateurController::class, 'statut'])->name('statut');
    Route::post('/{id}/validation/{action}', [ServiceValidateurController::class, 'validation'])->name('validation');
    Route::post('/{id}/kyc-validation/{action}', [ServiceValidateurController::class, 'kycValidation'])->name('kyc.validation');
    Route::post('/{id}/suspendre', [ServiceValidateurController::class, 'suspendre'])->name('suspendre');
    Route::post('/{id}/bloquer', [ServiceValidateurController::class, 'bloquer'])->name('bloquer');
});

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
