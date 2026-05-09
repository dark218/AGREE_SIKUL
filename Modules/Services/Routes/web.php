<?php

use Illuminate\Support\Facades\Route;
use Modules\Services\Http\Controllers\ServiceCantineController;
use Modules\Services\Http\Controllers\MenuController;
use Modules\Services\Http\Controllers\MenuCantineController;
use Modules\Services\Http\Controllers\InscriptionCantineController;
use Modules\Services\Http\Controllers\PassageCantineController;
use Modules\Services\Http\Controllers\ServiceTransportController;
use Modules\Services\Http\Controllers\InscriptionTransportController;
use Modules\Services\Http\Controllers\ConsultationInfirmerieController;

// DEBUG: Route WITHOUT middleware
Route::get('/debug-auth', function () {
    \Log::info('🔐 Debug Auth Endpoint');
    \Log::info('Auth::check(): ' . (Auth::check() ? 'TRUE' : 'FALSE'));
    \Log::info('User: ' . (Auth::user()?->email ?? 'NONE'));

    return response()->json([
        'auth_check' => Auth::check(),
        'user' => Auth::user()?->email,
        'session_id' => session()->getId(),
        'timestamp' => now()
    ]);
});

// Services routes - Using only auth:web middleware (session_expired middleware has session issues)
Route::middleware(['auth:web'])->group(function () {

    // DEBUG ROUTE - Test Transport directly
    Route::get('/debug-transport', function () {
        \Log::info('🐛 DEBUG-TRANSPORT ROUTE ACCESSED');
        return response()->json([
            'status' => 'ok',
            'user' => auth()->user()?->email,
            'timestamp' => now(),
            'message' => 'Debug route works!'
        ]);
    });

    // DEBUG ROUTE - Check auth INSIDE middleware group
    Route::get('/debug-auth-inside', function () {
        \Log::info('🔐 Debug Auth (INSIDE middleware)');
        \Log::info('Auth::check(): ' . (Auth::check() ? 'TRUE' : 'FALSE'));
        \Log::info('User: ' . (Auth::user()?->email ?? 'NONE'));

        return response()->json([
            'auth_check' => Auth::check(),
            'user' => Auth::user()?->email,
            'message' => 'This is INSIDE the session_expired middleware group'
        ]);
    });

    // DEBUG ROUTE - Show menu structure
    Route::get('/debug-menu', function () {
        $user = auth()->user();
        $modules = \Modules\Administration\Entities\Module::actif()
            ->with(['fonctionnalitesActives' => function ($query) {
                $query->orderBy('ordre')->with('permissions');
            }])
            ->orderBy('ordre')
            ->get();

        $servicesModule = $modules->firstWhere('libelle', 'Services');

        $features = [];
        if ($servicesModule) {
            foreach ($servicesModule->fonctionnalitesActives as $f) {
                $canSee = $user->hasRole('super_admin') || $f->peutVoir($user);
                $features[] = [
                    'id' => $f->id,
                    'libelle' => $f->libelle,
                    'menu_url' => $f->menu_url,
                    'can_see' => $canSee,
                    'permissions_count' => $f->permissions()->count()
                ];
            }
        }

        return response()->json([
            'user' => $user->email,
            'is_super_admin' => $user->hasRole('super_admin'),
            'services_module_found' => !!$servicesModule,
            'features' => $features
        ]);
    });

    // Redirect /cantine to /services-cantine
    Route::redirect('/cantine', '/services-cantine');

    // Services Cantine
    Route::prefix('services-cantine')->name('services-cantine.')->group(function () {
        Route::get('/', [ServiceCantineController::class, 'index'])->name('index');
        Route::get('/create', [ServiceCantineController::class, 'create'])->name('create');
        Route::post('/', [ServiceCantineController::class, 'store'])->name('store');
        Route::get('/{service}', [ServiceCantineController::class, 'show'])->name('show');
        Route::get('/{service}/edit', [ServiceCantineController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{service}', [ServiceCantineController::class, 'update'])->name('update');
        Route::delete('/{service}', [ServiceCantineController::class, 'destroy'])->name('destroy');
        Route::put('/{service}/statut', [ServiceCantineController::class, 'statut'])->name('statut');
    });

    // Menus
    Route::prefix('menus')->name('menus.')->group(function () {
        Route::get('/', [MenuController::class, 'index'])->name('index');
        Route::get('/create', [MenuController::class, 'create'])->name('create');
        Route::post('/', [MenuController::class, 'store'])->name('store');
        Route::get('/{menu}', [MenuController::class, 'show'])->name('show');
        Route::get('/{menu}/edit', [MenuController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{menu}', [MenuController::class, 'update'])->name('update');
        Route::delete('/{menu}', [MenuController::class, 'destroy'])->name('destroy');
        Route::put('/{menu}/statut', [MenuController::class, 'statut'])->name('statut');
        Route::get('/{menu}/pdf', [MenuController::class, 'exportPdf'])->name('pdf');
    })->where('menu', '\d+');

    // Menus Cantine (Weekly)
    Route::prefix('menus-cantine')->name('menu-cantines.')->group(function () {
        Route::get('/', [MenuCantineController::class, 'index'])->name('index');
        Route::get('/create', [MenuCantineController::class, 'create'])->name('create');
        Route::post('/', [MenuCantineController::class, 'store'])->name('store');
        Route::post('/semaine', [MenuCantineController::class, 'storeWeek'])->name('storeWeek');
        Route::get('/{menuCantine}', [MenuCantineController::class, 'show'])->name('show');
        Route::get('/{menuCantine}/edit', [MenuCantineController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{menuCantine}', [MenuCantineController::class, 'update'])->name('update');
        Route::delete('/{menuCantine}', [MenuCantineController::class, 'destroy'])->name('destroy');
        Route::put('/{menuCantine}/statut', [MenuCantineController::class, 'statut'])->name('statut');
        Route::get('/{menuCantine}/pdf', [MenuCantineController::class, 'exportPdf'])->name('pdf');
    });

    // Inscriptions Cantine
    Route::prefix('inscriptions-cantine')->name('inscriptions-cantine.')->group(function () {
        Route::get('/', [InscriptionCantineController::class, 'index'])->name('index');
        Route::get('/create', [InscriptionCantineController::class, 'create'])->name('create');
        Route::post('/', [InscriptionCantineController::class, 'store'])->name('store');
        Route::get('/{inscriptionCantine}', [InscriptionCantineController::class, 'show'])->name('show');
        Route::get('/{inscriptionCantine}/edit', [InscriptionCantineController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{inscriptionCantine}', [InscriptionCantineController::class, 'update'])->name('update');
        Route::delete('/{inscriptionCantine}', [InscriptionCantineController::class, 'destroy'])->name('destroy');
        Route::put('/{inscriptionCantine}/statut', [InscriptionCantineController::class, 'statut'])->name('statut');
    });

    // Passages Cantine
    Route::prefix('passages-cantine')->name('passages-cantine.')->group(function () {
        Route::get('/', [PassageCantineController::class, 'index'])->name('index');
        Route::get('/create', [PassageCantineController::class, 'create'])->name('create');
        Route::post('/', [PassageCantineController::class, 'store'])->name('store');
        Route::get('/{passage}', [PassageCantineController::class, 'show'])->name('show');
        Route::get('/{passage}/edit', [PassageCantineController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{passage}', [PassageCantineController::class, 'update'])->name('update');
        Route::delete('/{passage}', [PassageCantineController::class, 'destroy'])->name('destroy');
        Route::put('/{passage}/statut', [PassageCantineController::class, 'statut'])->name('statut');
    });

    // Services Transport
    Route::prefix('services-transport')->name('services-transport.')->group(function () {
        Route::get('/', [ServiceTransportController::class, 'index'])->name('index');
        Route::get('/create', [ServiceTransportController::class, 'create'])->name('create');
        Route::post('/', [ServiceTransportController::class, 'store'])->name('store');
        Route::get('/{service}', [ServiceTransportController::class, 'show'])->name('show');
        Route::get('/{service}/edit', [ServiceTransportController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{service}', [ServiceTransportController::class, 'update'])->name('update');
        Route::delete('/{service}', [ServiceTransportController::class, 'destroy'])->name('destroy');
        Route::put('/{service}/statut', [ServiceTransportController::class, 'statut'])->name('statut');
    });

    // Inscriptions Transport
    Route::prefix('inscriptions-transport')->name('inscriptions-transport.')->group(function () {
        Route::get('/', [InscriptionTransportController::class, 'index'])->name('index');
        Route::get('/create', [InscriptionTransportController::class, 'create'])->name('create');
        Route::post('/', [InscriptionTransportController::class, 'store'])->name('store');
        Route::get('/{inscription}', [InscriptionTransportController::class, 'show'])->name('show');
        Route::get('/{inscription}/edit', [InscriptionTransportController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{inscription}', [InscriptionTransportController::class, 'update'])->name('update');
        Route::delete('/{inscription}', [InscriptionTransportController::class, 'destroy'])->name('destroy');
        Route::put('/{inscription}/statut', [InscriptionTransportController::class, 'statut'])->name('statut');
    });

    // Consultations Infirmerie
    Route::prefix('consultations-infirmerie')->name('consultations-infirmerie.')->group(function () {
        Route::get('/', [ConsultationInfirmerieController::class, 'index'])->name('index');
        Route::get('/create', [ConsultationInfirmerieController::class, 'create'])->name('create');
        Route::post('/', [ConsultationInfirmerieController::class, 'store'])->name('store');
        Route::get('/{consultation}', [ConsultationInfirmerieController::class, 'show'])->name('show');
        Route::get('/{consultation}/edit', [ConsultationInfirmerieController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{consultation}', [ConsultationInfirmerieController::class, 'update'])->name('update');
        Route::delete('/{consultation}', [ConsultationInfirmerieController::class, 'destroy'])->name('destroy');
        Route::put('/{consultation}/statut', [ConsultationInfirmerieController::class, 'statut'])->name('statut');
    });

});
