<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class SessionExpired
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        \Log::info('🔐 SessionExpired Middleware - Path: ' . $request->path());
        \Log::info('   Auth::check(): ' . (Auth::check() ? 'TRUE' : 'FALSE'));
        \Log::info('   Auth::user(): ' . (Auth::user()?->email ?? 'NULL'));
        \Log::info('   Session ID: ' . session()->getId());

        // Vérifie si l'utilisateur est authentifié
        if (!Auth::check()) {
            // Si l'utilisateur n'est pas authentifié (session expirée), redirige vers la page personnalisée
            \Log::warning('⚠️ SessionExpired: User not authenticated, redirecting to session.expired');
            return redirect()->route('session.expired')->with('message', 'Votre session a expiré. Veuillez vous reconnecter.');
        }

        \Log::info('✅ SessionExpired: User authenticated, proceeding');
        return $next($request);
    }
}
