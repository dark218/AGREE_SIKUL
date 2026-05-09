<?php

use Illuminate\Http\Request;
use Modules\Parametrage\Http\Controllers\Api\GlobalController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Form data endpoints
Route::get('/parametrage/form-data', function () {
    return response()->json([
        'niveaux' => \Modules\Parametrage\Entities\NiveauEtude::all()->map(fn($n) => ['id' => $n->id, 'libelle' => $n->libelle])->values(),
        'sections' => \Modules\Parametrage\Entities\Section::all()->map(fn($s) => ['id' => $s->id, 'libelle' => $s->libelle])->values(),
        'cycles' => \Modules\Parametrage\Entities\CycleEnseignement::all()->map(fn($c) => ['id' => $c->id, 'libelle' => $c->libelle])->values(),
        'pays' => \Modules\Parametrage\Entities\Pays::all()->map(fn($p) => ['id' => $p->id, 'libelle' => $p->libelle])->values(),
    ]);
})->name('form-data');

// Unites organisationnelles dropdown data
Route::get('/parametrage/unites-organisationnelles', function () {
    return response()->json([
        'unites' => \Modules\Parametrage\Entities\UniteOrganisationnelle::all()->map(fn($u) => ['id' => $u->id, 'libelle' => $u->libelle])->values(),
    ]);
})->name('unites-organisationnelles-dropdown');

Route::prefix('v1')->group(function () {
    // Global routes (no authentication required)
    Route::get('/global/pays', [GlobalController::class, 'getPays'])->name('global.pays');

    // Protected routes (authentication required)
    Route::middleware(['auth:api', 'jwt.blacklist'])->group(function () {
        Route::get('/global/devises', [GlobalController::class, 'getDevises'])->name('global.devises');
        // Payment providers by country
        Route::get("/global/fournisseurs-paiement", [GlobalController::class, "getFournisseursByPays"])->name("clients.fournisseurs-paiement");

    });
});
