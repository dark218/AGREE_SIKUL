<?php

use Illuminate\Support\Facades\Route;
use Modules\RessourcesLogistique\Http\Controllers\BibliothequeController;
use Modules\RessourcesLogistique\Http\Controllers\OuvrageController;
use Modules\RessourcesLogistique\Http\Controllers\ExemplaireController;
use Modules\RessourcesLogistique\Http\Controllers\PersonnelLogistiqueController;
use Modules\RessourcesLogistique\Http\Controllers\EmpruntController;
use Modules\RessourcesLogistique\Http\Controllers\ReservationController;
use Modules\RessourcesLogistique\Http\Controllers\CategorieDocumentController;
use Modules\RessourcesLogistique\Http\Controllers\DocumentController;
use Modules\RessourcesLogistique\Http\Controllers\CategorieEquipementController;
use Modules\RessourcesLogistique\Http\Controllers\EquipementController;
use Modules\RessourcesLogistique\Http\Controllers\MaintenanceEquipementController;
use Modules\RessourcesLogistique\Http\Controllers\CategorieFournitureController;
use Modules\RessourcesLogistique\Http\Controllers\FournitureController;

Route::middleware(['auth:web'])->group(function () {

    // ============ BIBLIOTHÈQUE ============

    // Bibliothèques
    Route::prefix('bibliotheques')->name('bibliotheques.')->group(function () {
        Route::get('/', [BibliothequeController::class, 'index'])->name('index');
        Route::get('/create', [BibliothequeController::class, 'create'])->name('create');
        Route::post('/', [BibliothequeController::class, 'store'])->name('store');
        Route::get('/{bibliotheque}', [BibliothequeController::class, 'show'])->name('show');
        Route::get('/{bibliotheque}/edit', [BibliothequeController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{bibliotheque}', [BibliothequeController::class, 'update'])->name('update');
        Route::delete('/{bibliotheque}', [BibliothequeController::class, 'destroy'])->name('destroy');
        Route::put('/{bibliotheque}/statut', [BibliothequeController::class, 'statut'])->name('statut');
    });

    // Ouvrages
    Route::prefix('ouvrages')->name('ouvrages.')->group(function () {
        Route::get('/', [OuvrageController::class, 'index'])->name('index');
        Route::get('/create', [OuvrageController::class, 'create'])->name('create');
        Route::post('/', [OuvrageController::class, 'store'])->name('store');
        Route::get('/{ouvrage}', [OuvrageController::class, 'show'])->name('show');
        Route::get('/{ouvrage}/edit', [OuvrageController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{ouvrage}', [OuvrageController::class, 'update'])->name('update');
        Route::delete('/{ouvrage}', [OuvrageController::class, 'destroy'])->name('destroy');
        Route::put('/{ouvrage}/statut', [OuvrageController::class, 'statut'])->name('statut');
    });

    // Exemplaires
    Route::prefix('exemplaires')->name('exemplaires.')->group(function () {
        Route::get('/', [ExemplaireController::class, 'index'])->name('index');
        Route::get('/create', [ExemplaireController::class, 'create'])->name('create');
        Route::post('/', [ExemplaireController::class, 'store'])->name('store');
        Route::get('/{exemplaire}', [ExemplaireController::class, 'show'])->name('show');
        Route::get('/{exemplaire}/edit', [ExemplaireController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{exemplaire}', [ExemplaireController::class, 'update'])->name('update');
        Route::delete('/{exemplaire}', [ExemplaireController::class, 'destroy'])->name('destroy');
        Route::put('/{exemplaire}/statut', [ExemplaireController::class, 'statut'])->name('statut');
    });

    // Emprunts
    Route::prefix('emprunts')->name('emprunts.')->group(function () {
        Route::get('/', [EmpruntController::class, 'index'])->name('index');
        Route::get('/create', [EmpruntController::class, 'create'])->name('create');
        Route::post('/', [EmpruntController::class, 'store'])->name('store');
        Route::get('/{emprunt}', [EmpruntController::class, 'show'])->name('show');
        Route::get('/{emprunt}/edit', [EmpruntController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{emprunt}', [EmpruntController::class, 'update'])->name('update');
        Route::delete('/{emprunt}', [EmpruntController::class, 'destroy'])->name('destroy');
        Route::put('/{emprunt}/statut', [EmpruntController::class, 'statut'])->name('statut');
    });

    // Réservations
    Route::prefix('reservations')->name('reservations.')->group(function () {
        Route::get('/', [ReservationController::class, 'index'])->name('index');
        Route::get('/create', [ReservationController::class, 'create'])->name('create');
        Route::post('/', [ReservationController::class, 'store'])->name('store');
        Route::get('/{reservation}', [ReservationController::class, 'show'])->name('show');
        Route::get('/{reservation}/edit', [ReservationController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{reservation}', [ReservationController::class, 'update'])->name('update');
        Route::delete('/{reservation}', [ReservationController::class, 'destroy'])->name('destroy');
        Route::put('/{reservation}/statut', [ReservationController::class, 'statut'])->name('statut');
    });

    // ============ DOCUMENTS ============

    // Catégories Documents
    Route::prefix('categories-documents')->name('categories-documents.')->group(function () {
        Route::get('/', [CategorieDocumentController::class, 'index'])->name('index');
        Route::get('/create', [CategorieDocumentController::class, 'create'])->name('create');
        Route::post('/', [CategorieDocumentController::class, 'store'])->name('store');
        Route::get('/{categorie}', [CategorieDocumentController::class, 'show'])->name('show');
        Route::get('/{categorie}/edit', [CategorieDocumentController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{categorie}', [CategorieDocumentController::class, 'update'])->name('update');
        Route::delete('/{categorie}', [CategorieDocumentController::class, 'destroy'])->name('destroy');
        Route::put('/{categorie}/statut', [CategorieDocumentController::class, 'statut'])->name('statut');
    });

    // Documents
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        Route::get('/create', [DocumentController::class, 'create'])->name('create');
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::get('/{document}', [DocumentController::class, 'show'])->name('show');
        Route::get('/{document}/edit', [DocumentController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{document}', [DocumentController::class, 'update'])->name('update');
        Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
        Route::put('/{document}/statut', [DocumentController::class, 'statut'])->name('statut');
    });

    // ============ INVENTAIRE ============

    // Catégories Équipements — pluriel/pluriel aligné sur les permissions
    // (`categories-equipements-*`) et les redirects du controller.
    Route::prefix('categories-equipements')->name('categories-equipements.')->group(function () {
        Route::get('/', [CategorieEquipementController::class, 'index'])->name('index');
        Route::get('/create', [CategorieEquipementController::class, 'create'])->name('create');
        Route::post('/', [CategorieEquipementController::class, 'store'])->name('store');
        Route::get('/{categorie}', [CategorieEquipementController::class, 'show'])->name('show');
        Route::get('/{categorie}/edit', [CategorieEquipementController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{categorie}', [CategorieEquipementController::class, 'update'])->name('update');
        Route::delete('/{categorie}', [CategorieEquipementController::class, 'destroy'])->name('destroy');
        Route::put('/{categorie}/statut', [CategorieEquipementController::class, 'statut'])->name('statut');
    });

    // Équipements
    Route::prefix('equipements')->name('equipements.')->group(function () {
        Route::get('/', [EquipementController::class, 'index'])->name('index');
        Route::get('/create', [EquipementController::class, 'create'])->name('create');
        Route::post('/', [EquipementController::class, 'store'])->name('store');
        Route::get('/{equipement}', [EquipementController::class, 'show'])->name('show');
        Route::get('/{equipement}/edit', [EquipementController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{equipement}', [EquipementController::class, 'update'])->name('update');
        Route::delete('/{equipement}', [EquipementController::class, 'destroy'])->name('destroy');
        Route::put('/{equipement}/statut', [EquipementController::class, 'statut'])->name('statut');
    });

    // Maintenances Équipements
    // Nom pluriel/pluriel — aligné sur les permissions RBAC (`maintenances-equipements-*`)
    // et sur les redirects du controller.
    Route::prefix('maintenances-equipements')->name('maintenances-equipements.')->group(function () {
        Route::get('/', [MaintenanceEquipementController::class, 'index'])->name('index');
        Route::get('/create', [MaintenanceEquipementController::class, 'create'])->name('create');
        Route::post('/', [MaintenanceEquipementController::class, 'store'])->name('store');
        Route::get('/{maintenance}', [MaintenanceEquipementController::class, 'show'])->name('show');
        Route::get('/{maintenance}/edit', [MaintenanceEquipementController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{maintenance}', [MaintenanceEquipementController::class, 'update'])->name('update');
        Route::delete('/{maintenance}', [MaintenanceEquipementController::class, 'destroy'])->name('destroy');
        Route::put('/{maintenance}/statut', [MaintenanceEquipementController::class, 'statut'])->name('statut');
    });

    // ============ FOURNITURES ============

    // Catégories Fournitures
    Route::prefix('categories-fournitures')->name('categories-fournitures.')->group(function () {
        Route::get('/', [CategorieFournitureController::class, 'index'])->name('index');
        Route::get('/create', [CategorieFournitureController::class, 'create'])->name('create');
        Route::post('/', [CategorieFournitureController::class, 'store'])->name('store');
        Route::get('/{categorieFourniture}', [CategorieFournitureController::class, 'show'])->name('show');
        Route::get('/{categorieFourniture}/edit', [CategorieFournitureController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{categorieFourniture}', [CategorieFournitureController::class, 'update'])->name('update');
        Route::delete('/{categorieFourniture}', [CategorieFournitureController::class, 'destroy'])->name('destroy');
        Route::put('/{categorieFourniture}/statut', [CategorieFournitureController::class, 'statut'])->name('statut');
    });

    // Fournitures
    Route::prefix('fournitures')->name('fournitures.')->group(function () {
        Route::get('/', [FournitureController::class, 'index'])->name('index');
        Route::get('/create', [FournitureController::class, 'create'])->name('create');
        Route::post('/', [FournitureController::class, 'store'])->name('store');
        Route::get('/{fourniture}', [FournitureController::class, 'show'])->name('show');
        Route::get('/{fourniture}/edit', [FournitureController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{fourniture}', [FournitureController::class, 'update'])->name('update');
        Route::delete('/{fourniture}', [FournitureController::class, 'destroy'])->name('destroy');
        Route::put('/{fourniture}/statut', [FournitureController::class, 'statut'])->name('statut');
    });

    // ============ NAVIGATION SHORTCUTS ============

    // Redirect routes for sidebar navigation items (using URL instead of route name to avoid conflicts)

    // Personnel Logistique Dashboard
    Route::get('/personnel-logistique', [PersonnelLogistiqueController::class, 'index'])->name('personnel-logistique');

    // Demandes de Ressources → Documents (gestion des documents)
    Route::get('/demandes-ressources', function () {
        return redirect()->route('documents.index');
    })->name('demandes-ressources');

    // Inventaire Logistique → Equipements (voir l'inventaire réel des équipements)
    Route::get('/inventaire-logistique', function () {
        return redirect()->route('equipements.index');
    })->name('inventaire-logistique');

    // Équipements (configuration) → Categories-Equipement (gérer les types d'équipements)
    Route::get('/equipements-config', function () {
        return redirect()->route('categories-equipement.index');
    })->name('equipements-config');

});
