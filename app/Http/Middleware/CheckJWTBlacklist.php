<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class CheckJWTBlacklist
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Vérifier si le token est présent
            $token = JWTAuth::getToken();
            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Token non fourni'
                ], 401);
            }

            // Parser le token pour vérifier s'il est blacklisté
            $payload = JWTAuth::getPayload($token);

            // Vérifier manuellement si le token est dans la blacklist
            $tokenId = $payload->get('jti');
            $blacklisted = \DB::table('jwt_blacklist')
                ->where('token', $tokenId)
                ->exists();

            if ($blacklisted) {
                return response()->json([
                    'status' => false,
                    'message' => 'Veuillez vous connecter à nouveau'
                ], 401);
            }

            return $next($request);

        } catch (TokenInvalidException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token invalide'
            ], 401);
        } catch (TokenExpiredException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token expiré'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erreur d\'authentification: ' . $e->getMessage()
            ], 401);
        }
    }
}
