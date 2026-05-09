<?php

namespace Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Generator;
use App\Services\PasswordResetService;
use App\Services\ValidationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Administration\Entities\Feature;
use Modules\Parametrage\Entities\Fichier;
use Modules\Parametrage\Entities\Pays;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:users-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:users-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:users-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:users-statut', ['only' => ['statut', 'suspendre', 'bloquer']]);
    }


    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        try {
            // Filtres
            $filters = [
                'nom' => $request->input('nom'),
                'prenoms' => $request->input('prenoms'),
                'login' => $request->input('login'),
                'email' => $request->input('email'),
                'role_id' => $request->input('role_id'),
                'pays_id' => $request->input('pays_id'),
                'kyc_status' => $request->input('kyc_status'),
                'statut' => $request->input('statut'),
            ];

            // Query des utilisateurs
            $query = User::with(['roles', 'pays'])
                ->orderBy('created_at', 'DESC');

            if (!empty($filters['nom'])) {
                $query->where('nom', 'LIKE', "%{$filters['nom']}%");
            }

            if (!empty($filters['prenoms'])) {
                $query->where('prenoms', 'LIKE', "%{$filters['prenoms']}%");
            }

            if (!empty($filters['login'])) {
                $query->where('login', 'LIKE', "%{$filters['login']}%");
            }

            if (!empty($filters['email'])) {
                $query->where('email', 'LIKE', "%{$filters['email']}%");
            }

            if (!empty($filters['pays_id'])) {
                $query->where('pays_id', $filters['pays_id']);
            }

            if (!empty($filters['role_id'])) {
                $query->whereHas('roles', function ($q) use ($filters) {
                    $q->where('roles.id', $filters['role_id']);
                });
            }

            if (!empty($filters['kyc_status'])) {
                $query->where('kyc_status', $filters['kyc_status']);
            }

            if (!empty($filters['statut'])) {
                $query->where('statut', $filters['statut']);
            }

            $users = $query->paginate(10)->withQueryString();

            // Transformer les utilisateurs pour Vue
            $users->getCollection()->transform(function ($user) {
                return [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'nom' => $user->nom,
                    'prenoms' => $user->prenoms,
                    'login' => $user->login,
                    'email' => $user->email,
                    'kyc_status' => $user->kyc_status,
                    'statut' => $user->statut,
                    'roles' => $user->getRoleNames()->toArray(),
                    'pays' => $user->pays ? $user->pays->libelle : null,
                ];
            });

            // Statuts pour le filtre
            $statuts = [
                ['value' => 'non_actif', 'label' => __('statuts.inactif')],
                ['value' => 'actif', 'label' => __('statuts.actif')],
                ['value' => 'suspendu', 'label' => __('statuts.suspendu')],
                ['value' => 'bloque', 'label' => __('statuts.bloque')],
                ['value' => 'supprime', 'label' => __('statuts.supprime')],
            ];

            return Inertia::render('Administration::Users/Index', [
                'users' => $users,
                'roles' => Role::all()->map(fn($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => __(config("appconstants.role_label.{$role->name}", $role->name)),
                ]),
                'pays' => Pays::select('id', 'libelle')->get(),
                'kyc_statuss' => self::getTranslatedConstants('user_kyc_status', 'user_kyc_status_label'),
                'statuts' => $statuts,
                'filters' => $filters,
            ]);
        }catch (\Throwable $th) {
            log_error("User", "index", $th->getMessage());
            return redirect()->route('home')->with('error', __('Erreur'));
        }
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        try {
            return Inertia::render('Administration::Users/Create', [
                'roles' => Role::all()->map(fn($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => __(config("appconstants.role_label.{$role->name}", $role->name)),
                ]),
                'pays' => Pays::select('id', 'libelle', 'code')->get(),
                'showPaysField' => auth()->user()->pays_id === null,
                'typePieces' => self::getTranslatedConstants('type_piece', 'type_piece_label'),
            ]);
        }catch (\Throwable $th) {
            log_error("User", "create", $th->getMessage());
            return redirect()->route('administration.users.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $payscurrent = auth()->user()->pays_id;

        $request->validate([
            'nom' => 'required|string|max:255',
            'prenoms' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'tel' => 'required|string|max:255',
            'role_id' => 'required',
            'photoprofile' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'pieceverso' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'piecerecto' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'pays_id' => ['exists:pays,id', $payscurrent === null ? 'required' : 'nullable'],
            'alias_smil' => 'required|string|max:255|unique:users,alias_smil',
            'numero_piece' => 'nullable|string|max:255',
            'date_delivrance' => 'nullable|date',
            'date_naissance' => 'nullable|date',
            'lieu_delivrance' => 'nullable|string|max:255',
            'lieu_naissance' => 'nullable|string|max:255',
            // 'adresse' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Gestion des fichiers
            $fileIds = $this->handleFileUploads($request);

            // Génération du mot de passe
            $password = $this->generatePassword(4);

            // Récupération du pays
            $pays = Pays::find($request->pays_id ?? $payscurrent);
            $indicatif = $pays ? $pays->code : '';


            // Création de l'utilisateur
            $user = User::create([
                'nom' => $request->nom,
                'prenoms' => $request->prenoms,
                'email' => $request->email,
                'login' => $request->tel,
                'full_login' => $indicatif . $request->tel,
                'password' => Hash::make($password),
                'qr_data' => Generator::QrCode($request->tel),
                'code_owner' => Generator::codeOwner(),
                'uuid' => Generator::uuid(),
                'pays_id' => $request->pays_id ?? $payscurrent,
                'email_verified_at' => Carbon::now(),
                'type_piece' => $request->type_piece,
                'role'=> array_search($request->role_id, config('appconstants.role')) ?: null,
                'statut' => config('appconstants.user_statut.actif'),
                'photoprofile_id' => $fileIds['photoprofile'] ?? null,
                'piecerecto_id' => $fileIds['piecerecto'] ?? null,
                'pieceverso_id' => $fileIds['pieceverso'] ?? null,
                'alias_smil' => $request->alias_smil,
                'numero_piece' => $request->numero_piece,
                'date_delivrance' => $request->date_delivrance,
                'date_naissance' => $request->date_naissance,
                'lieu_delivrance' => $request->lieu_delivrance,
                'lieu_naissance' => $request->lieu_naissance,
                // 'adresse' => $request->adresse,
            ]);

            // Assignation du rôle
            $role = Role::findByName($request->role_id, config('appconstants.guard.web', 'web'));
            $user->assignRole($role);

            DB::commit();
            PasswordResetService::sendResetLinkSms($user);

            return redirect()->route('administration.users.index')->with('success', __('enregistrementsucces'));
        } catch (\Throwable $e) {
            DB::rollback();
            log_error("User", "store", $e->getMessage());
            return redirect()->route('administration.users.create')->with('error', __('Erreur') . ': ' . $e->getMessage());
        }
    }

    /**
     * Display the specified user.
     */
    public function show($uuid): Response
    {
        try {
            $user = User::with(['roles', 'pays'])->where('uuid', $uuid)->firstOrFail();

            return Inertia::render('Administration::Users/Show', [
                'user' => $this->formatUserForVue($user),
                'roles' => Role::all()->map(fn($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => __(config("appconstants.role_label.{$role->name}", $role->name)),
                ]),
                'pays' => Pays::select('id', 'libelle', 'code')->get(),
                'showPaysField' => auth()->user()->pays_id === null,
                'typePieces' => self::getTranslatedConstants('type_piece', 'type_piece_label'),
                'kycStatuts' => self::getTranslatedConstants('user_kyc_status', 'user_kyc_status_label'),
                'userStatuts' => config('appconstants.user_statut'),
            ]);
        }catch (\Throwable $th) {
            log_error("User", "show", $th->getMessage());
            return redirect()->route('administration.users.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit($uuid): Response
    {
        try {
            $user = User::with(['roles', 'pays'])->where('uuid', $uuid)->firstOrFail();

            return Inertia::render('Administration::Users/Edit', [
                'user' => $this->formatUserForVue($user),
                'roles' => Role::all()->map(fn($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => __(config("appconstants.role_label.{$role->name}", $role->name)),
                ]),
                'pays' => Pays::select('id', 'libelle', 'code')->get(),
                'showPaysField' => auth()->user()->pays_id === null,
                'isAdmin' => auth()->user()->hasRole('Super Admin') || auth()->user()->hasRole('Admin'),
                'isSuperAdmin' => auth()->user()->hasRole('Super Admin'),
                'statuts' => self::getTranslatedConstants('user_statut', 'user_statut_label'),
                'kycStatuts' => self::getTranslatedConstants('user_kyc_status', 'user_kyc_status_label'),
                'typePieces' => self::getTranslatedConstants('type_piece', 'type_piece_label'),
            ]);
        }catch (\Throwable $th) {
            log_error("User", "edit", $th->getMessage());
            return redirect()->route('administration.users.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $uuid)
    {
        $payscurrent = auth()->user()->pays_id;
        $user = User::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'nom' => 'required|string|max:255',
            'prenoms' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'tel' => 'required|string|max:255',
            'role_id' => 'required',
            'photoprofile' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'pieceverso' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'piecerecto' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'pays_id' => ['exists:pays,id', $payscurrent === null ? 'required' : 'nullable'],
            'alias_smil' => 'required|string|max:255|unique:users,alias_smil,'.$user->id,
            'type_piece' => 'nullable|string|max:255',
            'numero_piece' => 'nullable|string|max:255',
            'date_delivrance' => 'nullable|date',
            'date_naissance' => 'nullable|date',
            'lieu_delivrance' => 'nullable|string|max:255',
            'lieu_naissance' => 'nullable|string|max:255',
            // 'adresse' => 'nullable|string|max:500',
            'statut' => 'nullable|string',
            'kyc_status' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Gestion des fichiers
            $fileIds = $this->handleFileUploads($request, $user);

            // Préparer les données de mise à jour
            $updateData = [
                'nom' => $request->nom,
                'prenoms' => $request->prenoms,
                'email' => $request->email,
                'login' => $request->tel,
                'pays_id' => $request->pays_id ?? $user->pays_id,
                'photoprofile_id' => $fileIds['photoprofile'] ?? $user->photoprofile_id,
                'piecerecto_id' => $fileIds['piecerecto'] ?? $user->piecerecto_id,
                'pieceverso_id' => $fileIds['pieceverso'] ?? $user->pieceverso_id,
                'type_piece' => $request->type_piece,
                'numero_piece' => $request->numero_piece,
                'date_delivrance' => $request->date_delivrance,
                'date_naissance' => $request->date_naissance,
                'lieu_delivrance' => $request->lieu_delivrance,
                'lieu_naissance' => $request->lieu_naissance,
                // 'adresse' => $request->adresse,
            ];

            // alias_smil modifiable uniquement si kyc_status n'est pas "verifie"
            if ($user->kyc_status !== config('appconstants.user_kyc_status.verifie')) {
                $updateData['alias_smil'] = $request->alias_smil;
            }
            // statut modifiable uniquement par admin
            $isAdmin = auth()->user()->hasRole(config('appconstants.role.admin')) || auth()->user()->hasRole(config('appconstants.role.superadmin'));
            if ($isAdmin && $request->has('statut')) {
                $updateData['statut'] = $request->statut;
            }

            // kyc_status modifiable uniquement par Super Admin
            $isSuperAdmin = auth()->user()->hasRole(config('appconstants.role.superadmin'));
            if ($isSuperAdmin && $request->has('kyc_status')) {
                $updateData['kyc_status'] = $request->kyc_status;
            }

            // Mise à jour de l'utilisateur
            $user->update($updateData);

            // Mise à jour du rôle
            $role = Role::findByName($request->role_id, config('appconstants.guard.web', 'web'));
            $user->syncRoles([$role]);

            DB::commit();

            return redirect()->route('administration.users.index')->with('success', __('modifsucces'));
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error("User", "update", $e->getMessage());
            return redirect()->route('administration.users.edit', $uuid)->with('error', __('erreurmaj'));
        }
    }

    /**
     * Toggle user status (soft delete/restore).
     */
    public function statut($id)
    {
        try {
            $user = User::withTrashed()->findOrFail($id);

            if ($user->trashed()) {
                $user->restore();
                $message = __('restaurationsucces');
            } else {
                $user->delete();
                $message = __('suppressionsucces');
            }

            return redirect()->route('administration.users.index')->with('success', $message);
        } catch (\Throwable $e) {
            log_error("User", "statut", $e->getMessage());
            return redirect()->route('administration.users.index')->with('error', __('erreurmaj'));
        }
    }

    /**
     * Edit user profile.
     */
    public function editprofile($id): Response
    {
        try {
            $user = User::with(['roles', 'pays'])->findOrFail($id);

            return Inertia::render('Administration::Users/EditProfile', [
                'user' => $this->formatUserForVue($user),
                'roles' => Role::all()->map(fn($role) => ['id' => $role->id, 'name' => $role->name]),
                'pays' => Pays::select('id', 'libelle', 'code')->get(),
            ]);
        }catch (\Throwable $th) {
            log_error("User", "editprofile", $th->getMessage());
            return redirect()->route('home')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Update user profile.
     */
    public function updateprofile(Request $request, $id)
    {
        $request->validate([
            'nom' => 'required',
            'prenoms' => 'required',
            'email' => 'nullable|email',
            'alias_smil' => 'nullable|string|max:255|unique:users,alias_smil,' . $id,
            'photoprofile' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        try {
            $user = User::findOrFail($id);

            // Gestion de la photo de profil
            $photoId = $user->photoprofile_id;
            if ($request->hasFile('photoprofile')) {
                $photoId = $this->uploadFile($request->file('photoprofile'), $user->photoprofile_id);
            }

            // Gestion de l'alias_smil
            $aliasSmil = $request->alias_smil;
            if (empty($aliasSmil)) {
                // Générer automatiquement si vide
                $aliasSmil = Generator::generateAliasSmil($request->nom, $request->prenoms, $id);
            }

            $user->update([
                'nom' => $request->nom,
                'prenoms' => $request->prenoms,
                'email' => $request->email,
                'alias_smil' => $aliasSmil,
                'photoprofile_id' => $photoId,
            ]);

            return redirect()->route('administration.users.editprofile', $id)->with('success', __('modifsucces'));
        } catch (\Throwable $e) {
            log_error("User", "updateprofile", $e->getMessage());
            return redirect()->route('administration.users.editprofile', $id)->with('error', __('erreurmaj'));
        }
    }

    /**
     * Handle file uploads.
     */
    private function handleFileUploads(Request $request, ?User $existingUser = null): array
    {
        $fileIds = [];
        $files = ['photoprofile', 'piecerecto', 'pieceverso'];

        foreach ($files as $fileKey) {
            if ($request->hasFile($fileKey)) {
                $existingFileId = $existingUser ? $existingUser->{$fileKey . '_id'} : null;
                $fileIds[$fileKey] = $this->uploadFile($request->file($fileKey), $existingFileId);
            }
        }

        return $fileIds;
    }

    /**
     * Upload a single file.
     */
    private function uploadFile($file, $existingFileId = null): int
    {
        if (!$file || !$file->isValid()) {
            return $existingFileId ?? 0;
        }

        $fileName = time() . '-' . Str::random(10) . '.' . $file->getClientOriginalExtension();

        // S'assurer que le dossier existe
        $destPath = public_path('images');
        if (!file_exists($destPath)) {
            mkdir($destPath, 0755, true);
        }

        // Copier le fichier (plus fiable que move avec les fichiers temporaires)
        copy($file->getRealPath(), $destPath . '/' . $fileName);

        if ($existingFileId) {
            DB::table('fichier')
                ->where('id', $existingFileId)
                ->update([
                    'nom' => $fileName,
                    'source' => 'images/' . $fileName,
                    'updated_at' => now(),
                ]);
            return $existingFileId;
        }

        $id = DB::table('fichier')->insertGetId([
            'nom' => $fileName,
            'source' => 'images/' . $fileName,
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    /**
     * Generate random password.
     */
    private function generatePassword(int $length = 4): string
    {
        $numbers = '0123456789';
        $characters = str_shuffle($numbers);
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $password;
    }

    /**
     * Check if alias_smil is unique.
     */
    public function checkAliasSmil(Request $request)
    {
        $request->validate([
            'alias_smil' => 'required|string|max:255',
            'user_id' => 'nullable|integer',
        ]);

        $alias = $request->alias_smil;
        $userId = $request->user_id;

        $query = User::where('alias_smil', $alias);

        // Exclure l'utilisateur actuel en cas de modification
        if ($userId) {
            $query->where('id', '!=', $userId);
        }

        $exists = $query->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? __('aliasuse') : null,
        ]);
    }

    /**
     * Suspendre un utilisateur.
     */
    public function suspendre(Request $request, $id)
    {
        try {
            $motif = $request->input('motif');
            $user = User::findOrFail($id);

            $result = ValidationService::suspendUser($user, $motif);

            if (!$result['success']) {
                return redirect()->route('administration.users.index')->with('error', $result['message']);
            }

            return redirect()->route('administration.users.index')->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("User", "suspendre", $e->getMessage());
            return redirect()->route('administration.users.index')->with('error', __('Erreur'));
        }
    }

    /**
     * Bloquer un utilisateur.
     */
    public function bloquer(Request $request, $id)
    {
        try {
            $motif = $request->input('motif');
            $user = User::findOrFail($id);

            $result = ValidationService::blockUser($user, $motif);

            if (!$result['success']) {
                return redirect()->route('administration.users.index')->with('error', $result['message']);
            }

            return redirect()->route('administration.users.index')->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("User", "bloquer", $e->getMessage());
            return redirect()->route('administration.users.index')->with('error', __('Erreur'));
        }
    }

    /**
     * Format user for Vue component.
     */
    private function formatUserForVue(User $user): array
    {
        // Récupérer les fichiers depuis la table fichier (sans s) — FK pointe vers fichier
        $photoprofile = $user->photoprofile_id ? DB::table('fichier')->find($user->photoprofile_id) : null;
        $piecerecto = $user->piecerecto_id ? DB::table('fichier')->find($user->piecerecto_id) : null;
        $pieceverso = $user->pieceverso_id ? DB::table('fichier')->find($user->pieceverso_id) : null;

        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'nom' => $user->nom,
            'prenoms' => $user->prenoms,
            'login' => $user->login,
            'full_login' => $user->full_login,
            'email' => $user->email,
            'pays_id' => $user->pays_id,
            'kyc_status' => $user->kyc_status,
            'code_owner' => $user->code_owner,
            'code_parrain' => $user->code_parrain,
            'alias_smil' => $user->alias_smil,
            'type_piece' => $user->type_piece,
            'numero_piece' => $user->numero_piece,
            'date_delivrance' => $user->date_delivrance,
            'date_naissance' => $user->date_naissance,
            'lieu_delivrance' => $user->lieu_delivrance,
            'lieu_naissance' => $user->lieu_naissance,
            'adresse' => $user->adresse,
            'statut' => $user->statut,
            'roles' => $user->getRoleNames()->toArray(),
            'current_role' => $user->getRoleNames()->first(),
            'photoprofile' => $photoprofile ? asset($photoprofile->source) : null,
            'piecerecto' => $piecerecto ? asset($piecerecto->source) : null,
            'pieceverso' => $pieceverso ? asset($pieceverso->source) : null,
        ];
    }
}
