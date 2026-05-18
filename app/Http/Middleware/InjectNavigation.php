<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware NO-OP — la navigation est gérée par HandleInertiaRequests::getModulesMenu().
 *
 * Ce middleware existe pour préserver la compatibilité de la stack de middlewares
 * mais ne fait rien. À supprimer dans le futur si plus aucune référence dans Kernel.php.
 */
class InjectNavigation
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
