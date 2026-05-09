<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class MultiGuardJwt
{
    public function handle($request, Closure $next, ...$guards)
    {
        foreach ($guards as $guard) {
            Auth::shouldUse($guard);
            try {
                if (Auth::guard($guard)->check()) {
                    return $next($request);
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return response()->json(['message' => 'Veuillez vous connecté avant tout!'], 401);
    }
}
