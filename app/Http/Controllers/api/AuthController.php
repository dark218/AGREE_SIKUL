<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * AuthController API — stub minimal pour AGREE SIKUL.
 *
 * Les méthodes commerciales (login marchand, agent, client, wallet) ont été retirées.
 * À implémenter ici : login parents, enseignants, apprenants pour l'app mobile école.
 */
class AuthController extends Controller
{
    use ApiResponseTrait;
}
