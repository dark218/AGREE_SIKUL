<?php

namespace Modules\Personnel\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\User;
use App\Services\Generator;
use App\Services\PasswordResetService;
use App\Services\UserLoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Business\Entities\Marchand;
use Modules\Business\Resources\MarchandResource;
use Modules\Parametrage\Entities\Fichier;
use Modules\Parametrage\Entities\Pays;
use Modules\Personnel\Resources\AgentResource;
use Modules\Wallet\Entities\Wallet;
use Modules\ServiceClient\Entities\MoyenPaiement;
use Modules\Wallet\Resources\WalletResource;
use Modules\Wallet\Services\WalletService;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AgentController extends Controller
{
    use ApiResponseTrait;
    /**
     * Connecter un agent
     *
     * @OA\Post(
     *     path="/api/v1/agents/login",
     *     tags={"Agent"},
     *     summary="Connecter un agent",
     *     description="Permet à un agent de se connecter avec son numéro de téléphone et mot de passe",
     *     operationId="loginAgent",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"login","password","pays_id","device_info"},
     *             @OA\Property(property="login", type="string", example="701234567"),
     *             @OA\Property(property="password", type="string", example="12345"),
     *             @OA\Property(property="pays_id", type="integer", example=1),
     *             @OA\Property(
     *                 property="device_info",
     *                 type="object",
     *                 required={"device_id","device_type"},
     *                 @OA\Property(property="device_id", type="string", example="unique_device_id"),
     *                 @OA\Property(property="device_type", type="string", enum={"mobile","web","desktop"}, example="mobile"),
     *                 @OA\Property(property="fcm_token", type="string", nullable=true, example="fcm_token_here")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="token", type="string", example="Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *                 @OA\Property(property="user", ref="#/components/schemas/AgentResource"),
     *                 @OA\Property(property="session_id", type="string", example="random_session_id"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Mot de passe incorrect",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Mot de passe incorrect")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé - compte supprimé, suspendu, bloqué ou non agent",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ce compte est suspendu")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Utilisateur non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Utilisateur non trouvé")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function loginAgent(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'login' => 'required|string',
                'password' => 'required|string|digits:5',
                'pays_id' => 'required|integer|exists:pays,id',
                'device_info' => 'required|array',
                'device_info.device_id' => 'required|string',
                'device_info.device_type' => 'required|string|in:mobile,web,desktop',
                'device_info.fcm_token' => 'nullable|string',
            ], [
                'login.required' => 'Le numéro de téléphone est obligatoire',
                'password.required' => 'Le mot de passe est obligatoire',
                'password.digits' => 'Le mot de passe doit contenir exactement 5 chiffres',
                'pays_id.required' => 'Le pays est obligatoire',
                'device_info.required' => 'Les informations de l\'appareil sont obligatoires',
                'device_info.device_id.required' => 'L\'identifiant de l\'appareil est obligatoire',
                'device_info.device_type.in' => 'Le type d\'appareil doit être mobile, web ou desktop',
            ]);

            // Récupérer le pays pour construire le full_login
            $pays = Pays::find($validated['pays_id']);
            if (!$pays) {
                return $this->errorResponse('Pays non trouvé', 404);
            }
            $fullPhone = $pays->fullPhoneNumber($validated['login']);
            if (!$fullPhone) {
                return $this->errorResponse('Numéro de téléphone invalide', 400);
            }
            // Chercher l'utilisateur (y compris les supprimés) avec ses relations
            $user = User::withTrashed()
                ->where('login', $validated['login'])
                ->where('pays_id', $validated['pays_id'])
                ->first();

            if (!$user) {
                return $this->errorResponse('Utilisateur non trouvé', 404);
            }

            // Vérifier si l'utilisateur a été supprimé (soft delete)
            if ($user->trashed()) {
                return $this->errorResponse('Ce compte a été supprimé', 403);
            }

            // Vérifier si le statut de l'utilisateur est actif
            if ($user->statut !== config('appconstants.user_statut.actif') && $user->statut !== config('appconstants.user_statut.non_actif')) {
                $statutMessage = match($user->statut) {
                    config('appconstants.user_statut.suspendu') => 'Ce compte est suspendu',
                    config('appconstants.user_statut.bloque') => 'Ce compte est bloqué',
                    default => 'Ce compte n\'est pas dans un état valide pour la connexion'
                };
                return $this->errorResponse($statutMessage, 403);
            }

            // Vérifier que c'est bien un agent
            if (!$user->hasRole(config('appconstants.role.agent'))) {
                return $this->errorResponse('Ce compte n\'est pas un agent. Veuillez utiliser l\'application appropriée', 403);
            }

            // Authentification avec full_login ou login selon le cas
            $credentials = [
                'login' => $validated['login'],
                'password' => $validated['password']
            ];

            if (!Auth::attempt($credentials)) {
                return $this->errorResponse('Mot de passe incorrect', 401);
            }

            $user = Auth::user();

            // Récupérer le token FCM
            $fcmToken = $validated['device_info']['fcm_token'] ?? null;

            // Créer un token JWT pour l'utilisateur
            $jwtToken = JWTAuth::fromUser($user);

            // Stocker le token JWT dans la table jwt_tokens
            try {
                \Log::info('Tentative de stockage du token JWT', [
                    'user_id' => $user->id,
                    'jwt_token_exists' => !empty($jwtToken),
                ]);

                // Simuler la mise du token dans la requête pour pouvoir utiliser getPayload
                $originalToken = JWTAuth::getToken();
                JWTAuth::setToken($jwtToken);

                $payload = JWTAuth::getPayload();
                $tokenId = $payload->get('jti');
                $expiresAt = $payload->get('exp');

                // Restaurer le token original s'il y en avait un
                if ($originalToken) {
                    JWTAuth::setToken($originalToken);
                }

                if (!$tokenId || !$expiresAt) {
                    \Log::error('Token JWT invalide - jti ou exp manquant', [
                        'user_id' => $user->id,
                        'payload' => $payload->toArray()
                    ]);
                    throw new \Exception('Token JWT invalide');
                }

                $deviceInfo = $validated['device_info'];
                $userAgent = request()->userAgent();
                $ipAddress = request()->ip();

                // Détecter la plateforme depuis le user agent
                $platform = 'unknown';
                if (stripos($userAgent, 'android') !== false) {
                    $platform = 'Android';
                } elseif (stripos($userAgent, 'ios') !== false || stripos($userAgent, 'iphone') !== false || stripos($userAgent, 'ipad') !== false) {
                    $platform = 'iOS';
                } elseif (stripos($userAgent, 'windows') !== false) {
                    $platform = 'Windows';
                } elseif (stripos($userAgent, 'mac') !== false) {
                    $platform = 'macOS';
                } elseif (stripos($userAgent, 'linux') !== false) {
                    $platform = 'Linux';
                }

                $tokenData = [
                    'token_id' => $tokenId,
                    'user_id' => $user->id,
                    'device_type' => $deviceInfo['device_type'],
                    'device_name' => $deviceInfo['device_id'],
                    'platform' => $platform,
                    'user_agent' => substr($userAgent, 0, 500),
                    'ip_address' => $ipAddress,
                    'last_used_at' => now(),
                    'expires_at' => date('Y-m-d H:i:s', $expiresAt),
                    'is_active' => true,
                    'meta_json' => json_encode([
                        'fcm_token' => $fcmToken,
                        'device_info' => $deviceInfo,
                    ]),
                    'source_system' => 'smilpay',
                    'creation_hostname' => gethostname(),
                    'creation_username' => $user->login,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                \DB::table('jwt_tokens')->insert($tokenData);

                \Log::info('Token JWT stocké avec succès', [
                    'token_id' => $tokenId,
                    'user_id' => $user->id,
                    'device_type' => $deviceInfo['device_type'],
                    'platform' => $platform,
                    'expires_at' => date('Y-m-d H:i:s', $expiresAt),
                ]);

            } catch (\Exception $e) {
                \Log::error('Erreur lors du stockage du token JWT', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // On continue même si le stockage échoue pour ne pas bloquer la connexion
            }
            // Logger la connexion
            UserLoginService::logUserLogin($user, $validated['device_info']);


            // Mettre à jour le token FCM de l'utilisateur si fourni
            if ($fcmToken) {
                \DB::table('users')
                    ->where('id', $user->id)
                    ->update(['fcm_token' => $fcmToken]);
            }

            // Générer un session_id simple pour le frontend
            $sessionId = \Str::random(32);

            // Charger les wallets de l'agent pour la réponse
            $user->setRelation('wallets', Wallet::where('owner_id', $user->id)
                ->where('owner_type', Wallet::OWNER_TYPE_AGENT)
                ->with('paysDevise')
                ->get());

            return $this->successResponse([
                'token' => 'Bearer ' . $jwtToken,
                'user' => new AgentResource($user),
                'session_id' => $sessionId,
                'expires_in' => config('jwt.ttl', 60) * 60, // TTL en secondes
            ], 'Connexion réussie');

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            log_error('AgentController', 'loginAgent', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }

    /**
     * Récupérer les informations de l'agent connecté
     *
     * @OA\Get(
     *     path="/api/v1/agents/me",
     *     tags={"Agent"},
     *     summary="Récupérer les informations de l'agent connecté",
     *     description="Permet à un agent connecté de récupérer toutes ses informations personnelles",
     *     operationId="getAgent",
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations de l'agent récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Informations de l'agent récupérées avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/AgentResource"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Agent non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès interdit",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès réservé aux agents")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Une erreur est survenue")
     *         )
     *     )
     * )
     *
     * @return JsonResponse
     */
    public function getAgent(): JsonResponse
    {
        try {
            $agent = auth()->user();

            // Charger les rôles pour la vérification
            $agent->load('roles');

            // Vérifier si l'utilisateur est bien un agent
            if (!$agent->hasRole(config('appconstants.role.agent'))) {
                return $this->errorResponse('Accès réservé aux agents', 403);
            }

            // Charger les relations
            $agent->load(['pays', 'photoprofile', 'piecerecto', 'pieceverso', 'missions', 'affectations', 'affectationsActives.zone']);

            // Charger les wallets de l'agent
            $agent->setRelation('wallets', Wallet::where('owner_id', $agent->id)
                ->where('owner_type', Wallet::OWNER_TYPE_AGENT)
                ->with('paysDevise')
                ->get());

            return $this->successResponse(new AgentResource($agent), 'Informations de l\'agent récupérées avec succès');

        } catch (\Throwable $e) {
            log_error('AgentController', 'getAgent', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }


    /**
     * Mettre à jour le profil de l'agent connecté
     *
     * @OA\Patch(
     *     path="/api/v1/agents/update",
     *     summary="Mettre à jour le profil de l'agent connecté",
     *     description="Ce point d'accès met à jour les informations du profil agent connecté.",
     *     tags={"Agent"},
     *     operationId="updateAgent",
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom","prenoms","date_naissance","lieu_naissance","sexe"},
     *             @OA\Property(property="nom", type="string", example="Doe"),
     *             @OA\Property(property="prenoms", type="string", example="John"),
     *             @OA\Property(property="date_naissance", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="lieu_naissance", type="string", example="Abidjan"),
     *             @OA\Property(property="sexe", type="string", enum={"M","F"}, example="M"),
     *             @OA\Property(property="email", type="string", format="email", nullable=true, example="john@example.com"),
     *             @OA\Property(property="adresse", type="string", nullable=true, example="123 Rue Principale")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profil mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profil mis à jour avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/AgentResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Agent non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès réservé aux agents")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAgent(Request $request): JsonResponse
    {
        try {
            $agent = Auth::user();

            if (!$agent) {
                return $this->unauthorizedResponse('Agent non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle agent
            if (!$agent->hasRole(config('appconstants.role.agent'))) {
                return $this->unauthorizedResponse('Accès réservé aux agents');
            }

            $validated = $request->validate([
                'nom' => 'required|string|max:255',
                'prenoms' => 'required|string|max:255',
                'date_naissance' => 'required|date|before:today',
                'lieu_naissance' => 'required|string|max:255',
                'sexe' => 'required|in:M,F',
                'email' => 'nullable|email|max:255|unique:users,email,' . $agent->id,
                'adresse' => 'nullable|string|max:500',
            ]);

            $agent->update($validated);

            // Charger les relations pour la réponse
            $agent->load(['pays', 'photoprofile', 'piecerecto', 'pieceverso', 'missions', 'affectations', 'affectationsActives.zone']);

            return $this->successResponse(new AgentResource($agent), 'Profil mis à jour avec succès');

        } catch (ValidationException $e) {
            return $this->validationResponse($e->errors());
        } catch (\Throwable $e) {
            log_error("AgentController","updateAgent",$e->getMessage());
            return $this->serverErrorResponse('Une erreur est survenue lors de la mise à jour du profil');
        }
    }

    /**
     * Mettre à jour la photo de profil de l'agent connecté
     *
     * @OA\Post(
     *     path="/api/v1/agents/update-profile-photo",
     *     tags={"Agent"},
     *     summary="Mettre à jour la photo de profil de l'agent connecté",
     *     description="Permet à un agent connecté de mettre à jour sa photo de profil",
     *     security={{"bearer_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Formulaire multipart pour l'upload de la photo de profil",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"photoprofile"},
     *                 @OA\Property(
     *                     property="photoprofile",
     *                     type="string",
     *                     format="binary",
     *                     description="Fichier image (JPEG, PNG, JPG, GIF, SVG) - Max 2MB"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photo de profil mise à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Photo de profil mise à jour avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="client", ref="#/components/schemas/AgentResource")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Agent non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès réservé aux agents")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur serveur interne")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfilePhoto(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'photoprofile' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ], [
                'photoprofile.required' => 'La photo de profil est obligatoire',
                'photoprofile.image' => 'Le fichier doit être une image',
                'photoprofile.mimes' => 'Les formats autorisés sont: jpeg, png, jpg, gif, svg',
                'photoprofile.max' => 'La taille maximale autorisée est de 2MB',
            ]);

            $agent = Auth::user();

            if (!$agent) {
                return $this->unauthorizedResponse('Agent non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle agent
            if (!$agent->hasRole(config('appconstants.role.agent'))) {
                return $this->unauthorizedResponse('Accès réservé aux agents');
            }

            DB::beginTransaction();

            // Configuration des champs de fichiers
            $fileFields = [
                'photoprofile' => 'photoprofile_id',
            ];

            $newFileIds = [];

            foreach ($fileFields as $inputName => $dbField) {
                if ($request->hasFile($inputName)) {
                    // Supprimer l'ancien fichier s'il existe
                    $oldFileId = $agent->{$dbField};
                    if ($oldFileId) {
                        $oldFile = Fichier::find($oldFileId);
                        if ($oldFile) {
                            $oldFilePath = public_path($oldFile->source);
                            if (file_exists($oldFilePath)) {
                                unlink($oldFilePath);
                            }
                            $oldFile->delete();
                        }
                    }

                    // Gérer le nouveau fichier
                    $file = $request->file($inputName);
                    $fileName = time() . '-' . \Str::random(10) . '.' . $file->extension();
                    $file->move(public_path('images'), $fileName);

                    $f = Fichier::create([
                        'nom' => $fileName,
                        'source' => 'images/' . $fileName,
                    ]);

                    $newFileIds[$dbField] = $f->id;
                }
            }

            // Mettre à jour l'agent avec les nouveaux fichiers
            foreach ($newFileIds as $dbField => $fileId) {
                $agent->update([$dbField => $fileId]);
            }

            DB::commit();

            // Rafraîchir l'agent pour inclure les nouvelles relations
            $agent->refresh();

            // Retourner l'agent mis à jour avec sa nouvelle photo
            return $this->successResponse([
                'agent' => new AgentResource($agent)
            ], 'Photo de profil mise à jour avec succès');

        } catch (ValidationException $e) {
            return $this->validationResponse($e->errors());
        } catch (\Throwable $e) {
            DB::rollback();
            log_error("AgentController","updateProfilePhoto",$e->getMessage());
            \Log::error('Erreur lors de la mise à jour de la photo de profil: ' . $e->getMessage());
            return $this->serverErrorResponse('Une erreur est survenue lors de la mise à jour de la photo de profil');
        }
    }

    /**
     * Mettre à jour les documents de l'agent connecté
     *
     * @OA\Post(
     *     path="/api/v1/agents/update-documents",
     *     tags={"Agent"},
     *     summary="Mettre à jour les documents de l'agent connecté",
     *     description="Permet à un agent connecté de mettre à jour ses pièces d'identité",
     *     security={{"bearer_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Formulaire multipart pour l'upload des documents",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="piecerecto",
     *                     type="string",
     *                     format="binary",
     *                     description="Recto de la pièce d'identité (formats acceptés: JPEG, PNG, JPG, GIF, SVG - taille max: 2MB)"
     *                 ),
     *                 @OA\Property(
     *                     property="pieceverso",
     *                     type="string",
     *                     format="binary",
     *                     description="Verso de la pièce d'identité (formats acceptés: JPEG, PNG, JPG, GIF, SVG - taille max: 2MB)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Documents mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Documents mis à jour avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="client", ref="#/components/schemas/AgentResource")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur serveur interne")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateDocuments(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'piecerecto' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'pieceverso' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ], [
                'piecerecto.image' => 'Le fichier recto doit être une image',
                'piecerecto.mimes' => 'Les formats autorisés pour le recto sont: jpeg, png, jpg, gif, svg',
                'piecerecto.max' => 'La taille maximale autorisée pour le recto est de 2MB',
                'pieceverso.image' => 'Le fichier verso doit être une image',
                'pieceverso.mimes' => 'Les formats autorisés pour le verso sont: jpeg, png, jpg, gif, svg',
                'pieceverso.max' => 'La taille maximale autorisée pour le verso est de 2MB',
            ]);

            // Vérifier qu'au moins un fichier est fourni
            if (!$request->hasFile('piecerecto') && !$request->hasFile('pieceverso')) {
                return $this->validationResponse(['documents' => 'Au moins un document (recto ou verso) doit être fourni']);
            }

            $agent = Auth::user();

            if (!$agent) {
                return $this->unauthorizedResponse('Agent non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle agent
            if (!$agent->hasRole(config('appconstants.role.agent'))) {
                return $this->unauthorizedResponse('Accès réservé aux agents');
            }

            DB::beginTransaction();

            // Configuration des champs de fichiers
            $fileFields = [
                'piecerecto' => 'piecerecto_id',
                'pieceverso' => 'pieceverso_id',
            ];

            $newFileIds = [];

            foreach ($fileFields as $inputName => $dbField) {
                if ($request->hasFile($inputName)) {
                    // Supprimer l'ancien fichier s'il existe
                    $oldFileId = $agent->{$dbField};
                    if ($oldFileId) {
                        $oldFile = Fichier::find($oldFileId);
                        if ($oldFile) {
                            $oldFilePath = public_path($oldFile->source);
                            if (file_exists($oldFilePath)) {
                                unlink($oldFilePath);
                            }
                            $oldFile->delete();
                        }
                    }

                    // Gérer le nouveau fichier
                    $file = $request->file($inputName);
                    $fileName = time() . '-' . \Str::random(10) . '.' . $file->extension();
                    $file->move(public_path('images'), $fileName);

                    $f = Fichier::create([
                        'nom' => $fileName,
                        'source' => 'images/' . $fileName,
                    ]);

                    $newFileIds[$dbField] = $f->id;
                }
            }

            // Mettre à jour l'agent avec les nouveaux fichiers
            foreach ($newFileIds as $dbField => $fileId) {
                $agent->update([$dbField => $fileId]);
            }

            DB::commit();

            // Rafraîchir l'agent pour inclure les nouvelles relations
            $agent->refresh();

            // Retourner l'agent mis à jour avec ses nouveaux documents
            return $this->successResponse([
                'agent' => new AgentResource($agent)
            ], 'Documents mis à jour avec succès');

        } catch (ValidationException $e) {
            return $this->validationResponse($e->errors());
        } catch (\Throwable $e) {
            DB::rollback();
            log_error("AgentController","updateDocuments",$e->getMessage());
            \Log::error('Erreur lors de la mise à jour des documents: ' . $e->getMessage());
            return $this->serverErrorResponse('Une erreur est survenue lors de la mise à jour des documents');
        }
    }

    /**
     * Mettre à jour le mot de passe de l'agent connecté
     *
     * @OA\Post(
     *     path="/api/v1/agents/update-password",
     *     tags={"Agent"},
     *     summary="Mettre à jour le mot de passe de l'agent connecté",
     *     description="Permet à un agent connecté de mettre à jour son mot de passe",
     *     security={{"bearer_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password", "new_password", "new_password_confirmation"},
     *             @OA\Property(
     *                 property="current_password",
     *                 type="string",
     *                 format="password",
     *                 description="Mot de passe actuel",
     *                 example="12345"
     *             ),
     *             @OA\Property(
     *                 property="new_password",
     *                 type="string",
     *                 format="password",
     *                 description="Nouveau mot de passe (min 5 caractères)",
     *                 example="12345"
     *             ),
     *             @OA\Property(
     *                 property="new_password_confirmation",
     *                 type="string",
     *                 format="password",
     *                 description="Confirmation du nouveau mot de passe",
     *                 example="12345"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mot de passe mis à jour avec succès et déconnexion automatique",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="current_password", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="new_password", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié ou mot de passe actuel incorrect",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le mot de passe actuel est incorrect")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur serveur interne")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:5|confirmed',
            ], [
                'current_password.required' => 'Le mot de passe actuel est obligatoire',
                'current_password.string' => 'Le mot de passe actuel doit être une chaîne de caractères',
                'new_password.required' => 'Le nouveau mot de passe est obligatoire',
                'new_password.string' => 'Le nouveau mot de passe doit être une chaîne de caractères',
                'new_password.min' => 'Le nouveau mot de passe doit contenir au moins 5 caractères',
                'new_password.confirmed' => 'La confirmation du nouveau mot de passe ne correspond pas',
            ]);

            $agent = Auth::user();

            if (!$agent) {
                return $this->unauthorizedResponse('Agent non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle agent
            if (!$agent->hasRole(config('appconstants.role.agent'))) {
                return $this->unauthorizedResponse('Accès réservé aux agents');
            }

            // Vérifier que le mot de passe actuel est correct
            if (!\Hash::check($validated['current_password'], $agent->password)) {
                return $this->unauthorizedResponse('Le mot de passe actuel est incorrect');
            }

            // Vérifier que le nouveau mot de passe est différent de l'ancien
            if (\Hash::check($validated['new_password'], $agent->password)) {
                return $this->validationResponse([
                    'new_password' => ['Le nouveau mot de passe doit être différent de l\'ancien mot de passe']
                ]);
            }

            DB::beginTransaction();

            // Mettre à jour le mot de passe
            $agent->update([
                'password' => \Hash::make($validated['new_password']),
            ]);

            DB::commit();

            // Appeler logout pour invalider le token actuel
            return $this->logout($request);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationResponse($e->errors());
        } catch (\Throwable $e) {
            DB::rollback();
            log_error("AgentController","updatePassword",$e->getMessage());
            \Log::error('Erreur lors de la mise à jour du mot de passe: ' . $e->getMessage());
            return $this->serverErrorResponse('Une erreur est survenue lors de la mise à jour du mot de passe');
        }
    }

    /**
     * Récupérer les wallets de l'agent connecté
     *
     * @OA\Get(
     *     path="/api/v1/agents/wallets",
     *     tags={"Agent"},
     *     summary="Récupérer les wallets de l'agent connecté",
     *     description="Retourne la liste des wallets de l'agent authentifié",
     *     security={{"bearer_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Wallets récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Wallets du client"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/WalletResource")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès interdit")
     * )
     *
     * @return JsonResponse
     */
    public function wallets(): JsonResponse
    {
        try {
            $agent = auth()->user();

            // Vérifier si l'utilisateur est bien un agent
            if (!$agent->hasRole(config('appconstants.role.agent'))) {
                return $this->errorResponse('Accès réservé aux agents', 403);
            }

            // Utiliser la requête directe pour contourner le problème de relation
            $wallets = Wallet::where('owner_id', $agent->id)
                ->where('owner_type', Wallet::OWNER_TYPE_AGENT)
                ->with('paysDevise')
                ->get();

            return $this->successResponse(
                WalletResource::collection($wallets),
                'Wallets du client'
            );

        } catch (\Throwable $e) {
            log_error('AgentController', 'wallets', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }

    /**
     * Helper method pour les réponses de validation
     */
    private function validationResponse(array $errors): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation',
            'errors' => $errors
        ], 422);
    }

    /**
     * Lister tous les appareils connectés de l'agent
     *
     * @OA\Get(
     *     path="/api/v1/agents/connected-devices",
     *     tags={"Agent"},
     *     summary="Lister tous les appareils connectés",
     *     description="Permet à un agent connecté de voir tous ses appareils actuellement connectés avec leurs informations",
     *     operationId="listConnectedDevicesAgent",
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des appareils connectés",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Appareils connectés récupérés avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="devices",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="device_name", type="string", example="unique_device_id"),
     *                         @OA\Property(property="device_type", type="string", example="mobile"),
     *                         @OA\Property(property="platform", type="string", example="Android"),
     *                         @OA\Property(property="ip_address", type="string", example="192.168.1.100"),
     *                         @OA\Property(property="user_agent", type="string", example="Mozilla/5.0..."),
     *                         @OA\Property(property="last_used_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
     *                         @OA\Property(property="expires_at", type="string", format="date-time", example="2024-02-15T10:30:00Z"),
     *                         @OA\Property(property="is_current_device", type="boolean", example=true),
     *                         @OA\Property(property="is_active", type="boolean", example=true),
     *                         @OA\Property(
     *                             property="meta",
     *                             type="object",
     *                             @OA\Property(property="fcm_token", type="string", nullable=true, example="fcm_token_here"),
     *                             @OA\Property(
     *                                 property="device_info",
     *                                 type="object",
     *                                 @OA\Property(property="device_id", type="string", example="unique_device_id"),
     *                                 @OA\Property(property="device_type", type="string", example="mobile"),
     *                                 @OA\Property(property="fcm_token", type="string", nullable=true, example="fcm_token_here")
     *                             )
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="total_devices", type="integer", example=3),
     *                 @OA\Property(property="active_devices", type="integer", example=2)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Agent non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès réservé aux agents")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Une erreur est survenue lors de la récupération des appareils")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listConnectedDevicesAgent(Request $request): JsonResponse
    {
        try {
            $agent = Auth::user();

            if (!$agent) {
                return $this->unauthorizedResponse('Agent non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle agent
            if (!$agent->hasRole(config('appconstants.role.agent'))) {
                return $this->unauthorizedResponse('Accès réservé aux agents');
            }

            // Récupérer le token actuel pour identifier l'appareil courant
            $currentTokenId = null;
            $currentToken = JWTAuth::getToken();
            if ($currentToken) {
                try {
                    $payload = JWTAuth::getPayload($currentToken);
                    $currentTokenId = $payload->get('jti');
                } catch (\Exception $e) {
                    \Log::warning('Impossible de récupérer le token actuel', [
                        'user_id' => $agent->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Récupérer tous les tokens actifs de l'utilisateur
            $activeTokens = \DB::table('jwt_tokens')
                ->where('user_id', $agent->id)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->orderBy('last_used_at', 'desc')
                ->get();

            $devices = [];
            $activeCount = 0;

            foreach ($activeTokens as $token) {
                $meta = json_decode($token->meta_json, true) ?? [];

                $deviceData = [
                    'id' => $token->id,
                    'device_name' => $token->device_name,
                    'device_type' => $token->device_type,
                    'platform' => $token->platform,
                    'ip_address' => $token->ip_address,
                    'user_agent' => $token->user_agent,
                    'last_used_at' => $token->last_used_at,
                    'expires_at' => $token->expires_at,
                    'is_current_device' => $token->token_id === $currentTokenId,
                    'is_active' => $token->is_active,
                    'meta' => [
                        'fcm_token' => $meta['fcm_token'] ?? null,
                        'device_info' => $meta['device_info'] ?? [],
                    ],
                ];

                $devices[] = $deviceData;

                if ($token->is_active) {
                    $activeCount++;
                }
            }

            \Log::info('Appareils connectés récupérés', [
                'user_id' => $agent->id,
                'total_devices' => count($devices),
                'active_devices' => $activeCount,
                'current_device_id' => $currentTokenId,
            ]);

            return $this->successResponse([
                'devices' => $devices,
                'total_devices' => count($devices),
                'active_devices' => $activeCount,
            ], 'Appareils connectés récupérés avec succès');

        } catch (TokenInvalidException $e) {
            return $this->unauthorizedResponse('Token invalide');
        } catch (TokenExpiredException $e) {
            return $this->unauthorizedResponse('Token expiré');
        } catch (\Throwable $e) {
            log_error("AgentController","listConnectedDevicesAgent",$e->getMessage());
            \Log::error('Erreur lors de la récupération des appareils connectés: ' . $e->getMessage());
            return $this->serverErrorResponse('Une erreur est survenue lors de la récupération des appareils');
        }
    }

    /**
     * Déconnecter l'agent connecté
     *
     * @OA\Post(
     *     path="/api/v1/agents/logout",
     *     tags={"Agent"},
     *     summary="Déconnecter l'agent connecté",
     *     description="Permet à un agent connecté de se déconnecter et de révoquer son token d'accès",
     *     operationId="logout",
     *     security={{"bearer_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Agent non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès réservé aux agents")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur serveur interne")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $agent = Auth::user();

            if (!$agent) {
                return $this->unauthorizedResponse('Agent non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle agent
            if (!$agent->hasRole(config('appconstants.role.agent'))) {
                return $this->unauthorizedResponse('Accès réservé aux agents');
            }

            // Invalider le token JWT actuel
            $token = JWTAuth::getToken();
            if ($token) {
                $payload = JWTAuth::getPayload($token);
                $tokenId = $payload->get('jti');
                $expiresAt = $payload->get('exp');

                // Vérifier si le token est déjà dans la blacklist
                $alreadyBlacklisted = \DB::table('jwt_blacklist')
                    ->where('token', $tokenId)
                    ->exists();

                if (!$alreadyBlacklisted) {
                    // Ajouter manuellement à la blacklist
                    \DB::table('jwt_blacklist')->insert([
                        'token' => $tokenId,
                        'user_id' => $agent->id,
                        'expires_at' => date('Y-m-d H:i:s', $expiresAt),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Désactiver le token dans jwt_tokens s'il existe
                \DB::table('jwt_tokens')
                    ->where('token_id', $tokenId)
                    ->where('user_id', $agent->id)
                    ->update([
                        'is_active' => false,
                        'updated_at' => now(),
                    ]);

                \Log::info('Token traité', [
                    'token_id' => $tokenId,
                    'user_id' => $agent->id,
                    'blacklisted' => !$alreadyBlacklisted
                ]);
            } else {
                \Log::warning('Aucun token trouvé pour l\'invalidation');
            }

            return $this->successResponse([], 'Déconnexion réussie');

        } catch (TokenInvalidException $e) {
            return $this->unauthorizedResponse('Token invalide');
        } catch (TokenExpiredException $e) {
            return $this->unauthorizedResponse('Token expiré');
        } catch (\Throwable $e) {
            log_error("AgentController","logout",$e->getMessage());
            \Log::error('Erreur lors de la déconnexion: ' . $e->getMessage());
            return $this->serverErrorResponse('Une erreur est survenue lors de la déconnexion');
        }
    }

    /**
     * Déconnecter tous les appareils de l'agent connecté
     *
     * @OA\Post(
     *     path="/api/v1/agents/logout-all-devices",
     *     tags={"Agent"},
     *     summary="Déconnecter tous les appareils connectés",
     *     description="Permet à un agent connecté de révoquer tous ses tokens d'accès et de déconnecter tous ses appareils",
     *     operationId="logoutAllDevicesAgent",
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion de tous les appareils réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tous les appareils ont été déconnectés avec succès"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="devices_revoked", type="integer", example=3))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Agent non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès réservé aux agents")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Une erreur est survenue lors de la déconnexion des appareils")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logoutAllDevicesAgent(Request $request): JsonResponse
    {
        try {
            $agent = Auth::user();

            if (!$agent) {
                return $this->unauthorizedResponse('Agent non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle agent
            if (!$agent->hasRole(config('appconstants.role.agent'))) {
                return $this->unauthorizedResponse('Accès réservé aux agents');
            }

            // Récupérer tous les tokens JWT actifs pour cet utilisateur
            $tokensRevoked = 0;

            // 1. Récupérer tous les tokens JWT actifs depuis jwt_tokens
            $activeTokens = \DB::table('jwt_tokens')
                ->where('user_id', $agent->id)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->get();

            foreach ($activeTokens as $token) {
                // Vérifier si le token est déjà dans la blacklist
                $alreadyBlacklisted = \DB::table('jwt_blacklist')
                    ->where('token', $token->token_id)
                    ->exists();

                if (!$alreadyBlacklisted) {
                    // Ajouter chaque token à la blacklist
                    \DB::table('jwt_blacklist')->insert([
                        'token' => $token->token_id,
                        'user_id' => $agent->id,
                        'expires_at' => $token->expires_at,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $tokensRevoked++;
                }

                // Désactiver le token dans jwt_tokens
                \DB::table('jwt_tokens')
                    ->where('id', $token->id)
                    ->update([
                        'is_active' => false,
                        'updated_at' => now(),
                    ]);
            }

            // 2. Invalider le token JWT actuel s'il existe et n'est pas déjà dans jwt_tokens
            $currentToken = JWTAuth::getToken();
            if ($currentToken) {
                try {
                    $payload = JWTAuth::getPayload($currentToken);
                    $tokenId = $payload->get('jti');
                    $expiresAt = $payload->get('exp');

                    // Vérifier si le token est déjà dans jwt_tokens
                    $tokenExists = \DB::table('jwt_tokens')
                        ->where('token_id', $tokenId)
                        ->where('user_id', $agent->id)
                        ->exists();

                    // Vérifier si le token est déjà dans la blacklist
                    $alreadyBlacklisted = \DB::table('jwt_blacklist')
                        ->where('token', $tokenId)
                        ->exists();

                    if (!$tokenExists && !$alreadyBlacklisted) {
                        // Ajouter à la blacklist
                        \DB::table('jwt_blacklist')->insert([
                            'token' => $tokenId,
                            'user_id' => $agent->id,
                            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $tokensRevoked++;
                    }
                } catch (\Exception $e) {
                    \Log::warning('Impossible de récupérer le payload du token actuel', [
                        'user_id' => $agent->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // 3. Supprimer les sessions actives si elles existent
            \DB::table('sessions')
                ->where('user_id', $agent->id)
                ->delete();

            // 4. Mettre à jour le FCM token à null pour forcer la déconnexion push
            $agent->update(['fcm_token' => null]);

            \Log::info('Déconnexion de tous les appareils', [
                'user_id' => $agent->id,
                'tokens_revoked' => $tokensRevoked
            ]);

            return $this->successResponse([
                'devices_revoked' => $tokensRevoked
            ], 'Tous les appareils ont été déconnectés avec succès');

        } catch (TokenInvalidException $e) {
            return $this->unauthorizedResponse('Token invalide');
        } catch (TokenExpiredException $e) {
            return $this->unauthorizedResponse('Token expiré');
        } catch (\Throwable $e) {
            log_error("AgentController","logoutAllDevicesAgent",$e->getMessage());
            \Log::error('Erreur lors de la déconnexion des appareils: ' . $e->getMessage());
            return $this->serverErrorResponse('Une erreur est survenue lors de la déconnexion des appareils');
        }
    }


    /**
     * Créer un marchand
     *
     * @OA\Post(
     *     path="/api/v1/agents/create-marchand",
     *     tags={"Agent"},
     *     summary="Créer un nouveau marchand",
     *     description="Permet à un agent de créer un nouveau compte marchand",
     *     security={{"bearer_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Formulaire multipart pour la création d'un marchand avec fichiers",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"raison_sociale","type","nom","prenoms","email","tel"},
     *                 @OA\Property(property="raison_sociale", type="string", maxLength=255, example="AUCHAN"),
     *                 @OA\Property(property="identifiant_fiscal", type="string", maxLength=100, nullable=true, example="AUCHAN_CI001"),
     *                 @OA\Property(property="type", type="string", enum={"informel","boutique","grande_surface"}, example="grande_surface"),
     *                 @OA\Property(property="nom", type="string", maxLength=255, example="TANOH"),
     *                 @OA\Property(property="prenoms", type="string", maxLength=255, example="VINCENT"),
     *                 @OA\Property(property="email", type="string", format="email", maxLength=255, example="mr.tanoh.vincent@gmail.com"),
     *                 @OA\Property(property="tel", type="string", maxLength=255, example="0747780473"),
     *                 @OA\Property(property="type_piece", type="string", enum={"passport","cni","pc","ai"}, nullable=true, example="cni"),
     *                 @OA\Property(property="numero_piece", type="string", nullable=true, example="123456789"),
     *                 @OA\Property(property="date_delivrance", type="string", format="date", nullable=true, example="2020-01-15"),
     *                 @OA\Property(property="date_naissance", type="string", format="date", nullable=true, example="1985-05-20"),
     *                 @OA\Property(property="lieu_delivrance", type="string", nullable=true, example="Abidjan"),
     *                 @OA\Property(property="lieu_naissance", type="string", nullable=true, example="Abidjan"),
     *                 @OA\Property(property="pays_id", type="integer", nullable=true, example=8),
     *                 @OA\Property(
     *                     property="photoprofile_id",
     *                     type="string",
     *                     format="binary",
     *                     description="Photo de profil (JPEG, PNG, JPG, GIF, SVG - Max 2MB)"
     *                 ),
     *                 @OA\Property(
     *                     property="piecerecto_id",
     *                     type="string",
     *                     format="binary",
     *                     description="Recto de la pièce d'identité (JPEG, PNG, JPG, GIF, SVG - Max 2MB)"
     *                 ),
     *                 @OA\Property(
     *                     property="pieceverso_id",
     *                     type="string",
     *                     format="binary",
     *                     description="Verso de la pièce d'identité (JPEG, PNG, JPG, GIF, SVG - Max 2MB)"
     *                 ),
     *                 @OA\Property(
     *                     property="dfe_id",
     *                     type="string",
     *                     format="binary",
     *                     description="Document fiscal unique (DFE) (JPEG, PNG, JPG, GIF, SVG - Max 2MB)"
     *                 ),
     *                 @OA\Property(
     *                     property="rccm_id",
     *                     type="string",
     *                     format="binary",
     *                     description="Registre de commerce (RCCM) (JPEG, PNG, JPG, GIF, SVG - Max 2MB)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Marchand créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Marchand créé avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="marchand", ref="#/components/schemas/MarchandResource")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createMarchand(Request $request): JsonResponse
    {
        try {
            $agent = auth()->user();

            // Vérifier que l'utilisateur a le rôle agent
            if (!$agent->hasRole(config('appconstants.role.agent'))) {
                return $this->errorResponse('Accès réservé aux agents', 403);
            }

            $paysCurrent = $agent->pays_id;

            $validatedData = $request->validate([
                'raison_sociale' => 'required|string|max:255',
                'identifiant_fiscal' => 'nullable|string|max:100',
                'type' => 'required|in:informel,boutique,grande_surface',
                'nom' => 'required|string|max:255',
                'prenoms' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'tel' => 'required|string|max:255|unique:users,login',
                'type_piece' => 'nullable|in:passport,cni,pc,ai',
                'numero_piece' => 'nullable|string',
                'date_delivrance' => 'nullable|date',
                'date_naissance' => 'nullable|date',
                'lieu_naissance' => 'nullable|string',
                'lieu_delivrance' => 'nullable|string',
                'pays_id' => [
                    $paysCurrent === null ? 'required' : 'nullable',
                    'exists:pays,id',
                ],
            ], [
                'raison_sociale.required' => 'La raison sociale est obligatoire',
                'type.required' => 'Le type de marchand est obligatoire',
                'type.in' => 'Le type doit être informel, boutique ou grande_surface',
                'nom.required' => 'Le nom est obligatoire',
                'prenoms.required' => 'Les prénoms sont obligatoires',
                'email.required' => 'L\'email est obligatoire',
                'email.email' => 'L\'adresse email doit être valide',
                'tel.required' => 'Le numéro de téléphone est obligatoire',
                'tel.unique' => 'Ce numéro de téléphone est déjà utilisé',
            ]);

            DB::beginTransaction();

            $fileFields = ['photoprofile_id', 'piecerecto_id', 'pieceverso_id', 'dfe_id', 'rccm_id'];
            $fileIds = [];

            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $fileName = time() . '-' . Str::random(10) . '.' . $file->extension();
                    $file->move(public_path('images'), $fileName);

                    $fileIds[$field] = Fichier::create([
                        'nom' => $fileName,
                        'source' => 'images/' . $fileName,
                    ])->id;
                }
            }

            $password = collect(range(0, 9))->shuffle()->take(4)->implode('');

            $paysId = $paysCurrent ?? $validatedData['pays_id'];
            $pays = Pays::findOrFail($paysId);
            $indicatif = $pays->code;

            $user = User::create([
                'nom' => $validatedData['nom'],
                'prenoms' => $validatedData['prenoms'],
                'email' => $validatedData['email'],
                'login' => $validatedData['tel'],
                'full_login' => $indicatif . $validatedData['tel'],
                'password' => Hash::make($password),
                'pays_id' => $paysId,
                'uuid' => Generator::uuid(),
                'alias_smil' => Generator::generateAliasSmil($validatedData['nom'], $validatedData['prenoms']),
                'lieu_delivrance' => $validatedData['lieu_delivrance'] ?? null,
                'date_delivrance' => $validatedData['date_delivrance'] ?? null,
                'date_naissance' => $validatedData['date_naissance'] ?? null,
                'lieu_naissance' => $validatedData['lieu_naissance'] ?? null,
                'type_piece' => $validatedData['type_piece'] ?? null,
                'numero_piece' => $validatedData['numero_piece'] ?? null,
                'role' => config('appconstants.role.marchand'),
                'code_owner' => Generator::codeOwner(),
                'qr_data' => Generator::QrCode($validatedData['tel']),
                'photoprofile_id' => $fileIds['photoprofile_id'] ?? null,
                'piecerecto_id' => $fileIds['piecerecto_id'] ?? null,
                'pieceverso_id' => $fileIds['pieceverso_id'] ?? null,
            ]);

            $marchand = Marchand::create([
                'raison_sociale' => $validatedData['raison_sociale'],
                'identifiant_fiscal' => $validatedData['identifiant_fiscal'] ?? null,
                'type' => $validatedData['type'],
                'dfe_id' => $fileIds['dfe_id'] ?? null,
                'rccm_id' => $fileIds['rccm_id'] ?? null,
                'proprietaire_id' => $user->id,
                'create_by' => $agent->id,
            ]);

            $user->assignRole(config('appconstants.role.marchand'));

            // Création des wallets
            WalletService::createWalletsForOwner($marchand->id, 'marchand', $paysId);

            DB::commit();

            // Envoyer le mot de passe par SMS
            PasswordResetService::sendResetLinkSms($user);

            return $this->successResponse([
                'marchand' => new MarchandResource($marchand->fresh(['proprietaire', 'dfe', 'rccm'])),
                'temp_password' => $password
            ], 'Marchand créé avec succès');

        } catch (ValidationException $e) {
            return $this->validationResponse($e->errors());
        } catch (\Throwable $e) {
            DB::rollback();
            log_error('AgentController', 'createMarchand', $e->getMessage());
            \Log::error('Erreur lors de la création du marchand: ' . $e->getMessage());
            return $this->serverErrorResponse('Une erreur est survenue lors de la création du marchand');
        }
    }
}
