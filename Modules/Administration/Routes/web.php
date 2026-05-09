<?php

use Modules\Administration\Http\Controllers\ErrorLogController;
use Modules\Administration\Http\Controllers\RoleController;
use Modules\Administration\Http\Controllers\UserController;
use Modules\Administration\Http\Controllers\ModuleController;
use Modules\Administration\Http\Controllers\FeatureController;
use Modules\Administration\Http\Controllers\PermissionsController;
use Modules\Administration\Http\Controllers\SessionController;

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

Route::middleware(["auth:web"])->group(function () {
    Route::prefix('administration')->name('administration.')->group(function() {
        Route::get('/', function() {
            return redirect()->route('administration.roles.index');
        });

        // ============================================
        // Routes Gestion des Rôles
        // ============================================
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::get('/create', [RoleController::class, 'create'])->name('create');
            Route::post('/', [RoleController::class, 'store'])->name('store');
            Route::get('/{id}', [RoleController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [RoleController::class, 'edit'])->name('edit');
            Route::match(['put', 'post'], '/{id}', [RoleController::class, 'update'])->name('update');
            Route::delete('/{id}', [RoleController::class, 'destroy'])->name('destroy');
            Route::put('/{id}/statut', [RoleController::class, 'statut'])->name('statut');
        });

        // ============================================
        // Routes Gestion des Utilisateurs
        // ============================================
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');

            // Vérification alias_smil (DOIT être avant les routes avec paramètres dynamiques)
            Route::post('/check-alias', [UserController::class, 'checkAliasSmil'])->name('check-alias');

            Route::get('/{uuid}', [UserController::class, 'show'])->name('show');
            Route::get('/{uuid}/edit', [UserController::class, 'edit'])->name('edit');
            Route::patch('/{uuid}', [UserController::class, 'update'])->name('update');
            Route::match(['put', 'post'], '/{uuid}', [UserController::class, 'update']); // Alternative pour PATCH
            Route::delete('/{uuid}', [UserController::class, 'destroy'])->name('destroy');
            Route::put('/{uuid}/statut', [UserController::class, 'statut'])->name('statut');
            Route::put('/{id}/suspendre', [UserController::class, 'suspendre'])->name('suspendre');
            Route::put('/{id}/bloquer', [UserController::class, 'bloquer'])->name('bloquer');

            // Profile
            Route::get('/{id}/editprofile', [UserController::class, 'editprofile'])->name('editprofile');
            Route::post('/{id}/updateprofile', [UserController::class, 'updateprofile'])->name('updateprofile');
        });

        // ============================================
        // Routes Gestion des Modules
        // ============================================
        Route::prefix('modules')->name('modules.')->group(function () {
            Route::get('/', [ModuleController::class, 'index'])->name('index');
            Route::get('/create', [ModuleController::class, 'create'])->name('create');
            Route::post('/', [ModuleController::class, 'store'])->name('store');
            Route::get('/{id}', [ModuleController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [ModuleController::class, 'edit'])->name('edit');
            Route::put('/{id}', [ModuleController::class, 'update'])->name('update');
            Route::put('/{id}/statut', [ModuleController::class, 'statut'])->name('statut');
        });

        // ============================================
        // Routes Gestion des Fonctionnalités (Features)
        // ============================================
        Route::prefix('features')->name('features.')->group(function () {
            Route::get('/', [FeatureController::class, 'index'])->name('index');
            Route::get('/create', [FeatureController::class, 'create'])->name('create');
            Route::post('/', [FeatureController::class, 'store'])->name('store');
            Route::get('/{id}', [FeatureController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [FeatureController::class, 'edit'])->name('edit');
            Route::put('/{id}', [FeatureController::class, 'update'])->name('update');
            Route::put('/{id}/statut', [FeatureController::class, 'statut'])->name('statut');
        });

        // ============================================
        // Routes Gestion des Permissions
        // ============================================
        Route::prefix('permissions')->name('permissions.')->group(function () {
            Route::get('/', [PermissionsController::class, 'index'])->name('index');
            Route::get('/create', [PermissionsController::class, 'create'])->name('create');
            Route::post('/', [PermissionsController::class, 'store'])->name('store');
            Route::get('/{id}', [PermissionsController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [PermissionsController::class, 'edit'])->name('edit');
            Route::put('/{id}', [PermissionsController::class, 'update'])->name('update');
            Route::put('/{id}/statut', [PermissionsController::class, 'statut'])->name('statut');
        });

        // ============================================
        // Routes Gestion des Sessions
        // ============================================
        Route::prefix('session')->name('session.')->group(function () {
            Route::get('/', [SessionController::class, 'index'])->name('index');
            Route::delete('/{id}', [SessionController::class, 'destroy'])->name('destroy');
            Route::delete('/user/{userId}', [SessionController::class, 'destroyByUser'])->name('destroyByUser');
        });

        // ============================================
        // Routes Gestion des Logs d'Erreurs
        // ============================================
        Route::prefix('errorlog')->name('errorlog.')->group(function () {
            Route::get('/', [ErrorLogController::class, 'index'])->name('index');
            Route::get('/{id}', [ErrorLogController::class, 'show'])->name('show');
            Route::delete('/{id}', [ErrorLogController::class, 'destroy'])->name('destroy');
            Route::delete('/', [ErrorLogController::class, 'destroyAll'])->name('destroyAll');
        });
    });
});
