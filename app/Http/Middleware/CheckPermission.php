<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @param  mixed  ...$permissions
     * @return mixed
     */
    public function handle($request, Closure $next, ...$permissions)
    {
        // Skip permissions check in development mode
        if (env('DEV_MODE') === true || env('DEV_MODE') === 'true') {
            \Log::info('✓ DEV_MODE enabled - skipping permission check for ' . $request->path());
            return $next($request);
        }

        \Log::info('=== CheckPermission Middleware ===');
        \Log::info('Path: ' . $request->path());
        \Log::info('Required permissions: ' . json_encode($permissions));
        \Log::info('User ID: ' . Auth::id());

        if (Auth::check()) {
            $user = Auth::user();
            \Log::info('User permissions: ' . json_encode($user->getAllPermissions()->pluck('name')->toArray()));

            // Vérifie si l'utilisateur a l'une des permissions requises
            foreach ($permissions as $permission) {
                if ($user->can($permission)) {
                    \Log::info('✓ Permission granted: ' . $permission);
                    return $next($request);
                }
            }
            \Log::warning('✗ Permission denied for user ' . $user->id);
        } else {
            \Log::warning('✗ User not authenticated');
        }

        // Retourne une réponse Inertia vers la page Vue 3
        \Log::error('✗ Rendering Unauthorized error page (403)');
        return Inertia::render('Error/Unauthorized', [
            'message' => __('Vous n\'avez pas la permission requise.')
        ])->toResponse($request)->setStatusCode(403);
    }
}
