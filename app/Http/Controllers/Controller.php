<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="API AGREE SIKUL",
 *      description="Documentation de l'API pour l'application AGREE SIKUL",
 *      @OA\Contact(
 *          email="mr.tanoh.vincent@gmail.com"
 *     )
 * )
 *
 * @OA\SecurityScheme(
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="bearer_token",
 *     description="Authentification via token JWT. Obtenez un token via les endpoints d'authentification."
 * )
 *
 * @OA\Tag(
 *      name="Global",
 *      description="API endpoints pour les données globales (publiques)"
 *  )
 * @OA\Tag(
 *     name="Authentification",
 *     description="Points d'accès pour l'authentification des utilisateurs"
 * )
 *
 * @OA\Tag(
 *     name="Client",
 *     description="Gestion de l'application cliente"
 * )
 *  @OA\Tag(
 *     name="Apprenant",
 *     description="Gestion des apprenants (école)"
 * )
 *  @OA\Tag(
 *     name="Agent",
 *     description="Gestion de l'application Agent"
 * )
 *  @OA\Tag(
 *     name="POS",
 *     description="Gestion de l'application POS"
 * )
 *
 * @OA\Tag(
 *     name="Test",
 *     description="Endpoints de test pour le développement"
 * )
 *
 * @OA\Tag(
 *     name="Webhooks",
 *     description="Endpoints pour les webhooks externes"
 * )
 */
class Controller extends BaseController
{

    use AuthorizesRequests, ValidatesRequests;

     /**
     * Get translated constants for frontend.
     */
    public function getTranslatedConstants(string $configKey, string $labelKey): array
    {
        $values = config("appconstants.{$configKey}");
        $labels = config("appconstants.{$labelKey}");
        $result = [];

        foreach ($values as $key => $value) {
            $result[] = [
                'value' => $value,
                'label' => isset($labels[$key]) ? __($labels[$key]) : $value,
            ];
        }

        return $result;
    }
}
