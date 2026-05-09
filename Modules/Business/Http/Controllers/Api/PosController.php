<?php

namespace Modules\Business\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\User;
use App\Services\Generator;
use App\Services\MoneyService;
use App\Services\UserLoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Business\Entities\Caisse;
use Modules\Business\Entities\Employe;
use Modules\Business\Entities\PointVente;
use Modules\Business\Entities\Terminal;
use Modules\Business\Resources\EmployeResource;
use Modules\Parametrage\Entities\Fichier;
use Modules\Parametrage\Entities\Pays;
use Modules\Pos\Entities\SessionCaisse;
use Modules\Pos\Entities\VentePos;
use Tymon\JWTAuth\Facades\JWTAuth;

class PosController extends Controller
{
    use ApiResponseTrait;

    /**
     * Authentifier un pos
     *
     * Ce point d'accès authentifie un pos avec mot de passe et retourne un token d'accès API.
     *
     * @OA\Post(
     *     path="/api/v1/pos/login-pos",
     *     tags={"POS"},
     *     summary="Authentifier un pos",
     *     description="Ce point d'accès authentifie un pos avec mot de passe et retourne un token d'accès API.",
     *     operationId="loginEmploye",
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
     *                 @OA\Property(property="user", ref="#/components/schemas/EmployeResource"),
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
    public function loginPos(Request $request): JsonResponse
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

            // Vérifier que c'est bien un employe
            if ((!$user->hasRole(config('appconstants.role.caissier'))) && (!$user->hasRole(config('appconstants.role.manager')))) {
                return $this->errorResponse('Ce compte n\'est pas un employe. Veuillez utiliser l\'application appropriée', 403);
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

            // Récupérer le employe associé à cet utilisateur
            $employe = $user->employePrincipal;

            if (!$employe) {
                return $this->errorResponse('Aucun compte employe associé à cet utilisateur', 404);
            }
            $sessionId = \Str::random(32);



            return $this->successResponse([
                'token' => 'Bearer ' . $jwtToken,
                'user' => new EmployeResource($employe),
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
            log_error('AuthController','loginPos',$e);
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }

    /**
     * Mettre à jour le profil de l'employé connecté
     *
     * @OA\Post(
     *     path="/api/v1/pos/update-user",
     *     tags={"POS"},
     *     summary="Mettre à jour le profil de l'employé",
     *     description="Permet à un employé connecté de mettre à jour ses informations personnelles et ses fichiers",
     *     operationId="updateUser",
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Formulaire multipart pour la mise à jour du profil employé avec fichiers",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"nom","prenoms","tel"},
     *                 @OA\Property(property="nom", type="string", maxLength=255, example="Doe"),
     *                 @OA\Property(property="prenoms", type="string", maxLength=255, example="John"),
     *                 @OA\Property(property="email", type="string", format="email", maxLength=255, nullable=true, example="john@example.com"),
     *                 @OA\Property(property="tel", type="string", maxLength=255, example="0707070707"),
     *                 @OA\Property(property="type_piece", type="string", enum={"passport","cni","pc","ai"}, nullable=true, example="cni"),
     *                 @OA\Property(property="numero_piece", type="string", nullable=true, example="123456789"),
     *                 @OA\Property(property="date_delivrance", type="string", format="date", nullable=true, example="2020-01-15"),
     *                 @OA\Property(property="date_naissance", type="string", format="date", nullable=true, example="1985-05-20"),
     *                 @OA\Property(property="lieu_delivrance", type="string", nullable=true, example="Abidjan"),
     *                 @OA\Property(property="lieu_naissance", type="string", nullable=true, example="Abidjan"),
     *                 @OA\Property(property="date_embauche", type="string", format="date", nullable=true, example="2022-01-01"),
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
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profil mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profil mis à jour avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/EmployeResource")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=404, description="Employé non trouvé"),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateUser(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Employé non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle caissier ou manager
            if (!$user->hasRole(config('appconstants.role.caissier')) && !$user->hasRole(config('appconstants.role.manager'))) {
                return $this->unauthorizedResponse('Accès réservé aux employés');
            }

            // Récupérer l'employé associé à cet utilisateur
            $employe = $user->employePrincipal;

            if (!$employe) {
                return $this->errorResponse('Aucun compte employé associé à cet utilisateur', 404);
            }

            $validatedData = $request->validate([
                'nom' => 'required|string|max:255',
                'prenoms' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'tel' => 'required|string|max:255|unique:users,login,' . $user->id,
                'type_piece' => 'nullable|in:passport,cni,pc,ai',
                'numero_piece' => 'nullable|string',
                'date_delivrance' => 'nullable|date',
                'date_naissance' => 'nullable|date',
                'lieu_naissance' => 'nullable|string',
                'lieu_delivrance' => 'nullable|string',
                'date_embauche' => 'nullable|date',
            ], [
                'nom.required' => 'Le nom est obligatoire',
                'prenoms.required' => 'Les prénoms sont obligatoires',
                'email.email' => 'L\'adresse email doit être valide',
                'tel.required' => 'Le numéro de téléphone est obligatoire',
                'tel.unique' => 'Ce numéro de téléphone est déjà utilisé',
            ]);

            DB::beginTransaction();

            // Récupérer le point de vente et le marchand pour obtenir le pays
            $pointVente = PointVente::with('marchand.proprietaire.pays')->findOrFail($employe->points_vente_id);
            $marchand = $pointVente->marchand;

            // L'employé prend le pays de son marchand
            $pays = $marchand->proprietaire?->pays;
            $paysId = $pays?->id ?? $user->pays_id;
            $indicatif = $pays?->code ?? '';

            $fileFields = ['photoprofile_id', 'piecerecto_id', 'pieceverso_id'];
            $newFileIds = [];

            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $fileName = time() . '-' . Str::random(10) . '.' . $file->extension();
                    $file->move(public_path('images'), $fileName);

                    $newFileIds[$field] = Fichier::create([
                        'nom' => $fileName,
                        'source' => 'images/' . $fileName,
                    ])->id;
                }
            }

            $user->update([
                'nom' => $validatedData['nom'],
                'prenoms' => $validatedData['prenoms'],
                'email' => $validatedData['email'],
                'login' => $validatedData['tel'],
                'full_login' => $indicatif . $validatedData['tel'],
                'type_piece' => $validatedData['type_piece'],
                'numero_piece' => $validatedData['numero_piece'] ?? $user->numero_piece,
                'date_delivrance' => $validatedData['date_delivrance'] ?? $user->date_delivrance,
                'date_naissance' => $validatedData['date_naissance'] ?? $user->date_naissance,
                'lieu_naissance' => $validatedData['lieu_naissance'] ?? $user->lieu_naissance,
                'lieu_delivrance' => $validatedData['lieu_delivrance'] ?? $user->lieu_delivrance,
                'pays_id' => $paysId,
            ] + $newFileIds);

            $employe->update([
                'date_embauche' => $validatedData['date_embauche'] ?? $employe->date_embauche,
            ]);

            DB::commit();

            return $this->successResponse([
                'user' => new EmployeResource($employe->fresh(['user', 'pointVente', 'marchand']))
            ], 'Profil mis à jour avec succès');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationResponse($e->errors());
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('PosController', 'updateUser', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de la mise à jour du profil');
        }
    }

    /**
     * Récupérer les informations de l'employé connecté
     *
     * @OA\Get(
     *     path="/api/v1/pos/get-employe",
     *     tags={"POS"},
     *     summary="Récupérer le profil de l'employé",
     *     description="Permet à un employé connecté de récupérer ses informations complètes",
     *     operationId="getEmploye",
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profil récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profil récupéré avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/EmployeResource")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=404, description="Employé non trouvé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getEmploye(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Employé non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle caissier ou manager
            if (!$user->hasRole(config('appconstants.role.caissier')) && !$user->hasRole(config('appconstants.role.manager'))) {
                return $this->unauthorizedResponse('Accès réservé aux employés');
            }

            // Récupérer l'employé associé à cet utilisateur
            $employe = $user->employePrincipal;

            if (!$employe) {
                return $this->errorResponse('Aucun compte employé associé à cet utilisateur', 404);
            }

            // Charger les relations nécessaires pour la resource
            $employe->load([
                'user',
                'pointVente',
                'marchand',
                'validatedByUser',
                'createdByUser'
            ]);

            return $this->successResponse([
                'user' => new EmployeResource($employe)
            ], 'Profil récupéré avec succès');

        } catch (\Throwable $e) {
            log_error('PosController', 'getEmploye', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de la récupération du profil');
        }

    }

    /**
     * Récupérer la session active du caissier
     *
     * @OA\Get(
     *     path="/api/v1/pos/current-session",
     *     tags={"POS"},
     *     summary="Récupérer la session active du caissier",
     *     description="Permet à un caissier de récupérer sa session de caisse active (en attente ou ouverte)",
     *     operationId="getCurrentSession",
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Session active récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Session active récupérée avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="session", type="object", nullable=true, description="Session de caisse active",
                    @OA\Property(property="id", type="integer", example=123),
                    @OA\Property(property="reference", type="string", example="SC-001"),
                    @OA\Property(property="point_vente_nom", type="string", example="Auchan Abidjan"),
                    @OA\Property(property="caisse_nom", type="string", example="CAI-001 - Caisse Principale"),
                    @OA\Property(property="caissier_nom", type="string", example="John Doe"),
                    @OA\Property(property="terminal_numero", type="string", example="TERM-001"),
                    @OA\Property(property="date_ouverture", type="string", example="15/01/2025 08:00", nullable=true),
                    @OA\Property(property="date_fermeture", type="string", example="15/01/2025 16:00", nullable=true),
                    @OA\Property(property="fond_ouverture", type="string", example="100 000,00", nullable=true),
                    @OA\Property(property="total_encaisse", type="string", example="150 000,00", nullable=true),
                    @OA\Property(property="total_reel", type="string", example="149 500,00", nullable=true),
                    @OA\Property(property="ecart", type="string", example="-500,00", nullable=true),
                    @OA\Property(property="statut", type="string", example="ouverte"),
                    @OA\Property(property="devise", type="string", example="XOF")
                ),
     *                 @OA\Property(property="devises", type="array", items=@OA\Property(type="string"), example={"XOF", "EUR"})
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=404, description="Aucune session active"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCurrentSession(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Employé non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle caissier
            if (!$user->hasRole(config('appconstants.role.caissier'))) {
                return $this->unauthorizedResponse('Accès réservé aux caissiers');
            }

            if ($user->pays_id == null) {
                return $this->errorResponse('Le pays de l\'utilisateur est requis', 400);
            }

            $employe = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.caissier'))
                ->first();

            if (!$employe) {
                return $this->errorResponse('Aucun compte caissier associé à cet utilisateur', 404);
            }

            // Récupérer la session en attente OU ouverte
            $session = SessionCaisse::with(['caisse', 'caisse.pointVente', 'caissier.user', 'terminal'])
                ->where('caissier_id', $employe->id)
                ->whereIn('statut', [
                    config('appconstants.session_caisse_statut.attente'),
                    config('appconstants.session_caisse_statut.ouverte')
                ])
                ->first();

            // Récupérer les devises autorisées pour le pays
            $devisesAutorisees = [];
            if ($user->pays) {
                $devisesAutorisees = $user->pays
                    ->paysDevises()
                    ->with('devise')
                    ->get()
                    ->map(fn($pd) => $pd->devise->code)
                    ->toArray();
            }

            // Préparer les données de session pour le frontend
            $sessionData = null;
            if ($session) {
                $sessionData = [
                    'id' => $session->id,
                    'reference' => $session->reference,
                    'point_vente_nom' => $session->caisse->pointVente->nom ?? 'N/A',
                    'caisse_nom' => ($session->caisse->code ?? 'N/A') . ' - ' . ($session->caisse->nom ?? 'N/A'),
                    'caissier_nom' => ($session->caissier->user->nom ?? '') . ' ' . ($session->caissier->user->prenoms ?? ''),
                    'terminal_numero' => $session->terminal->numero_serie ?? 'N/A',
                    'date_ouverture' => $session->opened_at?->format('d/m/Y H:i'),
                    'date_fermeture' => $session->closed_at?->format('d/m/Y H:i'),
                    'fond_ouverture' => $session->fond_ouverture_cents !== null ? MoneyService::toDisplay($session->fond_ouverture_cents, $session->devise) : null,
                    'total_encaisse' => $session->total_encaisse_cents !== null ? MoneyService::toDisplay($session->total_encaisse_cents, $session->devise) : null,
                    'total_reel' => $session->total_reel_cents !== null ? MoneyService::toDisplay($session->total_reel_cents, $session->devise) : null,
                    'ecart' => $session->ecart_cents !== null ? MoneyService::toDisplay($session->ecart_cents, $session->devise) : null,
                    'statut' => $session->statut,
                    'devise' => $session->devise,
                ];
            }

            return $this->successResponse([
                'session' => $sessionData,
                'devises' => $devisesAutorisees
            ], 'Session active récupérée avec succès');

        } catch (\Throwable $e) {
            log_error('PosController', 'getCurrentSession', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de la récupération de la session');
        }
    }

    /**
     * Lister tous les appareils connectés (POS)
     *
     * @OA\Get(
     *     path="/api/v1/pos/connected-devices",
     *     tags={"POS"},
     *     summary="Lister tous les appareils connectés",
     *     description="Permet à un employé POS connecté de voir tous ses appareils actuellement connectés avec leurs informations",
     *     operationId="listConnectedDevicesPos",
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Appareils connectés récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Appareils connectés récupérés avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="devices", type="array", items=@OA\Property(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="device_name", type="string", example="iPhone 14 Pro"),
     *                     @OA\Property(property="device_type", type="string", example="mobile"),
     *                     @OA\Property(property="platform", type="string", example="iOS"),
     *                     @OA\Property(property="ip_address", type="string", example="192.168.1.100"),
     *                     @OA\Property(property="user_agent", type="string", example="Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)"),
     *                     @OA\Property(property="last_used_at", type="string", example="15/01/2025 16:30"),
     *                     @OA\Property(property="expires_at", type="string", example="16/01/2025 16:30"),
     *                     @OA\Property(property="is_current_device", type="boolean", example=true),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="meta",
     *                         type="object",
     *                         @OA\Property(property="fcm_token", type="string", nullable=true),
     *                         @OA\Property(property="device_info", type="object", nullable=true)
     *                     )
     *                 )),
     *                 @OA\Property(property="total_devices", type="integer", example=3),
     *                 @OA\Property(property="active_devices", type="integer", example=2)
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
    public function connectedDevices(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Employé POS non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle caissier ou manager
            if (!$user->hasRole(config('appconstants.role.caissier')) && !$user->hasRole(config('appconstants.role.manager'))) {
                return $this->unauthorizedResponse('Accès réservé aux employés POS (caissiers/managers)');
            }

            // Récupérer le token actuel pour identifier l'appareil courant
            $currentTokenId = null;
            $currentToken = JWTAuth::getToken();
            if ($currentToken) {
                try {
                    $payload = JWTAuth::getPayload($currentToken);
                    $currentTokenId = $payload->get('jti');
                } catch (\Exception $e) {
                    log_error('PosController', 'connectedDevices', 'Impossible de récupérer le token actuel: ' . $e->getMessage());
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

            log_error('PosController', 'connectedDevices', 'Appareils connectés récupérés avec succès', [
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

        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return $this->unauthorizedResponse('Token expiré');
        } catch (\Throwable $e) {
            log_error('PosController', 'connectedDevices', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de la récupération des appareils');
        }
    }

    /**
     * Mettre à jour la photo de profil (POS)
     *
     * @OA\Post(
     *     path="/api/v1/pos/update-profile-photo",
     *     tags={"POS"},
     *     summary="Mettre à jour la photo de profil",
     *     description="Permet à un employé POS de mettre à jour sa photo de profil",
     *     operationId="updateProfilePhotoPos",
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"photoprofile"},
     *                 @OA\Property(property="photoprofile", type="string", format="binary", description="Image de profil (jpeg, png, jpg, gif, svg - max 2MB)")
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
     *                 @OA\Property(property="employe", type="object", description="Employé mis à jour")
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

            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Employé POS non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle caissier ou manager
            if (!$user->hasRole(config('appconstants.role.caissier')) && !$user->hasRole(config('appconstants.role.manager'))) {
                return $this->unauthorizedResponse('Accès réservé aux employés POS (caissiers/managers)');
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
                    $oldFileId = $user->{$dbField};
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
                    $fileName = time() . '-' . Str::random(10) . '.' . $file->extension();
                    $file->move(public_path('images'), $fileName);

                    $f = Fichier::create([
                        'nom' => $fileName,
                        'source' => 'images/' . $fileName,
                    ]);

                    $newFileIds[$dbField] = $f->id;
                }
            }

            // Mettre à jour l'utilisateur avec les nouveaux fichiers
            foreach ($newFileIds as $dbField => $fileId) {
                $user->update([$dbField => $fileId]);
            }

            DB::commit();

            // Rafraîchir l'utilisateur pour inclure les nouvelles relations
            $user->refresh();

            // Récupérer l'employé associé pour la réponse
            $employe = Employe::where('users_id', $user->id)->first();
            if ($employe) {
                $employe->load(['user', 'pointVente']);
            }

            return $this->successResponse([
                'employe' => $employe ? new EmployeResource($employe) : null
            ], 'Photo de profil mise à jour avec succès');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationResponse($e->errors());
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('PosController', 'updateProfilePhoto', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de la mise à jour de la photo de profil');
        }
    }

    /**
     * Mettre à jour le mot de passe (POS)
     *
     * @OA\Post(
     *     path="/api/v1/pos/update-password",
     *     tags={"POS"},
     *     summary="Mettre à jour le mot de passe",
     *     description="Permet à un employé POS de mettre à jour son mot de passe",
     *     operationId="updatePasswordPos",
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password","new_password","new_password_confirmation"},
     *             @OA\Property(property="current_password", type="string", example="oldpass123", description="Mot de passe actuel"),
     *             @OA\Property(property="new_password", type="string", example="newpass123", description="Nouveau mot de passe (min 5 caractères)"),
     *             @OA\Property(property="new_password_confirmation", type="string", example="newpass123", description="Confirmation du nouveau mot de passe")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mot de passe mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié ou mot de passe incorrect"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=500, description="Erreur serveur")
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

            if (!$user) {
                return $this->unauthorizedResponse('Employé POS non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle caissier ou manager
            if (!$user->hasRole(config('appconstants.role.caissier')) && !$user->hasRole(config('appconstants.role.manager'))) {
                return $this->unauthorizedResponse('Accès réservé aux employés POS (caissiers/managers)');
            }

            // Vérifier que le mot de passe actuel est correct
            if (!Hash::check($validated['current_password'], $user->password)) {
                return $this->unauthorizedResponse('Le mot de passe actuel est incorrect');
            }

            // Vérifier que le nouveau mot de passe est différent de l'ancien
            if (Hash::check($validated['new_password'], $user->password)) {
                return $this->validationResponse([
                    'new_password' => ['Le nouveau mot de passe doit être différent de l\'ancien mot de passe']
                ]);
            }

            DB::beginTransaction();

            // Mettre à jour le mot de passe
            $user->update([
                'password' => Hash::make($validated['new_password']),
            ]);

            DB::commit();

            // Appeler logout pour invalider le token actuel
            return $this->logout($request);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationResponse($e->errors());
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('PosController', 'updatePassword', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de la mise à jour du mot de passe');
        }
    }

    /**
     * Déconnecter l'employé POS connecté
     *
     * @OA\Post(
     *     path="/api/v1/pos/logout",
     *     tags={"POS"},
     *     summary="Déconnecter l'employé POS",
     *     description="Permet à un employé POS connecté de se déconnecter et de révoquer son token d'accès",
     *     operationId="logoutPos",
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
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
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Employé POS non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle caissier ou manager
            if (!$user->hasRole(config('appconstants.role.caissier')) && !$user->hasRole(config('appconstants.role.manager'))) {
                return $this->unauthorizedResponse('Accès réservé aux employés POS (caissiers/managers)');
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

                log_error('PosController', 'logout', 'Token traité', [
                    'token_id' => $tokenId,
                    'user_id' => $user->id,
                    'blacklisted' => !$alreadyBlacklisted
                ]);
            } else {
                log_error('PosController', 'logout', 'Aucun token trouvé pour l\'invalidation');
            }

            return $this->successResponse([], 'Déconnexion réussie');

        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return $this->unauthorizedResponse('Token invalide');
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return $this->unauthorizedResponse('Token expiré');
        } catch (\Throwable $e) {
            log_error('PosController', 'logout', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de la déconnexion');
        }
    }

    /**
     * Déconnecter tous les appareils de l'employé POS connecté
     *
     * @OA\Post(
     *     path="/api/v1/pos/logout-all-devices",
     *     tags={"POS"},
     *     summary="Déconnecter tous les appareils connectés",
     *     description="Permet à un employé POS connecté de révoquer tous ses tokens d'accès et de déconnecter tous ses appareils",
     *     operationId="logoutAllDevicesPos",
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Tous les appareils déconnectés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tous les appareils ont été déconnectés avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="devices_revoked", type="integer", example=3)
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
    public function logoutAllDevices(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Employé POS non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle caissier ou manager
            if (!$user->hasRole(config('appconstants.role.caissier')) && !$user->hasRole(config('appconstants.role.manager'))) {
                return $this->unauthorizedResponse('Accès réservé aux employés POS (caissiers/managers)');
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
                    log_error('PosController', 'logoutAllDevices', 'Impossible de récupérer le payload du token actuel: ' . $e->getMessage());
                }
            }

            // 3. Supprimer les sessions actives si elles existent
            \DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();

            // 4. Mettre à jour le FCM token à null pour forcer la déconnexion push
            $user->update(['fcm_token' => null]);

            log_error('PosController', 'logoutAllDevices', 'Déconnexion de tous les appareils', [
                'user_id' => $user->id,
                'tokens_revoked' => $tokensRevoked
            ]);

            return $this->successResponse([
                'devices_revoked' => $tokensRevoked
            ], 'Tous les appareils ont été déconnectés avec succès');

        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return $this->unauthorizedResponse('Token invalide');
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return $this->unauthorizedResponse('Token expiré');
        } catch (\Throwable $e) {
            log_error('PosController', 'logoutAllDevices', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de la déconnexion des appareils');
        }
    }

    /**
     * Ouverture de la caisse par le caissier
     *
     * @OA\Post(
     *     path="/api/v1/pos/open-session",
     *     tags={"POS"},
     *     summary="Ouvrir une session de caisse",
     *     description="Permet à un caissier d'ouvrir sa session de caisse avec un fonds de départ",
     *     operationId="openSession",
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_id","fond_ouverture","devise"},
     *             @OA\Property(property="session_id", type="integer", example=123, description="ID de la session à ouvrir"),
     *             @OA\Property(property="fond_ouverture", type="number", format="float", example=100000.50, description="Fonds d'ouverture"),
     *             @OA\Property(property="devise", type="string", example="XOF", description="Devise utilisée")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session ouverte avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Session ouverte avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="session", type="object", description="Session de caisse ouverte",
                    @OA\Property(property="id", type="integer", example=123),
                    @OA\Property(property="reference", type="string", example="SC-001"),
                    @OA\Property(property="point_vente_nom", type="string", example="Auchan Abidjan"),
                    @OA\Property(property="caisse_nom", type="string", example="CAI-001 - Caisse Principale"),
                    @OA\Property(property="caissier_nom", type="string", example="John Doe"),
                    @OA\Property(property="terminal_numero", type="string", example="TERM-001"),
                    @OA\Property(property="date_ouverture", type="string", example="15/01/2025 08:00"),
                    @OA\Property(property="fond_ouverture", type="string", example="100 000,50"),
                    @OA\Property(property="total_encaisse", type="string", example="0,00"),
                    @OA\Property(property="total_reel", type="string", example=null, nullable=true),
                    @OA\Property(property="ecart", type="string", example=null, nullable=true),
                    @OA\Property(property="statut", type="string", example="ouverte"),
                    @OA\Property(property="devise", type="string", example="XOF")
                )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=404, description="Aucune session en attente"),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function openSession(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Employé non authentifié');
            }

            if ($user->pays_id == null) {
                return $this->errorResponse('Le pays de l\'utilisateur est requis', 400);
            }

            // Récupérer les devises autorisées
            $devisesAutorisees = $user->pays
                ->paysDevises()
                ->with('devise')
                ->get()
                ->pluck('devise.code')
                ->toArray();

            // Validation initiale
            $validatedData = $request->validate([
                'session_id' => 'required|exists:sessions_caisse,id',
                'fond_ouverture' => 'required|numeric|min:0',
                'devise' => 'required|in:' . implode(',', $devisesAutorisees),
            ], [
                'session_id.required' => 'L\'ID de la session est obligatoire',
                'session_id.exists' => 'La session n\'existe pas',
                'fond_ouverture.required' => 'Le fonds d\'ouverture est obligatoire',
                'fond_ouverture.numeric' => 'Le fonds d\'ouverture doit être un nombre',
                'fond_ouverture.min' => 'Le fonds d\'ouverture doit être positif',
                'devise.required' => 'La devise est obligatoire',
                'devise.in' => 'La devise n\'est pas autorisée pour ce pays',
            ]);

            // Vérifier que l'utilisateur a le rôle caissier
            if (!$user->hasRole(config('appconstants.role.caissier'))) {
                return $this->unauthorizedResponse('Accès réservé aux caissiers');
            }

            // Vérifier que l'utilisateur est bien caissier
            $employe = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.caissier'))
                ->first();

            if (!$employe) {
                return $this->errorResponse('Aucun compte caissier associé à cet utilisateur', 404);
            }

            // Charger la session
            $session = SessionCaisse::findOrFail($validatedData['session_id']);

            // Vérifier que la session appartient bien à ce caissier
            if ($session->caissier_id !== $employe->id) {
                return $this->errorResponse('Cette session ne vous appartient pas', 403);
            }

            // Vérifier si la session est déjà ouverte
            if ($session->statut === config('appconstants.session_caisse_statut.ouverte')) {
                return $this->errorResponse('Cette session est déjà ouverte', 400);
            }

            // Vérifier si la session est bien en attente
            if ($session->statut !== config('appconstants.session_caisse_statut.attente')) {
                return $this->errorResponse('Cette session n\'est pas en attente d\'ouverture', 400);
            }

            DB::beginTransaction();

            // Convertir le montant en cents avec MoneyService
            $fondOuvertureCents = MoneyService::toDatabase(
                $validatedData['fond_ouverture'],
                $validatedData['devise']
            );

            // Mettre à jour la session
            $session->update([
                'fond_ouverture_cents' => $fondOuvertureCents,
                'statut' => config('appconstants.session_caisse_statut.ouverte'),
                'opened_at' => now(),
                'devise' => $validatedData['devise'],
                'total_encaisse_cents' => 0,
            ]);

            DB::commit();

            // Retourner la session mise à jour
            $session->load(['caisse', 'caisse.pointVente', 'caissier.user', 'terminal']);

            $sessionData = [
                'id' => $session->id,
                'reference' => $session->reference,
                'point_vente_nom' => $session->caisse->pointVente->nom ?? 'N/A',
                'caisse_nom' => ($session->caisse->code ?? 'N/A') . ' - ' . ($session->caisse->nom ?? 'N/A'),
                'caissier_nom' => ($session->caissier->user->nom ?? '') . ' ' . ($session->caissier->user->prenoms ?? ''),
                'terminal_numero' => $session->terminal->numero_serie ?? 'N/A',
                'date_ouverture' => $session->opened_at?->format('d/m/Y H:i'),
                'fond_ouverture' => $session->fond_ouverture_cents !== null ? MoneyService::toDisplay($session->fond_ouverture_cents, $session->devise) : null,
                'total_encaisse' => $session->total_encaisse_cents !== null ? MoneyService::toDisplay($session->total_encaisse_cents, $session->devise) : null,
                'total_reel' => $session->total_reel_cents !== null ? MoneyService::toDisplay($session->total_reel_cents, $session->devise) : null,
                'ecart' => $session->ecart_cents !== null ? MoneyService::toDisplay($session->ecart_cents, $session->devise) : null,
                'statut' => $session->statut,
                'devise' => $session->devise,
            ];

            return $this->successResponse([
                'session' => $sessionData
            ], 'Session ouverte avec succès');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationResponse($e->errors());
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('PosController', 'openSession', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de l\'ouverture de la session');
        }
    }

    /**
     * Fermeture de la caisse par le caissier
     *
     * @OA\Post(
     *     path="/api/v1/pos/close-session",
     *     tags={"POS"},
     *     summary="Fermer une session de caisse",
     *     description="Permet à un caissier de fermer sa session de caisse avec le total en caisse",
     *     operationId="closeSession",
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_id","total_reel"},
     *             @OA\Property(property="session_id", type="integer", example=123, description="ID de la session à fermer"),
     *             @OA\Property(property="total_reel", type="number", format="float", example=150000.75, description="Total réel en caisse"),
     *             @OA\Property(property="commentaire", type="string", maxLength=500, nullable=true, example="Fermeture normale de la journée")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session fermée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Session fermée avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="session", type="object", description="Session de caisse fermée",
                    @OA\Property(property="id", type="integer", example=123),
                    @OA\Property(property="reference", type="string", example="SC-001"),
                    @OA\Property(property="point_vente_nom", type="string", example="Auchan Abidjan"),
                    @OA\Property(property="caisse_nom", type="string", example="CAI-001 - Caisse Principale"),
                    @OA\Property(property="caissier_nom", type="string", example="John Doe"),
                    @OA\Property(property="terminal_numero", type="string", example="TERM-001"),
                    @OA\Property(property="date_ouverture", type="string", example="15/01/2025 08:00"),
                    @OA\Property(property="date_fermeture", type="string", example="15/01/2025 16:00"),
                    @OA\Property(property="fond_ouverture", type="string", example="100 000,00"),
                    @OA\Property(property="total_encaisse", type="string", example="50 000,00"),
                    @OA\Property(property="total_reel", type="string", example="149 500,00"),
                    @OA\Property(property="ecart", type="string", example="-500,00"),
                    @OA\Property(property="statut", type="string", example="fermee"),
                    @OA\Property(property="devise", type="string", example="XOF")
                ),
     *                 @OA\Property(property="ecart", type="string", example="500,25", description="Écart constaté formaté"),
     *                 @OA\Property(property="requires_validation", type="boolean", example=false, description="Indique si une validation manager est requise")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=404, description="Aucune session ouverte"),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function closeSession(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Employé non authentifié');
            }

            // Validation (montant lisible)
            $validatedData = $request->validate([
                'session_id' => 'required|exists:sessions_caisse,id',
                'total_reel' => 'required|numeric|min:0',
                'commentaire' => 'nullable|string|max:500',
            ], [
                'session_id.required' => 'L\'ID de la session est obligatoire',
                'session_id.exists' => 'La session n\'existe pas',
                'total_reel.required' => 'Le total réel est obligatoire',
                'total_reel.numeric' => 'Le total réel doit être un nombre',
                'total_reel.min' => 'Le total réel doit être positif',
            ]);

            // Vérifier que l'utilisateur a le rôle caissier
            if (!$user->hasRole(config('appconstants.role.caissier'))) {
                return $this->unauthorizedResponse('Accès réservé aux caissiers');
            }

            // Vérifier que l'utilisateur est bien caissier
            $employe = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.caissier'))
                ->first();

            if (!$employe) {
                return $this->errorResponse('Aucun compte caissier associé à cet utilisateur', 404);
            }

            // Charger la session
            $session = SessionCaisse::findOrFail($validatedData['session_id']);

            // Vérifier propriétaire
            if ($session->caissier_id !== $employe->id) {
                return $this->errorResponse('Cette session ne vous appartient pas', 403);
            }

            // Vérifier statut
            if ($session->statut !== config('appconstants.session_caisse_statut.ouverte')) {
                return $this->errorResponse('Cette session n\'est pas ouverte', 400);
            }

            // Vérifier s'il existe des ventes en attente sur cette session
            $ventesEnAttente = VentePos::where('sessions_caisse_id', $session->id)
                ->where('statut', config('appconstants.statut_vente_pos.en_attente'))
                ->exists();

            if ($ventesEnAttente) {
                return $this->errorResponse('Impossible de fermer la session : des ventes sont en attente', 400);
            }

            DB::beginTransaction();

            // Conversion du montant réel selon la devise de la session
            $totalReelCents = MoneyService::toDatabase(
                $validatedData['total_reel'],
                $session->devise
            );

            // Totaux : fond de caisse + encaissements
            $fondCaisse = (int) ($session->fond_ouverture_cents ?? 0);
            $totalEncaisse = (int) ($session->total_encaisse_cents ?? 0);
            $totalTheorique = $fondCaisse + $totalEncaisse;
            $ecart = $totalReelCents - $totalTheorique;

            $requiresValidation = false;
            $message = 'Session fermée avec succès';

            // Cas écart ≠ 0 → validation manager
            if ($ecart !== 0) {
                $session->update([
                    'statut' => config('appconstants.session_caisse_statut.attente_validation'),
                    'total_reel_cents' => $totalReelCents,
                    'ecart_cents' => $ecart,
                ]);

                $requiresValidation = true;
                $message = 'Session fermée avec écart - en attente de validation manager';
            } else {
                // Cas normal → clôture directe
                $session->update([
                    'statut' => config('appconstants.session_caisse_statut.fermee'),
                    'closed_at' => now(),
                    'total_reel_cents' => $totalReelCents,
                    'ecart_cents' => 0,
                ]);
            }

            DB::commit();

            // Retourner la session mise à jour
            $session->load(['caisse', 'caisse.pointVente', 'caissier.user', 'terminal']);

            $sessionData = [
                'id' => $session->id,
                'reference' => $session->reference,
                'point_vente_nom' => $session->caisse->pointVente->nom ?? 'N/A',
                'caisse_nom' => ($session->caisse->code ?? 'N/A') . ' - ' . ($session->caisse->nom ?? 'N/A'),
                'caissier_nom' => ($session->caissier->user->nom ?? '') . ' ' . ($session->caissier->user->prenoms ?? ''),
                'terminal_numero' => $session->terminal->numero_serie ?? 'N/A',
                'date_ouverture' => $session->opened_at?->format('d/m/Y H:i'),
                'date_fermeture' => $session->closed_at?->format('d/m/Y H:i'),
                'fond_ouverture' => $session->fond_ouverture_cents !== null ? MoneyService::toDisplay($session->fond_ouverture_cents, $session->devise) : null,
                'total_encaisse' => $session->total_encaisse_cents !== null ? MoneyService::toDisplay($session->total_encaisse_cents, $session->devise) : null,
                'total_reel' => $session->total_reel_cents !== null ? MoneyService::toDisplay($session->total_reel_cents, $session->devise) : null,
                'ecart' => $session->ecart_cents !== null ? MoneyService::toDisplay($session->ecart_cents, $session->devise) : null,
                'statut' => $session->statut,
                'devise' => $session->devise,
            ];

            return $this->successResponse([
                'session' => $sessionData,
                'ecart' => $ecart !== 0 ? MoneyService::toDisplay($ecart, $session->devise) : MoneyService::toDisplay(0, $session->devise),
                'requires_validation' => $requiresValidation
            ], $message);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationResponse($e->errors());
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('PosController', 'closeSession', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de la fermeture de la session');
        }
    }

    /**
     * Lister les caisses du point de vente du manager
     *
     * @OA\Get(
     *     path="/api/v1/pos/caisses",
     *     tags={"POS"},
     *     summary="Lister les caisses",
     *     description="Permet à un manager de lister les caisses de son point de vente",
     *     operationId="getCaisses",
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des caisses récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Caisses récupérées avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="caisses", type="array", items=@OA\Property(
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=1),
     *                     @OA\Property(property="label", type="string", example="CAI-001 - Caisse Principale")
     *                 ))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=404, description="Aucun point de vente trouvé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     *
     * @return JsonResponse
     */
    public function getCaisses(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Utilisateur non authentifié');
            }

            // Vérifier rôle manager
            if (!$user->hasRole(config('appconstants.role.manager'))) {
                return $this->unauthorizedResponse('Accès réservé aux managers');
            }

            // Vérifier que l'utilisateur est bien manager
            $employeManager = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.manager'))
                ->first();

            if (!$employeManager || !$employeManager->points_vente_id) {
                return $this->errorResponse('Aucun point de vente associé à ce manager', 404);
            }

            $pointVenteId = $employeManager->points_vente_id;

            // Récupérer les caisses actives
            $caisses = Caisse::select('id', 'code', 'nom')
                ->where('points_vente_id', $pointVenteId)
                ->orderBy('nom')
                ->get()
                ->map(fn($caisse) => [
                    'value' => $caisse->id,
                    'label' => $caisse->code . ' - ' . $caisse->nom
                ]);

            return $this->successResponse([
                'caisses' => $caisses
            ], 'Caisses récupérées avec succès');

        } catch (\Throwable $e) {
            log_error('PosController', 'getCaisses', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de la récupération des caisses');
        }
    }

    /**
     * Lister les terminaux actifs
     *
     * @OA\Get(
     *     path="/api/v1/pos/terminaux",
     *     tags={"POS"},
     *     summary="Lister les terminaux",
     *     description="Permet à un manager de lister les terminaux actifs",
     *     operationId="getTerminaux",
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des terminaux récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Terminaux récupérés avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="terminaux", type="array", items=@OA\Property(
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=1),
     *                     @OA\Property(property="label", type="string", example="TERM-001")
     *                 ))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     *
     * @return JsonResponse
     */
    public function getTerminaux(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Utilisateur non authentifié');
            }

            // Vérifier rôle manager
            if (!$user->hasRole(config('appconstants.role.manager'))) {
                return $this->unauthorizedResponse('Accès réservé aux managers');
            }

            // Récupérer les terminaux actifs
            $terminaux = Terminal::select('id', 'numero_serie')
                ->where('statut', config('appconstants.terminaux_statut.actif'))
                ->orderBy('numero_serie')
                ->get()
                ->map(fn($terminal) => [
                    'value' => $terminal->id,
                    'label' => $terminal->numero_serie
                ]);

            return $this->successResponse([
                'terminaux' => $terminaux
            ], 'Terminaux récupérés avec succès');

        } catch (\Throwable $e) {
            log_error('PosController', 'getTerminaux', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de la récupération des terminaux');
        }
    }

    /**
     * Lister les caissiers du point de vente du manager
     *
     * @OA\Get(
     *     path="/api/v1/pos/caissiers",
     *     tags={"POS"},
     *     summary="Lister les caissiers",
     *     description="Permet à un manager de lister les caissiers de son point de vente avec leur statut de session",
     *     operationId="getCaissiers",
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des caissiers récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Caissiers récupérés avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="caissiers", type="array", items=@OA\Property(
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=1),
     *                     @OA\Property(property="label", type="string", example="John Doe (Disponible)"),
     *                     @OA\Property(property="session_active", type="boolean", example=false)
     *                 ))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=404, description="Aucun point de vente trouvé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     *
     * @return JsonResponse
     */
    public function getCaissiers(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Utilisateur non authentifié');
            }

            // Vérifier rôle manager
            if (!$user->hasRole(config('appconstants.role.manager'))) {
                return $this->unauthorizedResponse('Accès réservé aux managers');
            }

            // Vérifier que l'utilisateur est bien manager
            $employeManager = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.manager'))
                ->first();

            if (!$employeManager || !$employeManager->points_vente_id) {
                return $this->errorResponse('Aucun point de vente associé à ce manager', 404);
            }

            $pointVenteId = $employeManager->points_vente_id;

            // Récupérer les caissiers avec leur statut de session
            $caissiers = Employe::with(['user:id,nom,prenoms'])
                ->withCount([
                    'sessionsCaisse as session_active' => function ($query) {
                        $query->where('statut', config('appconstants.session_caisse_statut.ouverte'));
                    }
                ])
                ->where('type_employe', config('appconstants.type_employe.caissier'))
                ->where('points_vente_id', $pointVenteId)
                ->whereHas('user', function ($query) {
                    $query->where('statut', config('appconstants.user_statut.actif'));
                })
                ->get()
                ->map(function ($employe) {
                    $status = $employe->session_active > 0 ? '(En session)' : '(Disponible)';

                    return [
                        'value' => $employe->id,
                        'label' => $employe->user?->prenoms . ' ' . $employe->user?->nom . ' ' . $status,
                        'session_active' => $employe->session_active > 0
                    ];
                });

            return $this->successResponse([
                'caissiers' => $caissiers
            ], 'Caissiers récupérés avec succès');

        } catch (\Throwable $e) {
            log_error('PosController', 'getCaissiers', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de la récupération des caissiers');
        }
    }

    /**
     * Attribuer une session de caisse (Manager)
     *
     * @OA\Post(
     *     path="/api/v1/pos/attribution-session",
     *     tags={"POS"},
     *     summary="Attribuer une session de caisse",
     *     description="Permet à un manager d'attribuer une session de caisse à un caissier",
     *     operationId="attributionSession",
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"caisse_id","caissier_id"},
     *             @OA\Property(property="caisse_id", type="integer", example=1, description="ID de la caisse"),
     *             @OA\Property(property="caissier_id", type="integer", example=2, description="ID du caissier"),
     *             @OA\Property(property="terminal_id", type="integer", example=3, nullable=true, description="ID du terminal (optionnel)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session attribuée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Session attribuée avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="session", type="object", description="Session de caisse créée",
     *                     @OA\Property(property="id", type="integer", example=123),
     *                     @OA\Property(property="reference", type="string", example="SC-CIV-001-123"),
     *                     @OA\Property(property="caisse_id", type="integer", example=1),
     *                     @OA\Property(property="caissier_id", type="integer", example=2),
     *                     @OA\Property(property="terminal_id", type="integer", example=3, nullable=true),
     *                     @OA\Property(property="statut", type="string", example="attente"),
     *                     @OA\Property(property="devise", type="string", example="XOF")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=422, description="Erreur de validation ou caisse/caissier déjà affecté"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function attributionSession(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Utilisateur non authentifié');
            }

            // Vérifier rôle manager
            if (!$user->hasRole(config('appconstants.role.manager'))) {
                return $this->unauthorizedResponse('Accès réservé aux managers');
            }

            // Validation
            $validatedData = $request->validate([
                'caisse_id' => 'required|exists:caisses,id',
                'caissier_id' => 'required|exists:employes,id',
                'terminal_id' => 'nullable|exists:terminaux,id'
            ], [
                'caisse_id.required' => 'L\'ID de la caisse est obligatoire',
                'caisse_id.exists' => 'La caisse n\'existe pas',
                'caissier_id.required' => 'L\'ID du caissier est obligatoire',
                'caissier_id.exists' => 'Le caissier n\'existe pas',
                'terminal_id.exists' => 'Le terminal n\'existe pas',
            ]);

            // Vérifier que l'utilisateur est bien manager
            $employeManager = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.manager'))
                ->first();

            if (!$employeManager) {
                return $this->errorResponse('Aucun compte manager associé à cet utilisateur', 404);
            }

            // Vérifier qu'aucune session ouverte ou en attente n'existe déjà pour cette caisse
            $sessionExistante = SessionCaisse::where('caisse_id', $validatedData['caisse_id'])
                ->whereIn('statut', [
                    config('appconstants.session_caisse_statut.ouverte'),
                    config('appconstants.session_caisse_statut.attente')
                ])
                ->first();

            if ($sessionExistante) {
                return $this->errorResponse('Cette caisse est déjà affectée à une session', 422);
            }

            // Vérifier que le caissier n'est pas déjà affecté à une autre session active
            $sessionCaissier = SessionCaisse::where('caissier_id', $validatedData['caissier_id'])
                ->whereIn('statut', [
                    config('appconstants.session_caisse_statut.ouverte'),
                    config('appconstants.session_caisse_statut.attente')
                ])
                ->first();

            if ($sessionCaissier) {
                return $this->errorResponse('Ce caissier est déjà affecté à une session', 422);
            }

            // Récupérer les informations pour générer la référence
            $caissier = Employe::findOrFail($validatedData['caissier_id']);
            $pointVente = PointVente::findOrFail($caissier->points_vente_id);
            $pays = Pays::find($user->pays_id);
            $paysIso = $pays->iso ?? "XX";

            // Générer la référence
            $reference = Generator::generateSessionCaisseReference($paysIso, $pointVente->id, $validatedData['caisse_id']);

            DB::beginTransaction();

            // Création de la session en statut attente
            $session = SessionCaisse::create([
                'caisse_id' => $validatedData['caisse_id'],
                'caissier_id' => $validatedData['caissier_id'],
                'terminal_id' => $validatedData['terminal_id'] ?? null,
                'devise' => config('appconstants.devisedefault'), // Sera changé à l'ouverture
                'reference' => $reference,
                'statut' => config('appconstants.session_caisse_statut.attente'),
            ]);

            DB::commit();

            // Charger les relations pour la réponse
            $session->load(['caisse', 'caissier.user', 'terminal']);

            $sessionData = [
                'id' => $session->id,
                'reference' => $session->reference,
                'caisse_id' => $session->caisse_id,
                'caissier_id' => $session->caissier_id,
                'terminal_id' => $session->terminal_id,
                'statut' => $session->statut,
                'devise' => $session->devise,
                'caisse_nom' => $session->caisse?->code . ' - ' . $session->caisse?->nom,
                'caissier_nom' => $session->caissier?->user?->prenoms . ' ' . $session->caissier?->user?->nom,
                'terminal_numero' => $session->terminal?->numero_serie,
            ];

            return $this->successResponse([
                'session' => $sessionData
            ], 'Session attribuée avec succès');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationResponse($e->errors());
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('PosController', 'attributionSession', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de l\'attribution de la session');
        }
    }

    /**
     * Afficher les détails d'une session de caisse (Manager)
     *
     * @OA\Get(
     *     path="/api/v1/pos/show-session/{id}",
     *     tags={"POS"},
     *     summary="Afficher une session de caisse",
     *     description="Permet à un manager d'afficher les détails d'une session de caisse",
     *     operationId="showSession",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la session à afficher",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Session récupérée avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="session", type="object", description="Détails de la session",
     *                     @OA\Property(property="id", type="integer", example=123),
     *                     @OA\Property(property="reference", type="string", example="SC-CIV-001-123"),
     *                     @OA\Property(property="caisse_nom", type="string", example="CAI-001 - Caisse Principale"),
     *                     @OA\Property(property="caissier_nom", type="string", example="John Doe"),
     *                     @OA\Property(property="terminal_numero", type="string", example="TERM-001"),
     *                     @OA\Property(property="date_ouverture", type="string", example="15/01/2025 08:30", nullable=true),
     *                     @OA\Property(property="date_fermeture", type="string", example="15/01/2025 16:30", nullable=true),
     *                     @OA\Property(property="fond_ouverture", type="string", example="50 000,00", nullable=true),
     *                     @OA\Property(property="total_encaisse", type="string", example="125 500,00", nullable=true),
     *                     @OA\Property(property="total_reel", type="string", example="175 000,00", nullable=true),
     *                     @OA\Property(property="ecart", type="string", example="-500,00", nullable=true),
     *                     @OA\Property(property="statut", type="string", example="fermee"),
     *                     @OA\Property(property="devise", type="string", example="XOF"),
     *                     @OA\Property(property="validated_at", type="string", example="15/01/2025 16:45", nullable=true),
     *                     @OA\Property(property="validation_commentaire", type="string", example="Écart justifié", nullable=true),
     *                     @OA\Property(property="cancelled_at", type="string", example="15/01/2025 16:45", nullable=true),
     *                     @OA\Property(property="cancel_reason", type="string", example="Maintenance", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=404, description="Session non trouvée"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     *
     * @param int $id
     * @return JsonResponse
     */
    public function showSession(int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Utilisateur non authentifié');
            }

            // Vérifier rôle manager
            if (!$user->hasRole(config('appconstants.role.manager'))) {
                return $this->unauthorizedResponse('Accès réservé aux managers');
            }

            // Vérifier que l'utilisateur est bien manager
            $employeManager = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.manager'))
                ->first();

            if (!$employeManager) {
                return $this->errorResponse('Aucun compte manager associé à cet utilisateur', 404);
            }

            // Charger la session avec toutes les relations nécessaires
            $session = SessionCaisse::with([
                'caisse',
                'caissier.user',
                'terminal'
            ])->findOrFail($id);

            // Vérifier que la session appartient au point de vente du manager
            if ($session->caissier?->points_vente_id !== $employeManager->points_vente_id) {
                return $this->errorResponse('Cette session ne dépend pas de votre point de vente', 403);
            }

            // Préparer les données de la session avec MoneyService pour tous les montants
            $sessionData = [
                'id' => $session->id,
                'reference' => $session->reference,
                'caisse_nom' => ($session->caisse->code ?? 'N/A') . ' - ' . ($session->caisse->nom ?? 'N/A'),
                'caissier_nom' => $session->caissier?->user?->prenoms . ' ' . $session->caissier?->user?->nom ?? 'N/A',
                'terminal_numero' => $session->terminal?->numero_serie ?? 'N/A',
                'date_ouverture' => $session->opened_at?->format('d/m/Y H:i'),
                'date_fermeture' => $session->closed_at?->format('d/m/Y H:i'),
                'fond_ouverture' => $session->fond_ouverture_cents !== null && $session->devise
                    ? MoneyService::toDisplay($session->fond_ouverture_cents, $session->devise)
                    : null,
                'total_encaisse' => $session->total_encaisse_cents !== null && $session->devise
                    ? MoneyService::toDisplay($session->total_encaisse_cents, $session->devise)
                    : null,
                'total_reel' => $session->total_reel_cents !== null && $session->devise
                    ? MoneyService::toDisplay($session->total_reel_cents, $session->devise)
                    : null,
                'ecart' => $session->ecart_cents !== null && $session->devise
                    ? MoneyService::toDisplay($session->ecart_cents, $session->devise)
                    : null,
                'statut' => $session->statut,
                'devise' => $session->devise,
                'validated_at' => $session->validated_at?->format('d/m/Y H:i'),
                'validation_commentaire' => $session->validation_commentaire,
                'cancelled_at' => $session->cancelled_at?->format('d/m/Y H:i'),
                'cancel_reason' => $session->cancel_reason,
                'created_at' => $session->created_at?->format('d/m/Y H:i'),
                'updated_at' => $session->updated_at?->format('d/m/Y H:i'),
            ];

            return $this->successResponse([
                'session' => $sessionData
            ], 'Session récupérée avec succès');

        } catch (\Throwable $e) {
            log_error('PosController', 'showSession', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de la récupération de la session');
        }
    }

    /**
     * Valider la fermeture d'une session avec écart (Manager)
     *
     * @OA\Post(
     *     path="/api/v1/pos/fermerture-session",
     *     tags={"POS"},
     *     summary="Valider la fermeture d'une session avec écart",
     *     description="Permet à un manager de valider la fermeture d'une session ayant un écart",
     *     operationId="fermertureSession",
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_id"},
     *             @OA\Property(property="session_id", type="integer", example=123, description="ID de la session à fermer"),
     *             @OA\Property(property="commentaire", type="string", maxLength=500, nullable=true, example="Écart justifié par erreur de caisse", description="Commentaire de validation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session fermée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Session fermée avec écart validée"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="session", type="object", description="Session de caisse fermée",
     *                     @OA\Property(property="id", type="integer", example=123),
     *                     @OA\Property(property="reference", type="string", example="SC-CIV-001-123"),
     *                     @OA\Property(property="statut", type="string", example="fermee"),
     *                     @OA\Property(property="ecart", type="string", example="500,00"),
     *                     @OA\Property(property="validated_at", type="string", example="15/01/2025 16:30"),
     *                     @OA\Property(property="validation_commentaire", type="string", example="Écart justifié par erreur de caisse"),
     *                     @OA\Property(property="closed_at", type="string", example="15/01/2025 16:30"),
     *                     @OA\Property(property="devise", type="string", example="XOF")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=404, description="Session non trouvée"),
     *     @OA\Response(response=422, description="Session non en attente de validation ou sans écart"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function fermertureSession(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Utilisateur non authentifié');
            }

            // Vérifier rôle manager
            if (!$user->hasRole(config('appconstants.role.manager'))) {
                return $this->unauthorizedResponse('Accès réservé aux managers');
            }

            // Validation
            $validatedData = $request->validate([
                'session_id' => 'required|exists:sessions_caisse,id',
                'commentaire' => 'nullable|string|max:500',
            ], [
                'session_id.required' => 'L\'ID de la session est obligatoire',
                'session_id.exists' => 'La session n\'existe pas',
                'commentaire.string' => 'Le commentaire doit être une chaîne de caractères',
                'commentaire.max' => 'Le commentaire ne doit pas dépasser 500 caractères',
            ]);

            // Vérifier que l'utilisateur est bien manager
            $employe = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.manager'))
                ->first();

            if (!$employe) {
                return $this->errorResponse('Aucun compte manager associé à cet utilisateur', 404);
            }

            // Charger la session
            $session = SessionCaisse::findOrFail($validatedData['session_id']);

            // Vérifier l'appartenance au même marchand
            if (
                !$session->caisse?->pointVente ||
                !$employe->pointVente ||
                $session->caisse->pointVente->marchand_id !== $employe->pointVente->marchand_id
            ) {
                return $this->errorResponse('Cette session ne dépend pas de votre marchand', 403);
            }

            // Vérifier statut
            if ($session->statut !== config('appconstants.session_caisse_statut.attente_validation')) {
                return $this->errorResponse('Cette session n\'est pas en attente de validation', 422);
            }

            // Vérifier qu'il y a bien un écart
            if ((int) $session->ecart_cents === 0) {
                return $this->errorResponse('Cette session n\'a pas d\'écart à valider', 422);
            }

            DB::beginTransaction();

            // Validation finale
            $session->update([
                'statut' => config('appconstants.session_caisse_statut.fermee'),
                'validated_by' => $user->id,
                'validated_at' => now(),
                'validation_commentaire' => $validatedData['commentaire'] ?? null,
                'closed_at' => now(),
            ]);

            DB::commit();

            // Charger les relations pour la réponse
            $session->load(['caisse', 'caisse.pointVente', 'caissier.user', 'terminal']);

            $sessionData = [
                'id' => $session->id,
                'reference' => $session->reference,
                'statut' => $session->statut,
                'ecart' => $session->ecart_cents !== null ? MoneyService::toDisplay($session->ecart_cents, $session->devise) : null,
                'validated_at' => $session->validated_at?->format('d/m/Y H:i'),
                'validation_commentaire' => $session->validation_commentaire,
                'closed_at' => $session->closed_at?->format('d/m/Y H:i'),
                'devise' => $session->devise,
            ];

            return $this->successResponse([
                'session' => $sessionData
            ], 'Session fermée avec écart validée');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationResponse($e->errors());
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('PosController', 'fermertureSession', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de la validation de la session');
        }
    }

    /**
     * Valider la clôture d'une session de caisse (Manager)
     *
     * @OA\Post(
     *     path="/api/v1/pos/validate-session/{id}",
     *     tags={"POS"},
     *     summary="Valider une session de caisse",
     *     description="Permet à un manager de valider une session de caisse en attente de validation",
     *     operationId="validateSession",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la session à valider",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session validée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Session validée avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="session", type="object", description="Session de caisse validée",
     *                     @OA\Property(property="id", type="integer", example=123),
     *                     @OA\Property(property="reference", type="string", example="SC-CIV-001-123"),
     *                     @OA\Property(property="statut", type="string", example="fermee"),
     *                     @OA\Property(property="validated_at", type="string", example="15/01/2025 16:30"),
     *                     @OA\Property(property="closed_at", type="string", example="15/01/2025 16:30"),
     *                     @OA\Property(property="devise", type="string", example="XOF")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=404, description="Session non trouvée"),
     *     @OA\Response(response=422, description="Session non en attente de validation"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     *
     * @param int $id
     * @return JsonResponse
     */
    public function validateSession(int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Utilisateur non authentifié');
            }

            // Vérifier rôle manager
            if (!$user->hasRole(config('appconstants.role.manager'))) {
                return $this->unauthorizedResponse('Accès réservé aux managers');
            }

            // Vérifier que l'utilisateur est bien manager
            $employeManager = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.manager'))
                ->first();

            if (!$employeManager) {
                return $this->errorResponse('Aucun compte manager associé à cet utilisateur', 404);
            }

            // Charger la session
            $session = SessionCaisse::findOrFail($id);

            // Vérifier que la session appartient au point de vente du manager
            if ($session->caissier?->points_vente_id !== $employeManager->points_vente_id) {
                return $this->errorResponse('Cette session ne dépend pas de votre point de vente', 403);
            }

            // Vérifier le statut
            if ($session->statut !== config('appconstants.session_caisse_statut.attente_validation')) {
                return $this->errorResponse('Cette session n\'est pas en attente de validation', 422);
            }

            DB::beginTransaction();

            // Valider la session
            $session->update([
                'statut' => config('appconstants.session_caisse_statut.fermee'),
                'validated_by' => $user->id,
                'validated_at' => now(),
                'closed_at' => $session->closed_at ?? now(),
            ]);

            DB::commit();

            // Charger les relations pour la réponse
            $session->load(['caisse', 'caisse.pointVente', 'caissier.user', 'terminal']);

            $sessionData = [
                'id' => $session->id,
                'reference' => $session->reference,
                'statut' => $session->statut,
                'validated_at' => $session->validated_at?->format('d/m/Y H:i'),
                'closed_at' => $session->closed_at?->format('d/m/Y H:i'),
                'devise' => $session->devise,
            ];

            return $this->successResponse([
                'session' => $sessionData
            ], 'Session validée avec succès');

        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('PosController', 'validateSession', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de la validation de la session');
        }
    }

    /**
     * Annuler une session de caisse (Manager)
     *
     * @OA\Post(
     *     path="/api/v1/pos/cancel-session/{id}",
     *     tags={"POS"},
     *     summary="Annuler une session de caisse",
     *     description="Permet à un manager d'annuler une session de caisse",
     *     operationId="cancelSession",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la session à annuler",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"motif"},
     *             @OA\Property(property="motif", type="string", maxLength=500, example="Annulation pour maintenance", description="Motif de l'annulation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session annulée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Session annulée avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="session", type="object", description="Session de caisse annulée",
     *                     @OA\Property(property="id", type="integer", example=123),
     *                     @OA\Property(property="reference", type="string", example="SC-CIV-001-123"),
     *                     @OA\Property(property="statut", type="string", example="annulee"),
     *                     @OA\Property(property="cancelled_at", type="string", example="15/01/2025 16:30"),
     *                     @OA\Property(property="cancel_reason", type="string", example="Annulation pour maintenance")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=404, description="Session non trouvée"),
     *     @OA\Response(response=422, description="Session non annulable ou motif manquant"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function cancelSession(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Utilisateur non authentifié');
            }

            // Vérifier rôle manager
            if (!$user->hasRole(config('appconstants.role.manager'))) {
                return $this->unauthorizedResponse('Accès réservé aux managers');
            }

            // Vérifier que l'utilisateur est bien manager
            $employeManager = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.manager'))
                ->first();

            if (!$employeManager) {
                return $this->errorResponse('Aucun compte manager associé à cet utilisateur', 404);
            }

            // Validation
            $validatedData = $request->validate([
                'motif' => 'required|string|max:500',
            ], [
                'motif.required' => 'Le motif d\'annulation est obligatoire',
                'motif.string' => 'Le motif doit être une chaîne de caractères',
                'motif.max' => 'Le motif ne doit pas dépasser 500 caractères',
            ]);

            // Charger la session
            $session = SessionCaisse::findOrFail($id);

            // Vérifier que la session appartient au point de vente du manager
            if ($session->caissier?->points_vente_id !== $employeManager->points_vente_id) {
                return $this->errorResponse('Cette session ne dépend pas de votre point de vente', 403);
            }

            // Vérifier le statut - on ne peut annuler que les sessions en attente ou attente_validation
            $statutsAnnulables = [
                config('appconstants.session_caisse_statut.attente'),
                config('appconstants.session_caisse_statut.attente_validation'),
            ];

            if (!in_array($session->statut, $statutsAnnulables)) {
                return $this->errorResponse('Cette session ne peut pas être annulée', 422);
            }

            DB::beginTransaction();

            // Annuler la session
            $session->update([
                'statut' => config('appconstants.session_caisse_statut.annulee'),
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
                'cancel_reason' => $validatedData['motif'],
            ]);

            DB::commit();

            // Charger les relations pour la réponse
            $session->load(['caisse', 'caisse.pointVente', 'caissier.user', 'terminal']);

            $sessionData = [
                'id' => $session->id,
                'reference' => $session->reference,
                'statut' => $session->statut,
                'cancelled_at' => $session->cancelled_at?->format('d/m/Y H:i'),
                'cancel_reason' => $session->cancel_reason,
            ];

            return $this->successResponse([
                'session' => $sessionData
            ], 'Session annulée avec succès');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationResponse($e->errors());
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('PosController', 'cancelSession', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de l\'annulation de la session');
        }
    }
}
