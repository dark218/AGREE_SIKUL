<?php

use Illuminate\Support\Facades\Route;
use Modules\Communication\Http\Controllers\MessageController;
use Modules\Communication\Http\Controllers\AnnonceController;
use Modules\Communication\Http\Controllers\CommentaireAnnonceController;
use Modules\Communication\Http\Controllers\NotificationController;
use Modules\Communication\Http\Controllers\TraductionController;

Route::middleware(['auth:web'])->group(function () {

    // Traductions
    Route::prefix('traductions')->name('traductions.')->group(function () {
        Route::get('/', [TraductionController::class, 'index'])->name('index');
        Route::get('/create', [TraductionController::class, 'create'])->name('create');
        Route::post('/', [TraductionController::class, 'store'])->name('store');
        Route::get('/{traduction}', [TraductionController::class, 'show'])->name('show');
        Route::get('/{traduction}/edit', [TraductionController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{traduction}', [TraductionController::class, 'update'])->name('update');
        Route::delete('/{traduction}', [TraductionController::class, 'destroy'])->name('destroy');
        Route::put('/{traduction}/statut', [TraductionController::class, 'statut'])->name('statut');
    });

    // Messages
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::get('/create', [MessageController::class, 'create'])->name('create');
        Route::post('/', [MessageController::class, 'store'])->name('store');
        Route::get('/{message}', [MessageController::class, 'show'])->name('show');
        Route::get('/{message}/reply', [MessageController::class, 'reply'])->name('reply');
        Route::get('/{message}/edit', [MessageController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{message}', [MessageController::class, 'update'])->name('update');
        Route::delete('/{message}', [MessageController::class, 'destroy'])->name('destroy');
        Route::put('/{message}/statut', [MessageController::class, 'statut'])->name('statut');
    });

    // Annonces
    Route::prefix('annonces')->name('annonces.')->group(function () {
        Route::get('/', [AnnonceController::class, 'index'])->name('index');
        Route::get('/create', [AnnonceController::class, 'create'])->name('create');
        Route::post('/', [AnnonceController::class, 'store'])->name('store');
        Route::get('/{annonce}', [AnnonceController::class, 'show'])->name('show');
        Route::get('/{annonce}/edit', [AnnonceController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{annonce}', [AnnonceController::class, 'update'])->name('update');
        Route::delete('/{annonce}', [AnnonceController::class, 'destroy'])->name('destroy');
        Route::put('/{annonce}/statut', [AnnonceController::class, 'statut'])->name('statut');
    });

    // Commentaires Annonces
    Route::prefix('commentaires-annonces')->name('commentaires-annonces.')->group(function () {
        Route::get('/', [CommentaireAnnonceController::class, 'index'])->name('index');
        Route::get('/create', [CommentaireAnnonceController::class, 'create'])->name('create');
        Route::post('/', [CommentaireAnnonceController::class, 'store'])->name('store');
        Route::get('/{commentaire}', [CommentaireAnnonceController::class, 'show'])->name('show');
        Route::get('/{commentaire}/edit', [CommentaireAnnonceController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{commentaire}', [CommentaireAnnonceController::class, 'update'])->name('update');
        Route::delete('/{commentaire}', [CommentaireAnnonceController::class, 'destroy'])->name('destroy');
        Route::put('/{commentaire}/statut', [CommentaireAnnonceController::class, 'statut'])->name('statut');
    });

});
