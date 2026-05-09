<?php

namespace Modules\ServiceClient\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PhoneLoginRequest;
use App\Http\Resources\UserResource;
use App\Http\Traits\ApiResponseTrait;
use App\Models\OtpSession;
use App\Models\User;
use App\Services\Generator;
use App\Services\SMSApiProService;
use App\Services\UserLoginService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Parametrage\Entities\Pays;
use Modules\Wallet\Entities\Wallet;
use Modules\Wallet\Services\WalletService;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Facades\JWTAuth;
use Modules\Parametrage\Entities\Fichier;
use Modules\Parametrage\Resources\FournisseurPaiementResource;
use Modules\ServiceClient\Entities\Client;
use Modules\ServiceClient\Entities\MoyenPaiement;
use Modules\ServiceClient\Resources\ClientResource;
use Modules\ServiceClient\Resources\MoyenPaiementResource;
use Modules\Parametrage\Entities\FournisseurPaiement;

class ClientController extends Controller
{
    use ApiResponseTrait;


    /**
     * Vérification par téléphone
     *
     * Vérifie le numéro de téléphone et envoie un OTP si nécessaire.
     *
     * @OA\Post(
     *     path="/api/v1/clients/phone-verification",
     *     tags={"Client"},
     *     summary="Vérifier le numéro de téléphone et envoyer un OTP",
     *     description="Ce point d'accès vérifie si un utilisateur existe avec le numéro de téléphone et le pays donnés. Si l'utilisateur existe et a un mot de passe, il retourne un succès. Sinon, il envoie un code OTP par SMS.",
     *     operationId="phoneVerification",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"login", "pays_id"},
     *             @OA\Property(
     *                 property="login",
     *                 type="string",
     *                 description="Numéro de téléphone",
     *                 example="0747780101"
     *             ),
     *             @OA\Property(
     *                 property="pays_id",
     *                 type="integer",
     *                 description="Identifiant du pays",
     *                 example=8
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="L'utilisateur existe avec un mot de passe - connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="data", type="string", example="success")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="OTP envoyé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="string",
     *                 format="date-time",
     *                 description="Date d'expiration de l'OTP",
     *                 example="2026-01-28T18:25:00.000000Z"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de validation ou code pays invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Code pays invalide")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation échouée",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="login",
     *                     type="array",
     *                     @OA\Items(type="string", example="Le numéro de téléphone est obligatoire")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Trop de requêtes - OTP déjà envoyé",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Désolé, veuillez patienter 45 secondes pour une autre tentative.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur - Envoi SMS échoué",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Désolé, erreur lors de l'envoi de l'OTP.")
     *         )
     *     )
     * )
     */
    public function phoneVerification(PhoneLoginRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $user = User::where('login', $validated['login'])
                ->where('pays_id', $validated['pays_id'])
                ->first();

            // Si l'utilisateur existe et a un mot de passe, retourner success
            if ($user && !is_null($user->password)) {
                return $this->successResponse('success', 'Connexion possible');
            }
            // Sinon, procéder à la vérification OTP
            return UserLoginService::handleOtpVerification($validated['pays_id'], $validated['login']);

        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), $e->status ?? 500);
        }
    }

    /**
     * Vérification du code OTP
     *
     * Vérifie si le code OTP fourni est valide pour le numéro de téléphone donné.
     *
     * @OA\Post(
     *     path="/api/v1/clients/check-otp",
     *     tags={"Client"},
     *     summary="Vérifier le code OTP",
     *     description="Ce point d'accès vérifie si le code OTP fourni est valide pour le numéro de téléphone donné. Si valide, il supprime la session OTP.",
     *     operationId="checkOTP",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"login", "otp", "pays_id"},
     *             @OA\Property(
     *                 property="login",
     *                 type="string",
     *                 description="Numéro de téléphone",
     *                 example="0747780101"
     *             ),
     *             @OA\Property(
     *                 property="otp",
     *                 type="string",
     *                 description="Code OTP reçu par SMS",
     *                 example="1234"
     *             ),
     *             @OA\Property(
     *                 property="pays_id",
     *                 type="integer",
     *                 description="Identifiant du pays",
     *                 example=8
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP valide - vérification réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="OTP valide.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="OTP incorrect ou validation échouée",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Code invalide. Veuillez réessayer.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Trop de tentatives",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Nombre maximum de tentatives dépassé.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Session OTP introuvable ou expirée",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Session expirée ou introuvable.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation échouée",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="login",
     *                     type="array",
     *                     @OA\Items(type="string", example="Le numéro de téléphone est obligatoire")
     *                 ),
     *                 @OA\Property(
     *                     property="otp",
     *                     type="array",
     *                     @OA\Items(type="string", example="Le champ OTP est obligatoire")
     *                 ),
     *                 @OA\Property(
     *                     property="pays_id",
     *                     type="array",
     *                     @OA\Items(type="string", example="Le pays est obligatoire")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function checkOTP(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'login' => 'required|string',
                'otp' => 'required|string|min:4|max:4',
                'pays_id' => 'required|integer|exists:pays,id',
            ], [
                'login.required' => 'Le numéro de téléphone est obligatoire',
                'login.string' => 'Le numéro de téléphone doit être une chaîne de caractères',
                'otp.required' => 'Le code OTP est obligatoire',
                'otp.string' => 'Le code OTP doit être une chaîne de caractères',
                'otp.min' => 'Le code OTP doit contenir 4 caractères',
                'otp.max' => 'Le code OTP doit contenir 4 caractères',
                'pays_id.required' => 'Le pays est obligatoire',
                'pays_id.integer' => 'L\'identifiant du pays doit être un entier',
                'pays_id.exists' => 'Le pays sélectionné n\'existe pas',
            ]);

            // Récupérer le pays pour construire le numéro complet
            $pays = Pays::find($validated['pays_id']);
            if (!$pays) {
                return $this->errorResponse('Pays non trouvé', 404);
            }

            $fullPhone = $pays->fullPhoneNumber($validated['login']);
            if (!$fullPhone) {
                return $this->errorResponse('Numéro de téléphone invalide', 400);
            }

            // Récupérer la session OTP active
            $otpSession = OtpSession::where('phone_number', $fullPhone)
                ->where('end_date', '>', Carbon::now())
                ->first();

            if (!$otpSession) {
                return $this->errorResponse('Session expirée ou introuvable.', 404);
            }
            // Vérifier le nombre de tentatives
            $maxAttempts = config('services.sms.max_attempts', 3);
            if ($otpSession->tentative >= $maxAttempts) {
                return $this->errorResponse('Nombre maximum de tentatives dépassé.', 403);
            }

            // Vérifier le code OTP
            if ($otpSession->otp === $validated['otp']) {
                // Supprimer toutes les sessions OTP pour ce numéro
                UserLoginService::cleanupOldSessions($fullPhone);

                return $this->successResponse('OTP valide', 'OTP vérifié avec succès');
            }

            // Incrémenter le nombre de tentatives
            $otpSession->increment('tentative');
            $remainingAttempts = $maxAttempts - $otpSession->tentative;

            return $this->errorResponse(
                "Code invalide. Il vous reste {$remainingAttempts} tentative(s).",
                400
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }



    /**
     * Register a new client.
     *
     * @OA\Post(
     *     path="/api/v1/clients/register-client",
     *     summary="Créer un nouveau compte client",
     *     description="Ce point d'accès crée un nouveau compte client avec toutes les informations requises et retourne un token d'accès.",
     *     tags={"Client"},
     *     operationId="registerClient",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"nom", "prenoms", "login", "password", "pays_id"},
     *                 @OA\Property(
     *                     property="nom",
     *                     type="string",
     *                     description="Nom de famille du client",
     *                     example="Doe"
     *                 ),
     *                 @OA\Property(
     *                     property="prenoms",
     *                     type="string",
     *                     description="Prénoms du client",
     *                     example="John"
     *                 ),
     *                 @OA\Property(
     *                     property="login",
     *                     type="string",
     *                     description="Numéro de téléphone unique",
     *                     example="0747780101"
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     format="email",
     *                     description="Adresse email (optionnel)",
     *                     example="john@example.com"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string",
     *                     description="Mot de passe du compte",
     *                     example="12345"
     *                 ),
     *                 @OA\Property(
     *                     property="pays_id",
     *                     type="integer",
     *                     description="Identifiant du pays",
     *                     example=8
     *                 ),
     *                 @OA\Property(
     *                     property="type_piece",
     *                     type="string",
     *                     enum={"passport", "cni", "pc", "ai"},
     *                     description="Type de pièce d'identité",
     *                     example="cni"
     *                 ),
     *                 @OA\Property(
     *                     property="numero_piece",
     *                     type="string",
     *                     description="Numéro de la pièce d'identité",
     *                     example="123456789"
     *                 ),
     *                 @OA\Property(
     *                     property="date_delivrance",
     *                     type="string",
     *                     format="date",
     *                     description="Date de délivrance de la pièce",
     *                     example="2020-01-15"
     *                 ),
     *                 @OA\Property(
     *                     property="date_naissance",
     *                     type="string",
     *                     format="date",
     *                     description="Date de naissance",
     *                     example="1990-05-15"
     *                 ),
     *                 @OA\Property(
     *                     property="lieu_delivrance",
     *                     type="string",
     *                     description="Lieu de délivrance de la pièce",
     *                     example="Abidjan"
     *                 ),
     *                 @OA\Property(
     *                     property="lieu_naissance",
     *                     type="string",
     *                     description="Lieu de naissance",
     *                     example="Abidjan"
     *                 ),
     *                 @OA\Property(
     *                     property="photoprofile",
     *                     type="string",
     *                     format="binary",
     *                     description="Photo de profil (fichier image - max 2MB, formats: jpeg, png, jpg, gif, svg)"
     *                 ),
     *                 @OA\Property(
     *                     property="piecerecto",
     *                     type="string",
     *                     format="binary",
     *                     description="Recto de la pièce d'identité (fichier image - max 2MB, formats: jpeg, png, jpg, gif, svg)"
     *                 ),
     *                 @OA\Property(
     *                     property="pieceverso",
     *                     type="string",
     *                     format="binary",
     *                     description="Verso de la pièce d'identité (fichier image - max 2MB, formats: jpeg, png, jpg, gif, svg)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte client créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Client créé avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *                 @OA\Property(property="user", ref="#/components/schemas/ClientResource"),
     *                 @OA\Property(property="session_id", type="string", example="session_id_here"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="nom",
     *                     type="array",
     *                     @OA\Items(type="string", example="Le nom est obligatoire")
     *                 ),
     *                 @OA\Property(
     *                     property="prenoms",
     *                     type="array",
     *                     @OA\Items(type="string", example="Les prénoms sont obligatoires")
     *                 ),
     *                 @OA\Property(
     *                     property="login",
     *                     type="array",
     *                     @OA\Items(type="string", example="Le numéro de téléphone est obligatoire")
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="array",
     *                     @OA\Items(type="string", example="Le mot de passe est obligatoire")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Une erreur est survenue lors de l'inscription")
     *         )
     *     )
     * )
     */
    public function registerClient(Request $request): JsonResponse
    {
        try {
            // Vérifier d'abord si le login existe (y compris les supprimés)
            $existingUser = User::withTrashed()
                ->where('login', $request->login)
                ->first();

            if ($existingUser) {
                if ($existingUser->trashed()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Ce numéro de téléphone existe mais a été supprimé veuillez contacté un administrateur',
                        'code' => 'user_deleted'
                    ], 422);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Ce numéro de téléphone est déjà utilisé',
                        'code' => 'user_exists'
                    ], 422);
                }
            }

            $validated = $request->validate([
                'nom' => 'required|string|max:255',
                'prenoms' => 'required|string|max:255',
                'login' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'pays_id' => 'required|integer|exists:pays,id',
                'password' => 'required|string|digits:5',
                'type_piece' => 'nullable|in:passport,cni,pc,ai',
                'numero_piece' => 'nullable|string',
                'date_delivrance' => 'nullable|date',
                'date_naissance' => 'nullable|date',
                'lieu_delivrance' => 'nullable|string',
                'lieu_naissance' => 'nullable|string',
                'photoprofile' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'piecerecto' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'pieceverso' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ], [
                'nom.required' => 'Le nom est obligatoire',
                'prenoms.required' => 'Les prénoms sont obligatoires',
                'login.required' => 'Le numéro de téléphone est obligatoire',
                'pays_id.required' => 'Le pays est obligatoire',
                'password.required' => 'Le mot de passe est obligatoire',
                'password.digits' => 'Le mot de passe doit contenir exactement 5 chiffres',
            ]);

            DB::beginTransaction();

            // Gestion des fichiers uploadés
            $fileFields = ['photoprofile_id', 'piecerecto_id', 'pieceverso_id'];
            $fileIds = [];

            foreach ($fileFields as $field) {
                $fieldName = str_replace('_id', '', $field);
                if ($request->hasFile($fieldName)) {
                    $file = $request->file($fieldName);
                    $fileName = time() . '-' . \Str::random(10) . '.' . $file->extension();
                    $file->move(public_path('images'), $fileName);

                    $fileIds[$field] = Fichier::create([
                        'nom' => $fileName,
                        'source' => 'images/' . $fileName,
                    ])->id;
                }
            }

            // Récupérer le pays pour construire le full_login
            $pays = Pays::find($validated['pays_id']);
            if (!$pays) {
                return $this->errorResponse('Pays non trouvé', 404);
            }

            $indicatif = $pays->code;
            $fullPhone = $indicatif . $validated['login'];

            // Création de l'utilisateur
            $user = User::create([
                'nom' => $validated['nom'],
                'prenoms' => $validated['prenoms'],
                'email' => $validated['email'] ?? null,
                'login' => $validated['login'],
                'full_login' => $fullPhone,
                'password' => Hash::make($validated['password']),
                'pays_id' => $validated['pays_id'],
                'uuid' => Generator::uuid(),
                'role' => config('appconstants.role.client'),
                'alias_smil' => Generator::generateAliasSmil($validated['nom'], $validated['prenoms']),
                'lieu_delivrance' => $validated['lieu_delivrance'] ?? null,
                'date_delivrance' => $validated['date_delivrance'] ?? null,
                'date_naissance' => $validated['date_naissance'] ?? null,
                'lieu_naissance' => $validated['lieu_naissance'] ?? null,
                'type_piece' => $validated['type_piece'] ?? null,
                'numero_piece' => $validated['numero_piece'] ?? null,
                'code_owner' => Generator::codeOwner(),
                'qr_data' => Generator::QrCode($validated['login']),
                'photoprofile_id' => $fileIds['photoprofile_id'] ?? null,
                'piecerecto_id' => $fileIds['piecerecto_id'] ?? null,
                'pieceverso_id' => $fileIds['pieceverso_id'] ?? null,
            ]);

            // Assignation du rôle client
            $role = Role::findByName(
                config('appconstants.role.client'),
                config('appconstants.guard.web', 'web')
            );
            $user->assignRole($role);

            // Création des wallets pour le client
            WalletService::createWalletsForOwner(
                $user->id,
                Wallet::OWNER_TYPE_CLIENT,
                $validated['pays_id']
            );

            // Créer un token JWT pour l'utilisateur
            $jwtToken = JWTAuth::fromUser($user);

            // Envoyer un SMS de bienvenue
            $this->sendWelcomeSms($user);

            // Logger la création du compte
            UserLoginService::logUserLogin($user, [
                'device_id' => 'registration',
                'device_type' => 'api',
            ]);

            DB::commit();

            return $this->successResponse([
                'token' => 'Bearer ' . $jwtToken,
                'user' => new ClientResource($user),
                'session_id' => \Str::random(32),
                'expires_in' => config('jwt.ttl', 60) * 60, // TTL en secondes
            ], 'Client créé avec succès', 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            DB::rollback();
            \Log::error('Register client error: ' . $e->getMessage());
            log_error('ApiClientController','registerClient',$e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de l\'inscription', 500);
        }
    }
    /**
     * Login Client
     *
     * Authentifie un client avec OTP ou mot de passe et retourne un token API.
     *
     * @OA\Post(
     *     path="/api/v1/clients/login-client",
     *     tags={"Client"},
     *     summary="Authentifier un client",
     *     description="Ce point d'accès authentifie un client avec mot de passe et retourne un token d'accès API.",
     *     operationId="loginClient",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"login", "password", "pays_id", "device_info"},
     *             @OA\Property(
     *                 property="login",
     *                 type="string",
     *                 description="Numéro de téléphone",
     *                 example="0747780101"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 description="Mot de passe",
     *                 example="12345"
     *             ),
     *             @OA\Property(
     *                 property="pays_id",
     *                 type="integer",
     *                 description="Identifiant du pays",
     *                 example=8
     *             ),
     *             @OA\Property(
     *                 property="device_info",
     *                 type="object",
     *                 description="Informations sur l'appareil",
     *                 @OA\Property(property="device_id", type="string", example="unique_device_id"),
     *                 @OA\Property(property="device_type", type="string", example="mobile"),
     *                 @OA\Property(property="fcm_token", type="string", example="fcm_token_here")
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
     *                 @OA\Property(property="token", type="string", example="Bearer token_here"),
     *                 @OA\Property(property="user", ref="#/components/schemas/ClientResource"),
     *                 @OA\Property(property="session_id", type="string", example="session_id_here"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de validation ou utilisateur non client",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cet utilisateur n'est pas un client")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé - compte supprimé, suspendu, bloqué ou non client",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ce compte est suspendu")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Utilisateur non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Utilisateur non trouvé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation échouée",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="login",
     *                     type="array",
     *                     @OA\Items(type="string", example="Le numéro de téléphone est obligatoire")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function loginClient(Request $request): JsonResponse
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

            // Vérifier que c'est bien un client
            if (!$user->hasRole(config('appconstants.role.client'))) {
                return $this->errorResponse('Ce compte n\'est pas un client. Veuillez utiliser l\'application appropriée', 403);
            }

            // Authentification avec full_login comme dans AuthenticatedSessionController
            if (!Auth::attempt(['full_login' => $fullPhone, 'password' => $validated['password']])) {
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

                \Log::info('Données du token à insérer', [
                    'token_id' => $tokenId,
                    'user_id' => $user->id,
                    'expires_at' => date('Y-m-d H:i:s', $expiresAt),
                ]);

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

            // Charger les wallets du client pour la réponse
            $user->setRelation('wallets', Wallet::where('owner_id', $user->id)
                ->where('owner_type', Wallet::OWNER_TYPE_CLIENT)
                ->with('paysDevise')
                ->get());

            return $this->successResponse([
                'token' => 'Bearer ' . $jwtToken,
                'user' => new ClientResource($user),
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
            log_error('ApiClientController','loginClient',$e);
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }

    /**
     * Mettre à jour la photo de profil du client connecté
     *
     * @OA\Post(
     *     path="/api/v1/clients/update-profile-photo",
     *     tags={"Client"},
     *     summary="Mettre à jour la photo de profil du client connecté",
     *     description="Permet à un client connecté de mettre à jour sa photo de profil",
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
     *                 @OA\Property(property="client", ref="#/components/schemas/ClientResource")
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

            $client = Auth::user();

            if (!$client) {
                return $this->unauthorizedResponse('Client non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle client
            if (!$client->hasRole(config('appconstants.role.client'))) {
                return $this->unauthorizedResponse('Accès réservé aux clients');
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
                    $oldFileId = $client->{$dbField};
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

            // Mettre à jour le client avec les nouveaux fichiers
            foreach ($newFileIds as $dbField => $fileId) {
                $client->update([$dbField => $fileId]);
            }

            DB::commit();

            // Rafraîchir le client pour inclure les nouvelles relations
            $client->refresh();

            // Retourner le client mis à jour avec sa nouvelle photo
            return $this->successResponse([
                'client' => new ClientResource($client)
            ], 'Photo de profil mise à jour avec succès');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationResponse($e->errors());
        } catch (\Throwable $e) {
            DB::rollback();
            log_error("ClientController","updateProfilePhoto",$e->getMessage());
            \Log::error('Erreur lors de la mise à jour de la photo de profil: ' . $e->getMessage());
            return $this->serverErrorResponse('Une erreur est survenue lors de la mise à jour de la photo de profil');
        }
    }

    /**
     * Mettre à jour les documents d'identité du client connecté
     *
     * @OA\Post(
     *     path="/api/v1/clients/update-documents",
     *     tags={"Client"},
     *     summary="Mettre à jour les documents d'identité du client connecté",
     *     description="Permet à un client connecté de mettre à jour ses documents d'identité (pièce recto et verso)",
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
     *                 @OA\Property(property="client", ref="#/components/schemas/ClientResource")
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

            $client = Auth::user();

            if (!$client) {
                return $this->unauthorizedResponse('Client non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle client
            if (!$client->hasRole(config('appconstants.role.client'))) {
                return $this->unauthorizedResponse('Accès réservé aux clients');
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
                    $oldFileId = $client->{$dbField};
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

            // Mettre à jour le client avec les nouveaux fichiers
            foreach ($newFileIds as $dbField => $fileId) {
                $client->update([$dbField => $fileId]);
            }

            DB::commit();

            // Rafraîchir le client pour inclure les nouvelles relations
            $client->refresh();

            // Retourner le client mis à jour avec ses nouveaux documents
            return $this->successResponse([
                'client' => new ClientResource($client)
            ], 'Documents mis à jour avec succès');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationResponse($e->errors());
        } catch (\Throwable $e) {
            DB::rollback();
            log_error("ClientController","updateDocuments",$e->getMessage());
            \Log::error('Erreur lors de la mise à jour des documents: ' . $e->getMessage());
            return $this->serverErrorResponse('Une erreur est survenue lors de la mise à jour des documents');
        }
    }

    /**
     * Mettre à jour le mot de passe du client connecté
     *
     * @OA\Post(
     *     path="/api/v1/clients/update-password",
     *     tags={"Client"},
     *     summary="Mettre à jour le mot de passe du client connecté",
     *     description="Permet à un client connecté de mettre à jour son mot de passe",
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

            $client = Auth::user();

            if (!$client) {
                return $this->unauthorizedResponse('Client non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle client
            if (!$client->hasRole(config('appconstants.role.client'))) {
                return $this->unauthorizedResponse('Accès réservé aux clients');
            }

            // Vérifier que le mot de passe actuel est correct
            if (!\Hash::check($validated['current_password'], $client->password)) {
                return $this->unauthorizedResponse('Le mot de passe actuel est incorrect');
            }

            // Vérifier que le nouveau mot de passe est différent de l'ancien
            if (\Hash::check($validated['new_password'], $client->password)) {
                return $this->validationResponse([
                    'new_password' => ['Le nouveau mot de passe doit être différent de l\'ancien mot de passe']
                ]);
            }

            DB::beginTransaction();

            // Mettre à jour le mot de passe
            $client->update([
                'password' => \Hash::make($validated['new_password']),
            ]);

            DB::commit();

            // Appeler logout pour invalider le token actuel
            return $this->logout($request);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationResponse($e->errors());
        } catch (\Throwable $e) {
            DB::rollback();
            log_error("ClientController","updatePassword",$e->getMessage());
            \Log::error('Erreur lors de la mise à jour du mot de passe: ' . $e->getMessage());
            return $this->serverErrorResponse('Une erreur est survenue lors de la mise à jour du mot de passe');
        }
    }

    /**
     * Déconnecter le client connecté
     *
     * @OA\Post(
     *     path="/api/v1/clients/logout",
     *     tags={"Client"},
     *     summary="Déconnecter le client connecté",
     *     description="Permet à un client connecté de se déconnecter et de révoquer son token d'accès",
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
     *             @OA\Property(property="message", type="string", example="Client non authentifié")
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
            $client = Auth::user();

            if (!$client) {
                return $this->unauthorizedResponse('Client non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle client
            if (!$client->hasRole(config('appconstants.role.client'))) {
                return $this->unauthorizedResponse('Accès réservé aux clients');
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
                        'user_id' => $client->id,
                        'expires_at' => date('Y-m-d H:i:s', $expiresAt),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Désactiver le token dans jwt_tokens s'il existe
                \DB::table('jwt_tokens')
                    ->where('token_id', $tokenId)
                    ->where('user_id', $client->id)
                    ->update([
                        'is_active' => false,
                        'updated_at' => now(),
                    ]);

                \Log::info('Token traité', [
                    'token_id' => $tokenId,
                    'user_id' => $client->id,
                    'blacklisted' => !$alreadyBlacklisted
                ]);
            } else {
                \Log::warning('Aucun token trouvé pour l\'invalidation');
            }

            return $this->successResponse([], 'Déconnexion réussie');

        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return $this->unauthorizedResponse('Token invalide');
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return $this->unauthorizedResponse('Token expiré');
        } catch (\Throwable $e) {
            log_error("ClientController","logout",$e->getMessage());
            \Log::error('Erreur lors de la déconnexion: ' . $e->getMessage());
            return $this->serverErrorResponse('Une erreur est survenue lors de la déconnexion');
        }
    }


    /**
     * Déconnecter tous les appareils du client connecté
     *
     * @OA\Post(
     *     path="/api/v1/clients/logout-all-devices",
     *     tags={"Client"},
     *     summary="Déconnecter tous les appareils connectés",
     *     description="Permet à un client connecté de révoquer tous ses tokens d'accès et de déconnecter tous ses appareils",
     *     operationId="logoutAllDevices",
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
     *             @OA\Property(property="message", type="string", example="Client non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès réservé aux clients")
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
    public function logoutAllDevices(Request $request): JsonResponse
    {
        try {
            $client = Auth::user();

            if (!$client) {
                return $this->unauthorizedResponse('Client non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle client
            if (!$client->hasRole(config('appconstants.role.client'))) {
                return $this->unauthorizedResponse('Accès réservé aux clients');
            }

            // Récupérer tous les tokens JWT actifs pour cet utilisateur
            $tokensRevoked = 0;

            // 1. Récupérer tous les tokens JWT actifs depuis jwt_tokens
            $activeTokens = \DB::table('jwt_tokens')
                ->where('user_id', $client->id)
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
                        'user_id' => $client->id,
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
                        ->where('user_id', $client->id)
                        ->exists();

                    // Vérifier si le token est déjà dans la blacklist
                    $alreadyBlacklisted = \DB::table('jwt_blacklist')
                        ->where('token', $tokenId)
                        ->exists();

                    if (!$tokenExists && !$alreadyBlacklisted) {
                        // Ajouter à la blacklist
                        \DB::table('jwt_blacklist')->insert([
                            'token' => $tokenId,
                            'user_id' => $client->id,
                            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $tokensRevoked++;
                    }
                } catch (\Exception $e) {
                    \Log::warning('Impossible de récupérer le payload du token actuel', [
                        'user_id' => $client->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // 3. Supprimer les sessions actives si elles existent
            \DB::table('sessions')
                ->where('user_id', $client->id)
                ->delete();

            // 4. Mettre à jour le FCM token à null pour forcer la déconnexion push
            $client->update(['fcm_token' => null]);

            \Log::info('Déconnexion de tous les appareils', [
                'user_id' => $client->id,
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
            log_error("ClientController","logoutAllDevices",$e->getMessage());
            \Log::error('Erreur lors de la déconnexion des appareils: ' . $e->getMessage());
            return $this->serverErrorResponse('Une erreur est survenue lors de la déconnexion des appareils');
        }
    }


    /**
     * Lister tous les appareils connectés du client
     *
     * @OA\Get(
     *     path="/api/v1/clients/connected-devices",
     *     tags={"Client"},
     *     summary="Lister tous les appareils connectés",
     *     description="Permet à un client connecté de voir tous ses appareils actuellement connectés avec leurs informations",
     *     operationId="listConnectedDevices",
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
     *             @OA\Property(property="message", type="string", example="Client non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès réservé aux clients")
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
    public function listConnectedDevices(Request $request): JsonResponse
    {
        try {
            $client = Auth::user();

            if (!$client) {
                return $this->unauthorizedResponse('Client non authentifié');
            }

            // Vérifier que l'utilisateur a le rôle client
            if (!$client->hasRole(config('appconstants.role.client'))) {
                return $this->unauthorizedResponse('Accès réservé aux clients');
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
                        'user_id' => $client->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Récupérer tous les tokens actifs de l'utilisateur
            $activeTokens = \DB::table('jwt_tokens')
                ->where('user_id', $client->id)
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
                'user_id' => $client->id,
                'total_devices' => count($devices),
                'active_devices' => $activeCount,
                'current_device_id' => $currentTokenId,
            ]);

            return $this->successResponse([
                'devices' => $devices,
                'total_devices' => count($devices),
                'active_devices' => $activeCount,
            ], 'Appareils connectés récupérés avec succès');

        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return $this->unauthorizedResponse('Token invalide');
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return $this->unauthorizedResponse('Token expiré');
        } catch (\Throwable $e) {
            log_error("ClientController","listConnectedDevices",$e->getMessage());
            \Log::error('Erreur lors de la récupération des appareils connectés: ' . $e->getMessage());
            return $this->serverErrorResponse('Une erreur est survenue lors de la récupération des appareils');
        }
    }


    /**
     * Update client profile.
     *
     * @OA\Patch(
     *     path="/api/v1/clients/update-client",
     *     summary="Mettre à jour le profil du client",
     *     description="Ce point d'accès met à jour les informations du profil client connecté.",
     *     tags={"Client"},
     *     operationId="updateClient",
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="nom",
     *                     type="string",
     *                     description="Nom de famille du client",
     *                     example="Doe"
     *                 ),
     *                 @OA\Property(
     *                     property="prenoms",
     *                     type="string",
     *                     description="Prénoms du client",
     *                     example="John"
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     format="email",
     *                     description="Adresse email",
     *                     example="john@example.com"
     *                 ),
     *                 @OA\Property(
     *                     property="type_piece",
     *                     type="string",
     *                     enum={"passport", "cni", "pc", "ai"},
     *                     description="Type de pièce d'identité",
     *                     example="cni"
     *                 ),
     *                 @OA\Property(
     *                     property="numero_piece",
     *                     type="string",
     *                     description="Numéro de la pièce d'identité",
     *                     example="123456789"
     *                 ),
     *                 @OA\Property(
     *                     property="date_delivrance",
     *                     type="string",
     *                     format="date",
     *                     description="Date de délivrance de la pièce",
     *                     example="2020-01-15"
     *                 ),
     *                 @OA\Property(
     *                     property="date_naissance",
     *                     type="string",
     *                     format="date",
     *                     description="Date de naissance",
     *                     example="1990-05-15"
     *                 ),
     *                 @OA\Property(
     *                     property="lieu_delivrance",
     *                     type="string",
     *                     description="Lieu de délivrance de la pièce",
     *                     example="Abidjan"
     *                 ),
     *                 @OA\Property(
     *                     property="lieu_naissance",
     *                     type="string",
     *                     description="Lieu de naissance",
     *                     example="Abidjan"
     *                 ),
     *                 @OA\Property(
     *                     property="photoprofile",
     *                     type="string",
     *                     format="binary",
     *                     description="Photo de profil (fichier image - max 2MB, formats: jpeg, png, jpg, gif, svg)"
     *                 ),
     *                 @OA\Property(
     *                     property="piecerecto",
     *                     type="string",
     *                     format="binary",
     *                     description="Recto de la pièce d'identité (fichier image - max 2MB, formats: jpeg, png, jpg, gif, svg)"
     *                 ),
     *                 @OA\Property(
     *                     property="pieceverso",
     *                     type="string",
     *                     format="binary",
     *                     description="Verso de la pièce d'identité (fichier image - max 2MB, formats: jpeg, png, jpg, gif, svg)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profil mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profil mis à jour avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/ClientResource")
     *             )
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
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="nom",
     *                     type="array",
     *                     @OA\Items(type="string", example="Le nom est obligatoire")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function updateClient(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'nom' => 'required|string|max:255',
                'prenoms' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'type_piece' => 'nullable|in:passport,cni,pc,ai',
                'numero_piece' => 'nullable|string',
                'date_delivrance' => 'nullable|date',
                'date_naissance' => 'nullable|date',
                'lieu_delivrance' => 'nullable|string',
                'lieu_naissance' => 'nullable|string',
                'photoprofile' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'piecerecto' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'pieceverso' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ], [
                'nom.required' => 'Le nom est obligatoire',
                'prenoms.required' => 'Les prénoms sont obligatoires',
                'email.email' => 'L\'adresse email doit être valide',
            ]);

            DB::beginTransaction();

            // Gestion des fichiers uploadés
            $fileFields = ['photoprofile_id', 'piecerecto_id', 'pieceverso_id'];
            $newFileIds = [];

            foreach ($fileFields as $field) {
                $fieldName = str_replace('_id', '', $field);
                if ($request->hasFile($fieldName)) {
                    $file = $request->file($fieldName);
                    $fileName = time() . '-' . \Str::random(10) . '.' . $file->extension();
                    $file->move(public_path('images'), $fileName);

                    $newFileIds[$field] = Fichier::create([
                        'nom' => $fileName,
                        'source' => 'images/' . $fileName,
                    ])->id;
                }
            }

            // Mise à jour des informations utilisateur
            $updateData = [
                    'nom' => $validated['nom'],
                    'prenoms' => $validated['prenoms'],
                    'email' => $validated['email'] ?? $user->email,
                    'type_piece' => $validated['type_piece'] ?? $user->type_piece,
                    'numero_piece' => $validated['numero_piece'] ?? $user->numero_piece,
                    'date_delivrance' => $validated['date_delivrance'] ?? $user->date_delivrance,
                    'date_naissance' => $validated['date_naissance'] ?? $user->date_naissance,
                    'lieu_delivrance' => $validated['lieu_delivrance'] ?? $user->lieu_delivrance,
                    'lieu_naissance' => $validated['lieu_naissance'] ?? $user->lieu_naissance,
                ] + $newFileIds;

            $user->update($updateData);

            DB::commit();

            return $this->successResponse([
                'user' => new ClientResource($user->fresh())
            ], 'Profil mis à jour avec succès');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            DB::rollback();
            log_error('ApiClientController','updateClient',$e->getMessage());
            return $this->errorResponse('Une erreur est survenue lors de la mise à jour', 500);
        }
    }

    /**
     * Envoyer un SMS de bienvenue au nouvel utilisateur
     *
     * @param User $user
     * @return void
     */
    private function sendWelcomeSms(User $user): void
    {
        try {
            // Construire le nom complet de l'utilisateur
            $fullName = trim($user->nom . ' ' . $user->prenoms);

            // Numéro de téléphone du destinataire
            $recipient = $user->full_login ?? $user->login;

            // Message de bienvenue personnalisé
            $message = "Bienvenue {$fullName} !\n"
                . "Votre compte SmilPay a été créé avec succès.\n"
                . "Votre login est : {$user->login}\n"
                . "Téléchargez l'application et connectez-vous avec votre code PIN.\n"
                . "Pour toute assistance, contactez notre support.";

            // Envoyer le SMS
            SMSApiProService::sendNewSms($recipient, $message);

            // Logger l'envoi du SMS
            \Log::info('SMS de bienvenue envoyé', [
                'user_id' => $user->id,
                'recipient' => $recipient,
                'message' => $message
            ]);

        } catch (\Throwable $e) {
            // Logger l'erreur mais ne pas bloquer l'inscription
            \Log::error('Erreur lors de l\'envoi du SMS de bienvenue', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            log_error("ApiClientController","sendWelcomeSms",$e->getMessage());
        }
    }
    /**
     * Display the authenticated client.
     *
     * @OA\Get(
     *     path="/api/v1/clients/me",
     *     tags={"Client"},
     *     summary="Afficher le client connecté",
     *     description="Retourne les détails du client authentifié",
     *     security={{"bearer_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Détails du client",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Détails du client"),
     *             @OA\Property(property="data", ref="#/components/schemas/ClientResource")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès interdit")
     * )
     */
    public function getClient(): JsonResponse
    {
        try {
            $client = auth()->user();

            // Charger les rôles pour la vérification
            $client->load('roles');

            // Vérifier si l'utilisateur est bien un client
            if (!$client->hasRole(config('appconstants.role.client'))) {
                return $this->errorResponse('Accès réservé aux clients', 403);
            }

            // Charger les relations
            $client->load(['pays', 'photoprofile', 'piecerecto', 'pieceverso', 'moyensPaiement.fournisseur']);

            // Charger les wallets du client
            $client->setRelation('wallets', Wallet::where('owner_id', $client->id)
                ->where('owner_type', Wallet::OWNER_TYPE_CLIENT)
                ->with('paysDevise')
                ->get());

            return $this->successResponse(new ClientResource($client), 'Détails du client');

        } catch (\Throwable $e) {
            log_error('ApiClientController', 'show', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }

    /**
     * Get authenticated client payment methods.
     *
     * @OA\Get(
     *     path="/api/v1/clients/me/moyens-paiement",
     *     tags={"Client"},
     *     summary="Lister les moyens de paiement du client connecté",
     *     description="Retourne la liste des moyens de paiement du client authentifié",
     *     security={{"bearer_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Moyens de paiement du client",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Moyens de paiement du client"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/MoyenPaiementResource"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès interdit")
     * )
     */
    public function moyensPaiement(): JsonResponse
    {
        try {
            $client = auth()->user();

            // Vérifier si l'utilisateur est bien un client
            if (!$client->hasRole(config('appconstants.role.client'))) {
                return $this->errorResponse('Accès réservé aux clients', 403);
            }

            $moyensPaiement = $client->moyensPaiement()
                ->with('fournisseur')
                ->orderBy('is_defaut', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse(
                MoyenPaiementResource::collection($moyensPaiement),
                'Moyens de paiement du client'
            );

        } catch (\Throwable $e) {
            log_error('ApiClientController', 'moyensPaiement', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }

    /**
     * Get authenticated client wallets.
     *
     * @OA\Get(
     *     path="/api/v1/clients/me/wallets",
     *     tags={"Client"},
     *     summary="Lister les wallets du client connecté",
     *     description="Retourne la liste des wallets du client authentifié",
     *     security={{"bearer_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Wallets du client",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Wallets du client"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/WalletResource"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès interdit")
     * )
     */
    public function wallets(): JsonResponse
    {
        try {
            $client = auth()->user();

            // Vérifier si l'utilisateur est bien un client
            if (!$client->hasRole(config('appconstants.role.client'))) {
                return $this->errorResponse('Accès réservé aux clients', 403);
            }

            $wallets = $client->wallets()->with('paysDevise')->get();

            return $this->successResponse(
                \Modules\Wallet\Resources\WalletResource::collection($wallets),
                'Wallets du client'
            );

        } catch (\Throwable $e) {
            log_error('ApiClientController', 'wallets', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }

    /**
     * Add a payment method for the authenticated client.
     *
     * @OA\Post(
     *     path="/api/v1/clients/moyens-paiement",
     *     tags={"Client"},
     *     summary="Ajouter un moyen de paiement",
     *     description="Ajoute un nouveau moyen de paiement pour le client authentifié",
     *     security={{"bearer_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", enum={"mm", "iban", "card", "wallet"}, example="mm"),
     *             @OA\Property(property="fournisseur_id", type="integer", example=1),
     *             @OA\Property(property="label", type="string", example="MTN - 0701****1234"),
     *             @OA\Property(property="identifiant", type="string", example="0701234567"),
     *             @OA\Property(property="is_defaut", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Moyen de paiement ajouté",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Moyen de paiement ajouté avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/MoyenPaiementResource")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function addMoyenPaiement(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'type' => 'required|in:mm,iban,card,wallet',
                'fournisseur_id' => 'nullable|exists:fournisseurs_paiement,id',
                'label' => 'nullable|string|max:255',
                'identifiant' => 'required|string|max:255',
                'is_defaut' => 'boolean'
            ], [
                'type.required' => 'Le type est obligatoire',
                'type.in' => 'Le type doit être: mm, iban, card ou wallet',
                'fournisseur_id.exists' => 'Le fournisseur sélectionné n\'existe pas',
                'identifiant.required' => 'L\'identifiant est obligatoire',
                'identifiant.max' => 'L\'identifiant ne doit pas dépasser 255 caractères'
            ]);

            $client = auth()->user();

            // Vérifier si le client est bien un client
            if (!$client->hasRole(config('appconstants.role.client'))) {
                return $this->errorResponse('Accès réservé aux clients', 403);
            }

            DB::beginTransaction();

            try {
                // Si c'est le moyen de paiement par défaut, désactiver les autres
                if ($validated['is_defaut'] ?? false) {
                    MoyenPaiement::where('users_id', $client->id)
                        ->update(['is_defaut' => false]);
                }

                // Créer le moyen de paiement
                $moyenPaiement = MoyenPaiement::create([
                    'users_id' => $client->id,
                    'fournisseur_id' => $validated['fournisseur_id'],
                    'type' => $validated['type'],
                    'label' => $validated['label'] ?? $this->generateLabel($validated),
                    'identifiant_chiffre' => $validated['identifiant'], // Sera chiffré automatiquement par le mutator
                    'is_defaut' => $validated['is_defaut'] ?? false,
                    'statut' => MoyenPaiement::STATUT_ACTIF
                ]);

                DB::commit();

                // Charger les relations pour la réponse
                $moyenPaiement->load('fournisseur');

                return $this->successResponse(
                    new MoyenPaiementResource($moyenPaiement),
                    'Moyen de paiement ajouté avec succès',
                    201
                );

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            log_error('ApiClientController', 'addMoyenPaiement', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }

    /**
     * Show a specific payment method.
     *
     * @OA\Get(
     *     path="/api/v1/clients/moyens-paiement/{id}",
     *     tags={"Client"},
     *     summary="Afficher un moyen de paiement",
     *     description="Affiche les détails d'un moyen de paiement spécifique",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du moyen de paiement",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du moyen de paiement",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Détails du moyen de paiement"),
     *             @OA\Property(property="data", ref="#/components/schemas/MoyenPaiementResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Moyen de paiement non trouvé"),
     *     @OA\Response(response=403, description="Accès interdit"),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function showMoyenPaiement(int $id): JsonResponse
    {
        try {
            $client = auth()->user();

            // Vérifier si le client est bien un client
            if (!$client->hasRole(config('appconstants.role.client'))) {
                return $this->errorResponse('Accès réservé aux clients', 403);
            }

            $moyenPaiement = MoyenPaiement::where('users_id', $client->id)
                ->where('id', $id)
                ->with('fournisseur')
                ->first();

            if (!$moyenPaiement) {
                return $this->errorResponse('Moyen de paiement non trouvé', 404);
            }

            return $this->successResponse(
                new MoyenPaiementResource($moyenPaiement),
                'Détails du moyen de paiement'
            );

        } catch (\Throwable $e) {
            log_error('ApiClientController', 'showMoyenPaiement', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }

    /**
     * Update a payment method.
     *
     * @OA\Put(
     *     path="/api/v1/clients/moyens-paiement/{id}",
     *     tags={"Client"},
     *     summary="Modifier un moyen de paiement",
     *     description="Modifie un moyen de paiement existant",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du moyen de paiement",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="label", type="string", example="MTN - 0701****1234"),
     *             @OA\Property(property="identifiant", type="string", example="0701234567"),
     *             @OA\Property(property="is_defaut", type="boolean", example=false),
     *             @OA\Property(property="statut", type="string", enum={"actif", "desactive"}, example="actif")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Moyen de paiement modifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Moyen de paiement modifié avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/MoyenPaiementResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Moyen de paiement non trouvé"),
     *     @OA\Response(response=403, description="Accès interdit"),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function updateMoyenPaiement(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'label' => 'nullable|string|max:255',
                'identifiant' => 'nullable|string|max:255',
                'is_defaut' => 'boolean',
                'statut' => 'nullable|in:actif,desactive'
            ], [
                'label.max' => 'Le label ne doit pas dépasser 255 caractères',
                'identifiant.max' => 'L\'identifiant ne doit pas dépasser 255 caractères',
                'statut.in' => 'Le statut doit être: actif ou desactive'
            ]);

            $client = auth()->user();

            // Vérifier si le client est bien un client
            if (!$client->hasRole(config('appconstants.role.client'))) {
                return $this->errorResponse('Accès réservé aux clients', 403);
            }

            $moyenPaiement = MoyenPaiement::where('users_id', $client->id)
                ->where('id', $id)
                ->first();

            if (!$moyenPaiement) {
                return $this->errorResponse('Moyen de paiement non trouvé', 404);
            }

            DB::beginTransaction();

            try {
                // Si c'est le moyen de paiement par défaut, désactiver les autres
                if ($validated['is_defaut'] ?? false) {
                    MoyenPaiement::where('users_id', $client->id)
                        ->where('id', '!=', $id)
                        ->update(['is_defaut' => false]);
                }

                // Mettre à jour les champs
                $updateData = [];

                if (isset($validated['label'])) {
                    $updateData['label'] = $validated['label'];
                }

                if (isset($validated['identifiant'])) {
                    $updateData['identifiant_chiffre'] = $validated['identifiant']; // Sera chiffré automatiquement
                }

                if (isset($validated['is_defaut'])) {
                    $updateData['is_defaut'] = $validated['is_defaut'];
                }

                if (isset($validated['statut'])) {
                    $updateData['statut'] = $validated['statut'];
                }

                $moyenPaiement->update($updateData);

                DB::commit();

                // Charger les relations pour la réponse
                $moyenPaiement->load('fournisseur');

                return $this->successResponse(
                    new MoyenPaiementResource($moyenPaiement),
                    'Moyen de paiement modifié avec succès'
                );

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            log_error('ApiClientController', 'updateMoyenPaiement', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }

    /**
     * Delete a payment method.
     *
     * @OA\Delete(
     *     path="/api/v1/clients/moyens-paiement/{id}",
     *     tags={"Client"},
     *     summary="Supprimer un moyen de paiement",
     *     description="Supprime un moyen de paiement existant",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du moyen de paiement",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Moyen de paiement supprimé",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Moyen de paiement supprimé avec succès")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Moyen de paiement non trouvé"),
     *     @OA\Response(response=403, description="Accès interdit"),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function deleteMoyenPaiement(int $id): JsonResponse
    {
        try {
            $client = auth()->user();

            // Vérifier si le client est bien un client
            if (!$client->hasRole(config('appconstants.role.client'))) {
                return $this->errorResponse('Accès réservé aux clients', 403);
            }

            $moyenPaiement = MoyenPaiement::where('users_id', $client->id)
                ->where('id', $id)
                ->first();

            if (!$moyenPaiement) {
                return $this->errorResponse('Moyen de paiement non trouvé', 404);
            }

            $moyenPaiement->delete();

            return $this->successResponse(
                null,
                'Moyen de paiement supprimé avec succès'
            );

        } catch (\Throwable $e) {
            log_error('ApiClientController', 'deleteMoyenPaiement', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }

    /**
     * Toggle the status of a payment method (activate/deactivate).
     *
     * @OA\Put(
     *     path="/api/v1/clients/moyens-paiement/{id}/toggle-statut",
     *     tags={"Client"},
     *     summary="Activer/Désactiver un moyen de paiement",
     *     description="Inverse le statut d'un moyen de paiement (actif/désactivé)",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du moyen de paiement",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statut modifié avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Moyen de paiement activé avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/MoyenPaiementResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Moyen de paiement non trouvé"),
     *     @OA\Response(response=403, description="Accès interdit"),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function toggleStatutMoyenPaiement(int $id): JsonResponse
    {
        try {
            $client = auth()->user();

            // Vérifier si le client est bien un client
            if (!$client->hasRole(config('appconstants.role.client'))) {
                return $this->errorResponse('Accès réservé aux clients', 403);
            }

            $moyenPaiement = MoyenPaiement::where('users_id', $client->id)
                ->where('id', $id)
                ->first();

            if (!$moyenPaiement) {
                return $this->errorResponse('Moyen de paiement non trouvé', 404);
            }

            DB::beginTransaction();

            try {
                // Inverser le statut
                $nouveauStatut = $moyenPaiement->statut === MoyenPaiement::STATUT_ACTIF
                    ? MoyenPaiement::STATUT_DESACTIVE
                    : MoyenPaiement::STATUT_ACTIF;

                $moyenPaiement->update(['statut' => $nouveauStatut]);

                DB::commit();

                $message = $nouveauStatut === MoyenPaiement::STATUT_ACTIF
                    ? 'Moyen de paiement activé avec succès.'
                    : 'Moyen de paiement désactivé avec succès.';

                // Charger les relations pour la réponse
                $moyenPaiement->load('fournisseur');

                return $this->successResponse(
                    new MoyenPaiementResource($moyenPaiement),
                    $message
                );

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Throwable $e) {
            log_error('ApiClientController', 'toggleStatutMoyenPaiement', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }

    /**
     * Toggle the default status of a payment method.
     *
     * @OA\Put(
     *     path="/api/v1/clients/moyens-paiement/{id}/toggle-defaut",
     *     tags={"Client"},
     *     summary="Définir/Retirer le statut par défaut",
     *     description="Définit un moyen de paiement comme par défaut ou retire ce statut",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du moyen de paiement",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statut par défaut modifié avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Le moyen de paiement a été défini comme moyen par défaut"),
     *             @OA\Property(property="data", ref="#/components/schemas/MoyenPaiementResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Moyen de paiement non trouvé"),
     *     @OA\Response(response=403, description="Accès interdit"),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function toggleDefautMoyenPaiement(int $id): JsonResponse
    {
        try {
            $client = auth()->user();

            // Vérifier si le client est bien un client
            if (!$client->hasRole(config('appconstants.role.client'))) {
                return $this->errorResponse('Accès réservé aux clients', 403);
            }

            $moyenPaiement = MoyenPaiement::where('users_id', $client->id)
                ->where('id', $id)
                ->first();

            if (!$moyenPaiement) {
                return $this->errorResponse('Moyen de paiement non trouvé', 404);
            }

            DB::beginTransaction();

            try {
                if ($moyenPaiement->is_defaut) {
                    // Retirer le statut par défaut
                    $moyenPaiement->update(['is_defaut' => false]);
                    $message = 'Le moyen de paiement n\'est plus le moyen par défaut.';
                } else {
                    // Définir comme par défaut et désactiver les autres
                    MoyenPaiement::where('users_id', $client->id)
                        ->update(['is_defaut' => false]);

                    $moyenPaiement->update(['is_defaut' => true]);
                    $message = 'Le moyen de paiement a été défini comme moyen par défaut.';
                }

                DB::commit();

                // Charger les relations pour la réponse
                $moyenPaiement->load('fournisseur');

                return $this->successResponse(
                    new MoyenPaiementResource($moyenPaiement),
                    $message
                );

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Throwable $e) {
            log_error('ApiClientController', 'toggleDefautMoyenPaiement', $e->getMessage());
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }


    /**
     * Generate a label for the payment method
     */
    private function generateLabel(array $data): string
    {
        $identifiant = $data['identifiant'];
        $type = $data['type'];

        // Masquer l'identifiant pour la sécurité
        if (strlen($identifiant) > 4) {
            $masked = substr($identifiant, 0, 4) . str_repeat('*', strlen($identifiant) - 4);
        } else {
            $masked = str_repeat('*', strlen($identifiant));
        }

        $typeLabel = match($type) {
            'mm' => 'Mobile Money',
            'iban' => 'IBAN',
            'card' => 'Carte',
            'wallet' => 'Wallet',
            default => 'Moyen de paiement'
        };

        if (isset($data['fournisseur_id'])) {
            $fournisseur = FournisseurPaiement::find($data['fournisseur_id']);
            if ($fournisseur) {
                return "{$fournisseur->nom} - {$masked}";
            }
        }

        return "{$typeLabel} - {$masked}";
    }


}
