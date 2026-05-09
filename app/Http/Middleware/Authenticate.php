<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }
    protected function unauthenticated($request, array $guards)
    {
        if ($guards[0]=='api'){
            abort(response()->json(['status'=>false,'message'=>'Veuillez vous connecté avant tout! '], 401));
        }else{
            return route('login');
        }

    }
}
