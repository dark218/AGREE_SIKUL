<?php

namespace Modules\Business\Http\Controllers\Api;

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
use Illuminate\Support\Str;
use Modules\Business\Entities\Employe;
use Modules\Business\Entities\PointVente;
use Modules\Business\Resources\EmployeResource;
use Modules\Business\Resources\MarchandResource;
use Modules\Business\Resources\PointVenteResource;
use Modules\Parametrage\Entities\Fichier;
use Modules\Parametrage\Entities\Pays;
use Modules\Wallet\Entities\Wallet;
use Modules\Wallet\Resources\WalletResource;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class MarchandController extends Controller
{
    use ApiResponseTrait;

    /**
     * Authentifier un marchand
     *
     * Ce point d'accès authentifie un marchand avec mot de passe et retourne un token d'accès API.
     *
     * @OA\Post(
     *     path="/api/v1/marchand/login-marchand",
     *     tags={"Marchand"},
     *     summary="Authentifier un marchand",
     *     description="Ce point d'accès authentifie un marchand avec mot de passe et retourne un token d'accès API.",
     *     operationId="loginMarchand",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"login","password","pays_id","device_info"},
     *             @OA\Property(property="login", type="string", example="0707070707", description="Numéro de téléphone ou email"),
     *             @OA\Property(property="password", type="string", example="12345", description="Mot de passe"),
     *             @OA\Property(property="pays_id", type="integer", example=8, description="ID du pays"),
     *             @OA\Property(
     *                 property="device_info",
     *                 type="object",
     *                 required={"device_id","device_type"},
     *                 @OA\Property(property="device_id", type="string", example="device_123", description="Identifiant unique de l'appareil"),
     *                 @OA\Property(property="device_type", type="string", enum={"mobile","web","desktop"}, example="mobile"),
     *                 @OA\Property(property="fcm_token", type="string", example="fcm_token_here", description="Token FCM pour les notifications push")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="token", type="string", example="Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *                 @OA\Property(property="user", ref="#/components/schemas/MarchandResource"),
     *                 @OA\Property(property="session_id", type="string", example="abc123def456"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Mot de passe incorrect"),
     *     @OA\Response(response=403, description="Compte suspendu ou bloqué"),
     *     @OA\Response(response=404, description="Utilisateur non trouvé"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function loginMarchand(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'login' => 'required|string',
                'password' => 'required|string',
                'pays_id' => 'required|integer|exists:pays,id',
                'device_info' => 'required|array',
                'device_info.device_id' => 'required|string',
                'device_info.device_type' => 'required|string|in:mobile,web,desktop',
                'device_info.fcm_token' => 'nullable|string',
            ], [
                'login.required' => 'Le numéro de téléphone ou email est obligatoire',
                'password.required' => 'Le mot de passe est obligatoire',
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
                $fullPhone = $validated['login']; // Utiliser le login tel quel si pas un numéro de téléphone
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

            // Vérifier que c'est bien un marchand
            if (!$user->hasRole(config('appconstants.role.marchand'))) {
                return $this->errorResponse('Ce compte n\'est pas un marchand. Veuillez utiliser l\'application appropriée', 403);
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

            // Récupérer le marchand associé à cet utilisateur
            $marchand = $user->marchand;

            if (!$marchand) {
                return $this->errorResponse('Aucun compte marchand associé à cet utilisateur', 404);
            }
            $sessionId = \Str::random(32);
            // Charger les relations nécessaires pour la resource
            $marchand->load([
                'proprietaire',
                'rccm',
                'dfe',
                'validatedByUser',
                'createdByUser',
                'wallets',
                'pointsVente',
                'employes'
            ]);


            return $this->successResponse([
                'token' => 'Bearer ' . $jwtToken,
                'user' => new MarchandResource($marchand),
                'session_id' => $sessionId,
                'expires_in' => config('jwt.ttl', 60) * 60, // TTL en secondes
            ], 'Connexion réussie');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            log_error('AuthController','loginMarchand',$e);
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }

    /**
     * Mettre à jour le profil du marchand
     *
     * Ce point d'accès met à jour les informations du profil marchand connecté.
     *
     * @OA\Post(
     *     path="/api/v1/marchand/update-marchand",
     *     tags={"Marchand"},
     *     summary="Mettre à jour le profil du marchand",
     *     description="Ce point d'accès met à jour les informations du profil marchand connecté.",
     *     operationId="updateMarchand",
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"raison_sociale","type","nom","prenoms","email","tel"},
     *             @OA\Property(property="raison_sociale", type="string", maxLength=255, example="AUCHAN"),
     *             @OA\Property(property="identifiant_fiscal", type="string", maxLength=100, nullable=true, example="AUCHAN_CI001"),
     *             @OA\Property(property="type", type="string", enum={"informel","boutique","grande_surface"}, example="grande_surface"),
     *             @OA\Property(property="nom", type="string", maxLength=255, example="TANOH"),
     *             @OA\Property(property="prenoms", type="string", maxLength=255, example="VINCENT"),
     *             @OA\Property(property="email", type="string", format="email", maxLength=255, example="mr.tanoh.vincent@gmail.com"),
     *             @OA\Property(property="tel", type="string", maxLength=255, example="0747780473"),
     *             @OA\Property(property="type_piece", type="string", enum={"passport","cni","pc","ai"}, nullable=true, example="cni"),
     *             @OA\Property(property="numero_piece", type="string", nullable=true, example="123456789"),
     *             @OA\Property(property="date_delivrance", type="string", format="date", nullable=true, example="2020-01-15"),
     *             @OA\Property(property="date_naissance", type="string", format="date", nullable=true, example="1985-05-20"),
     *             @OA\Property(property="lieu_delivrance", type="string", nullable=true, example="Abidjan"),
     *             @OA\Property(property="lieu_naissance", type="string", nullable=true, example="Abidjan"),
     *             @OA\Property(property="pays_id", type="integer", nullable=true, example=8)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profil mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profil mis à jour avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/MarchandResource")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=404, description="Marchand non trouvé"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function updateMarchand(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // Vérifier que l'utilisateur a le rôle marchand
            if (!$user->hasRole(config('appconstants.role.marchand'))) {
                return $this->errorResponse('Accès réservé aux marchands', 403);
            }

            // Récupérer le marchand associé
            $marchand = $user->marchand;
            if (!$marchand) {
                return $this->errorResponse('Aucun compte marchand associé à cet utilisateur', 404);
            }

            $validated = $request->validate([
                'raison_sociale' => 'required|string|max:255',
                'identifiant_fiscal' => 'nullable|string|max:100',
                'type' => 'required|in:informel,boutique,grande_surface',
                'nom' => 'required|string|max:255',
                'prenoms' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'tel' => 'required|string|max:255|unique:users,login,' . $user->id,
                'type_piece' => 'nullable|in:passport,cni,pc,ai',
                'numero_piece' => 'nullable|string',
                'date_delivrance' => 'nullable|date',
                'date_naissance' => 'nullable|date',
                'lieu_delivrance' => 'nullable|string',
                'lieu_naissance' => 'nullable|string',
                'pays_id' => 'nullable|exists:pays,id',
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

            // Gestion des fichiers uploadés
            $fileFields = [
                'photoprofile_id' => 'user',
                'piecerecto_id' => 'user',
                'pieceverso_id' => 'user',
                'dfe_id' => 'marchand',
                'rccm_id' => 'marchand',
            ];

            $newFileIds = ['user' => [], 'marchand' => []];

            foreach ($fileFields as $inputName => $targetModel) {
                if ($request->hasFile($inputName)) {
                    $file = $request->file($inputName);
                    $fileName = time() . '-' . \Str::random(10) . '.' . $file->extension();
                    $file->move(public_path('images'), $fileName);

                    $f = Fichier::create([
                        'nom' => $fileName,
                        'source' => 'images/' . $fileName,
                    ]);

                    $newFileIds[$targetModel][$inputName] = $f->id;
                }
            }

            // Mise à jour des informations utilisateur
            $paysId = $validated['pays_id'] ?? $user->pays_id;
            $pays = Pays::find($paysId);
            $indicatif = $pays?->code ?? '';

            $userUpdateData = [
                    'nom' => $validated['nom'],
                    'prenoms' => $validated['prenoms'],
                    'email' => $validated['email'],
                    'login' => $validated['tel'],
                    'full_login' => $indicatif . $validated['tel'],
                    'type_piece' => $validated['type_piece'] ?? $user->type_piece,
                    'numero_piece' => $validated['numero_piece'] ?? $user->numero_piece,
                    'date_delivrance' => $validated['date_delivrance'] ?? $user->date_delivrance,
                    'date_naissance' => $validated['date_naissance'] ?? $user->date_naissance,
                    'lieu_delivrance' => $validated['lieu_delivrance'] ?? $user->lieu_delivrance,
                    'lieu_naissance' => $validated['lieu_naissance'] ?? $user->lieu_naissance,
                    'pays_id' => $paysId,
                ] + $newFileIds['user'];

            $user->update($userUpdateData);

            // Mise à jour des informations marchand
            $marchandUpdateData = [
                    'raison_sociale' => $validated['raison_sociale'],
                    'identifiant_fiscal' => $validated['identifiant_fiscal'] ?? $marchand->identifiant_fiscal,
                    'type' => $validated['type'],
                ] + $newFileIds['marchand'];

            $marchand->update($marchandUpdateData);

            DB::commit();

            // Recharger les relations pour la resource
            $marchand->fresh()->load([
                'proprietaire',
                'rccm',
                'dfe',
                'validatedByUser',
                'createdByUser',
                'wallets',
                'pointsVente',
                'employes'
            ]);

            return $this->successResponse([
                'user' => new MarchandResource($marchand)
            ], 'Profil mis à jour avec succès');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            DB::rollback();
            log_error('AuthController','updateMarchand',$e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de la mise à jour', 500);
        }
    }
    /**
     * Afficher les informations du marchand connecté
     *
     * Ce point d'accès retourne les informations complètes du marchand connecté.
     *
     * @OA\Get(
     *     path="/api/v1/marchand/me",
     *     tags={"Marchand"},
     *     summary="Afficher le profil du marchand connecté",
     *     description="Ce point d'accès retourne les informations complètes du marchand connecté.",
     *     operationId="getMarchand",
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Détails du marchand",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Détails du marchand"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/MarchandResource"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=404, description="Marchand non trouvé")
     * )
     */
    public function getMarchand(): JsonResponse
    {
        try {
            $user = auth()->user();

            // Vérifier que l'utilisateur a le rôle marchand
            if (!$user->hasRole(config('appconstants.role.marchand'))) {
                return $this->errorResponse('Accès réservé aux marchands', 403);
            }

            // Récupérer le marchand associé
            $marchand = $user->marchand;
            if (!$marchand) {
                return $this->errorResponse('Aucun compte marchand associé à cet utilisateur', 404);
            }

            // Charger les relations nécessaires
            $marchand->load([
                'proprietaire',
                'rccm',
                'dfe',
                'validatedByUser',
                'createdByUser',
                'wallets',
                'pointsVente',
                'employes'
            ]);

            return $this->successResponse(
                new MarchandResource($marchand),
                'Détails du marchand'
            );

        } catch (\Throwable $e) {
            log_error('MarchandController', 'show', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }

    /**
     * Afficher les wallets du marchand connecté
     *
     * Ce point d'accès retourne la liste des wallets du marchand connecté.
     *
     * @OA\Get(
     *     path="/api/v1/marchand/wallets",
     *     tags={"Marchand"},
     *     summary="Afficher les wallets du marchand",
     *     description="Ce point d'accès retourne la liste des wallets du marchand connecté.",
     *     operationId="walletsMarchand",
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des wallets",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Liste des wallets"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/WalletResource")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=404, description="Marchand non trouvé")
     * )
     */
    public function wallets(): JsonResponse
    {
        try {
            $user = auth()->user();

            // Vérifier que l'utilisateur a le rôle marchand
            if (!$user->hasRole(config('appconstants.role.marchand'))) {
                return $this->errorResponse('Accès réservé aux marchands', 403);
            }

            // Récupérer le marchand associé
            $marchand = $user->marchand;
            if (!$marchand) {
                return $this->errorResponse('Aucun compte marchand associé à cet utilisateur', 404);
            }

            // Charger les wallets
            $marchand->load('wallets');

            return $this->successResponse(
                WalletResource::collection($marchand->wallets),
                'Liste des wallets'
            );

        } catch (\Throwable $e) {
            log_error('MarchandController', 'wallets', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }
    /**
     * Lister tous les appareils connectés du marchand
     *
     * @OA\Get(
     *     path="/api/v1/marchand/connected-devices",
     *     tags={"Marchand"},
     *     summary="Lister tous les appareils connectés",
     *     description="Permet à un marchand connecté de voir tous ses appareils actuellement connectés avec leurs informations",
     *     operationId="listConnectedDevicesMarchand",
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
     *             @OA\Property(property="message", type="string", example="Marchand non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès réservé aux marchands")
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
    public function listConnectedDevicesMarchand(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Marchand non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle marchand
            if (!$user->hasRole(config('appconstants.role.marchand'))) {
                return $this->unauthorizedResponse('Accès réservé aux marchands');
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
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Récupérer tous les tokens actifs de l'utilisateur
            $activeTokens = \DB::table('jwt_tokens')
                ->where('user_id', $user->id)
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
                'user_id' => $user->id,
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
            log_error("MarchandController","listConnectedDevicesMarchand",$e->getMessage());
            \Log::error('Erreur lors de la récupération des appareils connectés: ' . $e->getMessage());
            return $this->serverErrorResponse('Une erreur est survenue lors de la récupération des appareils');
        }
    }


    /**
     * Mettre à jour la photo de profil du marchand connecté
     *
     * @OA\Post(
     *     path="/api/v1/marchand/update-profile-photo",
     *     tags={"Marchand"},
     *     summary="Mettre à jour la photo de profil du marchand connecté",
     *     description="Permet à un marchand connecté de mettre à jour sa photo de profil",
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
     *                     description="Fichier image de la photo de profil (formats acceptés: JPEG, PNG, JPG, GIF, SVG - taille max: 2MB)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photo de profil mise à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Photo de profil mise à jour avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="marchand", ref="#/components/schemas/MarchandResource")
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

            $marchand = Auth::user();

            if (!$marchand) {
                return $this->unauthorizedResponse('Marchand non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle marchand
            if (!$marchand->hasRole(config('appconstants.role.marchand'))) {
                return $this->unauthorizedResponse('Accès réservé aux marchands');
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
                    $oldFileId = $marchand->{$dbField};
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

            // Mettre à jour le marchand avec les nouveaux fichiers
            foreach ($newFileIds as $dbField => $fileId) {
                $marchand->update([$dbField => $fileId]);
            }

            DB::commit();

            // Rafraîchir le marchand pour inclure les nouvelles relations
            $marchand->refresh();

            // Retourner le marchand mis à jour avec sa nouvelle photo
            return $this->successResponse([
                'marchand' => new MarchandResource($marchand)
            ], 'Photo de profil mise à jour avec succès');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationResponse($e->errors());
        } catch (\Throwable $e) {
            DB::rollback();
            log_error("MarchandController","updateProfilePhoto",$e->getMessage());
            \Log::error('Erreur lors de la mise à jour de la photo de profil: ' . $e->getMessage());
            return $this->serverErrorResponse('Une erreur est survenue lors de la mise à jour de la photo de profil');
        }
    }

    /**
     * Mettre à jour les documents d'identité du marchand connecté
     *
     * @OA\Post(
     *     path="/api/v1/marchand/update-documents",
     *     tags={"Marchand"},
     *     summary="Mettre à jour les documents d'identité du marchand connecté",
     *     description="Permet à un marchand connecté de mettre à jour ses documents d'identité (pièce recto et verso)",
     *     security={{"bearer_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Formulaire multipart pour l'upload des documents d'identité",
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
     *                 @OA\Property(property="marchand", ref="#/components/schemas/MarchandResource")
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

            $user = Auth::user();

            $marchand = $user->marchand;

            if (!$marchand) {
                return $this->errorResponse('Aucun compte marchand associé à cet utilisateur', 404);
            }


            if (!$user) {
                return $this->unauthorizedResponse('Marchand non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle marchand
            if (!$user->hasRole(config('appconstants.role.marchand'))) {
                return $this->unauthorizedResponse('Accès réservé aux marchands');
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
                    $oldFileId = $marchand->{$dbField};
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

            // Mettre à jour le marchand avec les nouveaux fichiers
            foreach ($newFileIds as $dbField => $fileId) {
                $user->update([$dbField => $fileId]);
            }

            DB::commit();

            // Rafraîchir le marchand pour inclure les nouvelles relations
            $marchand->refresh();

            // Retourner le marchand mis à jour avec ses nouveaux documents
            return $this->successResponse([
                'marchand' => new MarchandResource($marchand)
            ], 'Documents mis à jour avec succès');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationResponse($e->errors());
        } catch (\Throwable $e) {
            DB::rollback();
            log_error("MarchandController","updateDocuments",$e->getMessage());
            \Log::error('Erreur lors de la mise à jour des documents: ' . $e->getMessage());
            return $this->serverErrorResponse('Une erreur est survenue lors de la mise à jour des documents');
        }
    }

    /**
     * Mettre à jour le mot de passe du marchand connecté
     *
     * @OA\Post(
     *     path="/api/v1/marchand/update-password",
     *     tags={"Marchand"},
     *     summary="Mettre à jour le mot de passe du marchand connecté",
     *     description="Permet à un marchand connecté de mettre à jour son mot de passe",
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

            $user = Auth::user();

            $marchand = $user->marchand;

            if (!$marchand) {
                return $this->errorResponse('Aucun compte marchand associé à cet utilisateur', 404);
            }

            if (!$user) {
                return $this->unauthorizedResponse('Marchand non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle marchand
            if (!$user->hasRole(config('appconstants.role.marchand'))) {
                return $this->unauthorizedResponse('Accès réservé aux marchands');
            }

            // Vérifier que le mot de passe actuel est correct
            if (!\Hash::check($validated['current_password'], $user->password)) {
                return $this->unauthorizedResponse('Le mot de passe actuel est incorrect');
            }

            // Vérifier que le nouveau mot de passe est différent de l'ancien
            if (\Hash::check($validated['new_password'], $user->password)) {
                return $this->validationResponse([
                    'new_password' => ['Le nouveau mot de passe doit être différent de l\'ancien mot de passe']
                ]);
            }

            DB::beginTransaction();

            // Mettre à jour le mot de passe
            $user->update([
                'password' => \Hash::make($validated['new_password']),
            ]);

            DB::commit();

            // Appeler logout pour invalider le token actuel
            return $this->logout($request);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationResponse($e->errors());
        } catch (\Throwable $e) {
            DB::rollback();
            log_error("MarchandController","updatePassword",$e->getMessage());
            \Log::error('Erreur lors de la mise à jour du mot de passe: ' . $e->getMessage());
            return $this->serverErrorResponse('Une erreur est survenue lors de la mise à jour du mot de passe');
        }
    }

    /**
     * Déconnecter le marchand connecté
     *
     * @OA\Post(
     *     path="/api/v1/marchand/logout",
     *     tags={"Marchand"},
     *     summary="Déconnecter le marchand connecté",
     *     description="Permet à un marchand connecté de se déconnecter et de révoquer son token d'accès",
     *     security={{"bearer_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Marchand non authentifié")
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
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Marchand non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle marchand
            if (!$user->hasRole(config('appconstants.role.marchand'))) {
                return $this->unauthorizedResponse('Accès réservé aux marchands');
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
                        'user_id' => $user->id,
                        'expires_at' => date('Y-m-d H:i:s', $expiresAt),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Désactiver le token dans jwt_tokens s'il existe
                \DB::table('jwt_tokens')
                    ->where('token_id', $tokenId)
                    ->where('user_id', $user->id)
                    ->update([
                        'is_active' => false,
                        'updated_at' => now(),
                    ]);

                \Log::info('Token traité', [
                    'token_id' => $tokenId,
                    'user_id' => $user->id,
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
            log_error("MarchandController","logout",$e->getMessage());
            \Log::error('Erreur lors de la déconnexion: ' . $e->getMessage());
            return $this->serverErrorResponse('Une erreur est survenue lors de la déconnexion');
        }
    }

    /**
     * Déconnecter tous les appareils du marchand connecté
     *
     * @OA\Post(
     *     path="/api/v1/marchand/logout-all-devices",
     *     tags={"Marchand"},
     *     summary="Déconnecter tous les appareils connectés",
     *     description="Permet à un marchand connecté de révoquer tous ses tokens d'accès et de déconnecter tous ses appareils",
     *     operationId="logoutAllDevicesMarchand",
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
     *             @OA\Property(property="message", type="string", example="Marchand non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès réservé aux marchands")
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
    public function logoutAllDevicesMarchand(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Marchand non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle marchand
            if (!$user->hasRole(config('appconstants.role.marchand'))) {
                return $this->unauthorizedResponse('Accès réservé aux marchands');
            }

            // Récupérer tous les tokens JWT actifs pour cet utilisateur
            $tokensRevoked = 0;

            // 1. Récupérer tous les tokens JWT actifs depuis jwt_tokens
            $activeTokens = \DB::table('jwt_tokens')
                ->where('user_id', $user->id)
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
                        'user_id' => $user->id,
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
                        ->where('user_id', $user->id)
                        ->exists();

                    // Vérifier si le token est déjà dans la blacklist
                    $alreadyBlacklisted = \DB::table('jwt_blacklist')
                        ->where('token', $tokenId)
                        ->exists();

                    if (!$tokenExists && !$alreadyBlacklisted) {
                        // Ajouter à la blacklist
                        \DB::table('jwt_blacklist')->insert([
                            'token' => $tokenId,
                            'user_id' => $user->id,
                            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $tokensRevoked++;
                    }
                } catch (\Exception $e) {
                    \Log::warning('Impossible de récupérer le payload du token actuel', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // 3. Supprimer les sessions actives si elles existent
            \DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();

            // 4. Mettre à jour le FCM token à null pour forcer la déconnexion push
            $user->update(['fcm_token' => null]);

            \Log::info('Déconnexion de tous les appareils', [
                'user_id' => $user->id,
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
            log_error("MarchandController","logoutAllDevicesMarchand",$e->getMessage());
            \Log::error('Erreur lors de la déconnexion des appareils: ' . $e->getMessage());
            return $this->serverErrorResponse('Une erreur est survenue lors de la déconnexion des appareils');
        }
    }

    /**
     * Créer un employé (Marchand)
     *
     * @OA\Post(
     *     path="/api/v1/marchand/create-employee",
     *     tags={"Marchand"},
     *     summary="Créer un employé",
     *     description="Permet à un marchand de créer un employé (caissier ou manager)",
     *     operationId="createEmployee",
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"points_vente_id","type_employe","nom","prenoms","tel"},
     *                 @OA\Property(property="points_vente_id", type="integer", example=1, description="ID du point de vente"),
     *                 @OA\Property(property="type_employe", type="string", example="caissier", description="Type d'employé (caissier ou manager)"),
     *                 @OA\Property(property="nom", type="string", example="Doe", description="Nom de l'employé"),
     *                 @OA\Property(property="prenoms", type="string", example="John", description="Prénoms de l'employé"),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com", nullable=true, description="Email de l'employé"),
     *                 @OA\Property(property="tel", type="string", example="0102030405", description="Téléphone de l'employé"),
     *                 @OA\Property(property="type_piece", type="string", example="cni", nullable=true, description="Type de pièce d'identité"),
     *                 @OA\Property(property="numero_piece", type="string", example="123456789", nullable=true, description="Numéro de pièce"),
     *                 @OA\Property(property="date_delivrance", type="string", example="2023-01-01", nullable=true, description="Date de délivrance"),
     *                 @OA\Property(property="date_naissance", type="string", example="1990-01-01", nullable=true, description="Date de naissance"),
     *                 @OA\Property(property="lieu_naissance", type="string", example="Abidjan", nullable=true, description="Lieu de naissance"),
     *                 @OA\Property(property="lieu_delivrance", type="string", example="Abidjan", nullable=true, description="Lieu de délivrance"),
     *                 @OA\Property(property="date_embauche", type="string", example="2023-01-01", nullable=true, description="Date d'embauche"),
     *                 @OA\Property(property="photoprofile", type="string", format="binary", nullable=true, description="Photo de profil"),
     *                 @OA\Property(property="piecerecto", type="string", format="binary", nullable=true, description="Pièce d'identité recto"),
     *                 @OA\Property(property="pieceverso", type="string", format="binary", nullable=true, description="Pièce d'identité verso")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Employé créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Employé créé avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="employe", ref="#/components/schemas/EmployeResource", description="Employé créé")
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
    public function createEmployee(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'points_vente_id' => 'required|exists:points_vente,id',
                'type_employe' => 'required|in:caissier,manager',
                'nom' => 'required|string|max:255',
                'prenoms' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'tel' => 'required|string|max:255|unique:users,login',
                'type_piece' => 'nullable|in:passport,cni,pc,ai',
                'numero_piece' => 'nullable|string',
                'date_delivrance' => 'nullable|date',
                'date_naissance' => 'nullable|date',
                'lieu_naissance' => 'nullable|string',
                'lieu_delivrance' => 'nullable|string',
                'date_embauche' => 'nullable|date',
            ], [
                'points_vente_id.required' => 'Le point de vente est obligatoire',
                'points_vente_id.exists' => 'Le point de vente n\'existe pas',
                'type_employe.required' => 'Le type d\'employé est obligatoire',
                'type_employe.in' => 'Le type d\'employé doit être caissier ou manager',
                'nom.required' => 'Le nom est obligatoire',
                'prenoms.required' => 'Les prénoms sont obligatoires',
                'tel.required' => 'Le téléphone est obligatoire',
                'tel.unique' => 'Ce téléphone est déjà utilisé',
            ]);

            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Marchand non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle marchand
            if (!$user->hasRole(config('appconstants.role.marchand'))) {
                return $this->unauthorizedResponse('Accès réservé aux marchands');
            }

            $marchand = $user->marchand;

            if (!$marchand) {
                return $this->errorResponse('Aucun compte marchand associé à cet utilisateur', 404);
            }

            DB::beginTransaction();

            // Vérifier que le point de vente appartient au marchand
            $pointVente = PointVente::with('marchand.proprietaire.pays')->findOrFail($validatedData['points_vente_id']);

            if ($pointVente->marchand_id !== $marchand->id) {
                return $this->errorResponse('Ce point de vente ne vous appartient pas', 403);
            }

            $fileFields = ['photoprofile_id', 'piecerecto_id', 'pieceverso_id'];
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

            // L'employé prend le pays de son marchand
            $pays = $marchand->proprietaire?->pays;
            $paysId = $pays?->id;
            $indicatif = $pays?->code ?? '';

            $roleCode = $validatedData['type_employe'] === 'caissier' ? config('appconstants.role.caissier') : config('appconstants.role.manager');

            $userEmploye = User::create([
                'nom' => $validatedData['nom'],
                'prenoms' => $validatedData['prenoms'],
                'email' => $validatedData['email'] ?? null,
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
                'role' => $roleCode,
                'code_owner' => Generator::codeOwner(),
                'qr_data' => Generator::QrCode($validatedData['tel']),
                'photoprofile_id' => $fileIds['photoprofile_id'] ?? null,
                'piecerecto_id' => $fileIds['piecerecto_id'] ?? null,
                'pieceverso_id' => $fileIds['pieceverso_id'] ?? null,
            ]);

            $codeEmploye = Generator::generateEmployeeCode(
                $marchand->raison_sociale,
                $pays->iso ?? 'XX',
                $marchand->id
            );

            $employe = Employe::create([
                'points_vente_id' => $validatedData['points_vente_id'],
                'users_id' => $user->id,
                'code_employe' => $codeEmploye,
                'type_employe' => $validatedData['type_employe'],
                'date_embauche' => $validatedData['date_embauche'] ?? now(),
                'create_by' => $user->id,
            ]);

            $userEmploye->assignRole(config('appconstants.role.' . $roleCode));

            DB::commit();

            // Envoyer le mot de passe par SMS
            try {
                PasswordResetService::sendResetLinkSms($userEmploye);
            } catch (\Exception $e) {
                log_error('MarchandController', 'createEmployee', 'Erreur envoi SMS: ' . $e->getMessage());
            }

            // Charger les relations pour la réponse
            $employe->load(['user.pays', 'pointVente']);

            return $this->successResponse([
                'employe' => new EmployeResource($employe)
            ], 'Employé créé avec succès');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationResponse($e->errors());
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('MarchandController', 'createEmployee', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de la création de l\'employé');
        }
    }

    /**
     * Lister les employés du marchand (Marchand)
     *
     * @OA\Get(
     *     path="/api/v1/marchand/employees",
     *     tags={"Marchand"},
     *     summary="Lister les employés",
     *     description="Permet à un marchand de lister tous ses employés avec filtres optionnels",
     *     operationId="getEmployees",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="nom",
     *         in="query",
     *         required=false,
     *         description="Filtrer par nom ou prénoms",
     *         @OA\Schema(type="string", example="John")
     *     ),
     *     @OA\Parameter(
     *         name="tel",
     *         in="query",
     *         required=false,
     *         description="Filtrer par téléphone",
     *         @OA\Schema(type="string", example="0102030405")
     *     ),
     *     @OA\Parameter(
     *         name="code_employe",
     *         in="query",
     *         required=false,
     *         description="Filtrer par code employé",
     *         @OA\Schema(type="string", example="EMP-001")
     *     ),
     *     @OA\Parameter(
     *         name="type_employe",
     *         in="query",
     *         required=false,
     *         description="Filtrer par type d'employé",
     *         @OA\Schema(type="string", example="caissier")
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         required=false,
     *         description="Filtrer par statut",
     *         @OA\Schema(type="string", example="actif")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Nombre d'éléments par page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Numéro de page",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Employés récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Employés récupérés avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="employees", type="array", items=@OA\Property(ref="#/components/schemas/EmployeResource")),
     *                 @OA\Property(property="pagination", type="object", description="Informations de pagination")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getEmployees(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Marchand non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle marchand
            if (!$user->hasRole(config('appconstants.role.marchand'))) {
                return $this->unauthorizedResponse('Accès réservé aux marchands');
            }

            $marchand = $user->marchand;

            if (!$marchand) {
                return $this->errorResponse('Aucun compte marchand associé à cet utilisateur', 404);
            }

            // Paramètres de filtrage
            $nom = $request->input('nom');
            $tel = $request->input('tel');
            $code_employe = $request->input('code_employe');
            $statut = $request->input('statut');
            $type_employe = $request->input('type_employe');
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            $query = Employe::with(['user.pays', 'pointVente'])
                ->whereHas('pointVente', function ($q) use ($marchand) {
                    $q->where('marchand_id', $marchand->id);
                })
                ->orderBy('created_at', 'DESC');

            // Appliquer les filtres
            if (!empty($nom)) {
                $query->whereHas('user', function ($q) use ($nom) {
                    $q->where('nom', 'LIKE', "%{$nom}%")
                        ->orWhere('prenoms', 'LIKE', "%{$nom}%");
                });
            }

            if (!empty($tel)) {
                $query->whereHas('user', function ($q) use ($tel) {
                    $q->where('login', 'LIKE', "%{$tel}%");
                });
            }

            if (!empty($code_employe)) {
                $query->where('code_employe', 'LIKE', "%{$code_employe}%");
            }

            if (!empty($type_employe)) {
                $query->where('type_employe', $type_employe);
            }

            if (!empty($statut)) {
                $query->whereHas('user', function ($q) use ($statut) {
                    $q->where('statut', $statut);
                });
            }

            $employees = $query->paginate($perPage, ['*'], 'page', $page);

            return $this->successResponse([
                'employees' => EmployeResource::collection($employees->items()),
                'pagination' => [
                    'current_page' => $employees->currentPage(),
                    'per_page' => $employees->perPage(),
                    'total' => $employees->total(),
                    'last_page' => $employees->lastPage(),
                    'from' => $employees->firstItem(),
                    'to' => $employees->lastItem(),
                ]
            ], 'Employés récupérés avec succès');

        } catch (\Throwable $e) {
            log_error('MarchandController', 'getEmployees', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de la récupération des employés');
        }
    }

    /**
     * Lister tous les points de vente du marchand (Marchand)
     *
     * @OA\Get(
     *     path="/api/v1/marchand/points-vente",
     *     tags={"Marchand"},
     *     summary="Lister les points de vente",
     *     description="Permet à un marchand de lister tous ses points de vente",
     *     operationId="getPointsVente",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Recherchercher par nom ou code",
     *         @OA\Schema(type="string", example="Boutique principale")
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         required=false,
     *         description="Filtrer par statut",
     *         @OA\Schema(type="string", example="actif")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Nombre d'éléments par page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *     *         in="query",
     *     *         required=false,
     *         description="Numéro de page",
     *     * @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Points de vente récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Points de vente récupérés avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="points_vente", type="array", items=@OA\Property(ref="#/components/schemas/PointVenteResource")),
     *                 @OA\Property(property="pagination", type="object", description="Informations de pagination")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPointsVente(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Marchand non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle marchand
            if (!$user->hasRole(config('appconstants.role.marchand'))) {
                return $this->unauthorizedResponse('Accès réservé aux marchands');
            }

            $marchand = $user->marchand;

            if (!$marchand) {
                return $this->errorResponse('Aucun compte marchand associé à cet utilisateur', 404);
            }

            // Paramètres de filtrage
            $search = $request->input('search');
            $statut = $request->input('statut');
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            $query = PointVente::with(['marchand', 'marchand.proprietaire.pays'])
                ->where('marchand_id', $marchand->id)
                ->orderBy('created_at', 'DESC');

            // Appliquer les filtres
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'LIKE', "%{$search}%")
                        ->orWhere('nom', 'LIKE', "%{$search}%")
                        ->orWhere('adresse', 'LIKE', "%{$search}%");
                });
            }

            if (!empty($statut)) {
                $query->where('statut', $statut);
            }

            $pointsVente = $query->paginate($perPage, ['*'], 'page', $page);

            return $this->successResponse([
                'points_vente' => PointVenteResource::collection($pointsVente->items()),
                'pagination' => [
                    'current_page' => $pointsVente->currentPage(),
                    'per_page' => $pointsVente->perPage(),
                    'total' => $pointsVente->total(),
                    'last_page' => $pointsVente->lastPage(),
                    'from' => $pointsVente->firstItem(),
                    'to' => $pointsVente->lastItem(),
                ]
            ], 'Points de vente récupérés avec succès');

        } catch (\Throwable $e) {
            log_error('MarchandController', 'getPointsVente', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de la récupération des points de vente');
        }
    }
}
