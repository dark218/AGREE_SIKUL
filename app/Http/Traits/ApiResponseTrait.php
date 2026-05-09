<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    /**
     * Retourner une réponse de succès
     *
     * @param mixed $data
     * @param string $message
     * @param int $status
     * @return JsonResponse
     */
    protected function successResponse($data = null, string $message = '', int $status = 200): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * Retourner une réponse d'erreur
     *
     * @param string $message
     * @param int $status
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message
        ], $status);
    }

    /**
     * Retourner une réponse de validation
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function validationResponse(array $errors, string $message = 'Erreur de validation'): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors
        ], 422);
    }

    /**
     * Retourner une réponse de succès avec pagination
     *
     * @param mixed $data
     * @param string $message
     * @param $pagination
     * @return JsonResponse
     */
    protected function successResponseWithPagination($data, string $message, $pagination): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => $pagination
        ], 200);
    }

    /**
     * Retourner une réponse non trouvé
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function notFoundResponse(string $message = 'Ressource non trouvée'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Retourner une réponse non autorisé
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $message = 'Non autorisé'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Retourner une réponse accès refusé
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function forbiddenResponse(string $message = 'Accès refusé'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Retourner une réponse erreur serveur
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function serverErrorResponse(string $message = 'Erreur serveur interne'): JsonResponse
    {
        return $this->errorResponse($message, 500);
    }

    /**
     * Retourner une réponse créé avec succès
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    protected function createdResponse($data = null, string $message = 'Créé avec succès'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Retourner une réponse aucune donnée
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function noContentResponse(string $message = 'Opération réussie'): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message
        ], 204);
    }
}
