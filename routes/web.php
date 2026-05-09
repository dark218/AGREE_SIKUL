<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerificationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Administration\Http\Controllers\ChangePasswordController;
use Modules\Administration\Http\Controllers\FeatureController;
use Modules\Administration\Http\Controllers\ModuleController;
use Modules\Administration\Http\Controllers\PermissionsController;
use Modules\Administration\Http\Controllers\RoleController;
use Modules\Administration\Http\Controllers\UserController;
use Modules\Parametrage\Http\Controllers\DeviseController;
use Modules\Parametrage\Http\Controllers\FournisseurPaiementController;
use Modules\Parametrage\Http\Controllers\PaysController;
use Modules\Parametrage\Http\Controllers\PaysDeviseController;
use Modules\Parametrage\Http\Controllers\ZoneController;
use Modules\Rapport\Http\Controllers\StatistiquesEcoleController;
use Modules\Rapport\Http\Controllers\StatistiquesClassesController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ApprenantPortalController;
use App\Http\Controllers\AdminChatController;


/*
 * |--------------------------------------------------------------------------
 * | Web Routes
 * |--------------------------------------------------------------------------
 */

// Page d'accueil (Welcome) - utilise app-auth template via middleware
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('welcome');

// ============================================
// Routes d'authentification (guests)
// ============================================
Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    // Forgot Password
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])
        ->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->name('password.email');

    // Custom Forgot Password (votre route existante)
    Route::post('/custompassword/forget', [ForgotPasswordController::class, 'sendLinkForgetPassword'])
        ->name('custompassword.forget');

    // Reset Password
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])
        ->name('password.reset');
    Route::get('/custompassword/reset/{token}', [ResetPasswordController::class, 'showResetForm'])
        ->name('custompassword.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])
        ->name('password.update');
    Route::post('/custompassword/update', [ResetPasswordController::class, 'reset'])
        ->name('custompassword.update');

    // Session Expired
    Route::get('/session-expired', function () {
        return Inertia::render('Auth/SessionExpired');
    })->name('session.expired');
});

// ============================================
// Routes authentifiées
// ============================================
Route::middleware(['session_expired', 'auth:web'])->group(function () {
    // Logout
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    // Dashboard / Home
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Paramétrage Généraux
    Route::get('/parametrage-generaux', [\App\Http\Controllers\ParametrageGenerauxController::class, 'index'])->name('parametrage-generaux.index');
    Route::post('/parametrage-generaux', [\App\Http\Controllers\ParametrageGenerauxController::class, 'update'])->name('parametrage-generaux.update');

    // Confirm Password
    Route::get('/confirm-password', [ConfirmPasswordController::class, 'showConfirmForm'])
        ->name('password.confirm');
    Route::post('/confirm-password', [ConfirmPasswordController::class, 'confirm']);

    // Email Verification
    Route::get('/verify-email', [VerificationController::class, 'show'])
        ->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', [VerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [VerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.resend');

    // Profile
//    Route::get('/profile/{user}/edit', function ($user) {
//        return Inertia::render('Dashboard/Profile', [
//            'user' => \App\Models\User::findOrFail($user),
//            'menu' => 'profile'
//        ]);
//    })->name('users.editprofile');

    // ============================================
    // Routes Notifications
    // ============================================
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])
            ->name('notifications.index');
        Route::get('/unread-count', [NotificationController::class, 'getUnreadCount'])
            ->name('notifications.unread-count');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])
            ->name('notifications.mark-all-read');
    });

    Route::prefix('notification')->group(function () {
        Route::get('/{id}', [NotificationController::class, 'show'])
            ->name('notification.show');
        Route::post('/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])
            ->name('notification.mark-as-read');
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])
            ->name('notification.mark-all-as-read');
        Route::post('/clear-all', [NotificationController::class, 'clearAll'])
            ->name('notification.clear-all');
    });

    // ============================================
    // Routes Transactions (exemple)
    // ============================================
    Route::get('/transactions', function () {
        return Inertia::render('Dashboard/Transactions', [
            'menu' => 'transactions'
        ]);
    })->name('user.transactions.index');

    // ============================================
    // Routes Rapport (Statistiques)
    // ============================================
    Route::prefix('rapport')->name('rapport.')->group(function () {
        // Statistiques Ecole
        Route::prefix('statistiques-ecole')->name('statistiques-ecole.')->group(function () {
            Route::get('/', [StatistiquesEcoleController::class, 'index'])->name('index');
            Route::get('/create', [StatistiquesEcoleController::class, 'create'])->name('create');
            Route::post('/', [StatistiquesEcoleController::class, 'store'])->name('store');
            Route::get('/{statistiquesEcole}', [StatistiquesEcoleController::class, 'show'])->name('show');
            Route::get('/{statistiquesEcole}/edit', [StatistiquesEcoleController::class, 'edit'])->name('edit');
            Route::put('/{statistiquesEcole}', [StatistiquesEcoleController::class, 'update'])->name('update');
            Route::delete('/{statistiquesEcole}', [StatistiquesEcoleController::class, 'destroy'])->name('destroy');
            Route::put('/{statistiquesEcole}/statut', [StatistiquesEcoleController::class, 'statut'])->name('statut');
        });

        // Statistiques Classes
        Route::prefix('statistiques-classes')->name('statistiques-classes.')->group(function () {
            Route::get('/', [StatistiquesClassesController::class, 'index'])->name('index');
            Route::get('/create', [StatistiquesClassesController::class, 'create'])->name('create');
            Route::post('/', [StatistiquesClassesController::class, 'store'])->name('store');
            Route::get('/{statistiquesClasses}', [StatistiquesClassesController::class, 'show'])->name('show');
            Route::get('/{statistiquesClasses}/edit', [StatistiquesClassesController::class, 'edit'])->name('edit');
            Route::put('/{statistiquesClasses}', [StatistiquesClassesController::class, 'update'])->name('update');
            Route::delete('/{statistiquesClasses}', [StatistiquesClassesController::class, 'destroy'])->name('destroy');
            Route::put('/{statistiquesClasses}/statut', [StatistiquesClassesController::class, 'statut'])->name('statut');
        });
    });

    // ============================================
    // Routes Admin Chat (réponses aux apprenants)
    // ============================================
    Route::prefix('admin-chat')->name('admin-chat.')->group(function () {
        Route::get('/', [AdminChatController::class, 'index'])->name('index');
        Route::get('/{userId}', [AdminChatController::class, 'show'])->name('show');
        Route::post('/{userId}/reply', [AdminChatController::class, 'reply'])->name('reply');
    });

    // ============================================
    // Routes Portail Apprenant
    // ============================================
    Route::prefix('apprenant')->name('apprenant.')->group(function () {
        Route::get('/certificats', [ApprenantPortalController::class, 'certificats'])->name('certificats');
        Route::get('/paiements', [ApprenantPortalController::class, 'paiements'])->name('paiements');
        Route::get('/notifications', [ApprenantPortalController::class, 'notifications'])->name('notifications');
        Route::post('/notifications/{id}/read', [ApprenantPortalController::class, 'markNotificationRead'])->name('notifications.read');
        Route::post('/notifications/mark-all-read', [ApprenantPortalController::class, 'markAllNotificationsRead'])->name('notifications.mark-all-read');
        Route::get('/chat', [ApprenantPortalController::class, 'chat'])->name('chat');
        Route::post('/chat/send', [ApprenantPortalController::class, 'sendMessage'])->name('chat.send');
        Route::get('/chat/messages', [ApprenantPortalController::class, 'chatMessages'])->name('chat.messages');
        Route::get('/chat/unread-count', [ApprenantPortalController::class, 'unreadChatCount'])->name('chat.unread-count');
    });
});

Route::get('/change-password', [ChangePasswordController::class, 'index'])->name('password.index');
Route::post('/change-password', [ChangePasswordController::class, 'changePassword'])->name('password.change');

// ============================================
// Routes utilitaires
// ============================================

// Changement de langue
Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'fr'])) {
        session()->put('locale', $locale);
        app()->setLocale($locale);
    }
    return back();
})->name('lang.switch');

// Route FCM (Firebase Cloud Messaging)
Route::post('/fcm/save-token', [NotificationController::class, 'saveFcmToken'])
    ->middleware('auth')
    ->name('fcm.save.token');

// ============================================
// Routes API (JSON responses)
// ============================================
Route::middleware('auth')->prefix('api')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
});

Route::post('/fileholder-upload', [FileController::class, 'storeFile'])->name('fileholder.upload');
Route::post('/fileholder-remove', [FileController::class, 'removeFile'])->name('fileholder.remove');
