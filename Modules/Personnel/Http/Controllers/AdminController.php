<?php

namespace Modules\Personnel\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Generator;
use App\Services\PasswordResetService;
use App\Services\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Modules\Parametrage\Entities\Fichier;
use Modules\Parametrage\Entities\Pays;
use Modules\Personnel\Entities\Admin;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:admin-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:admin-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:admin-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:admin-statut', ['only' => ['statut', 'suspendre', 'bloquer']]);
    }

    /**
     * Display a listing of admin.
     */
    public function index(Request $request)
    {
        try {
            $paysCurrent = auth()->user()->pays_id;

            $statut = $request->input('statut');
            $kyc_status = $request->input('kyc_status');
            $nom = $request->input('nom');
            $prenoms = $request->input('prenoms');
            $login = $request->input('login');
            $email = $request->input('email');
            $pays_id = $request->input('pays_id');

            // Filtres
            $filters = [
                'nom' => $nom,
                'prenoms' => $prenoms,
                'login' => $login,
                'email' => $email,
                'pays_id' => $pays_id,
                'statut' => $statut,
                'kyc_status' => $kyc_status,
            ];

            // Query des utilisateurs
            $query = Admin::with(['pays'])
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
            } elseif (!is_null($paysCurrent)) {
                $query->where('pays_id', $paysCurrent);
            }

            if (!empty($filters['kyc_status'])) {
                $query->where('kyc_status', $filters['kyc_status']);
            }

            if (!empty($filters['statut'])) {
                $query->where('statut', $filters['statut']);
            }

            $admin = $query->paginate(10)->withQueryString();

            // Transformer les utilisateurs pour Vue
            $admin->getCollection()->transform(function ($admin) {
                return [
                    'id' => $admin->id,
                    'uuid' => $admin->uuid,
                    'nom' => $admin->nom,
                    'prenoms' => $admin->prenoms,
                    'login' => $admin->login,
                    'email' => $admin->email,
                    'kyc_status' => $admin->kyc_status,
                    'statut' => $admin->statut,
                    'pays' => $admin->pays ? $admin->pays->libelle : null,
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
            $kycStatuts = [
                ['value' => 'non_verifie', 'label' => trans('kyc.non_verifie')],
                ['value' => 'en_attente', 'label' => trans('kyc.en_attente')],
                ['value' => 'verifie', 'label' => trans('kyc.verifie')],
                ['value' => 'rejete', 'label' => trans('kyc.rejete')],
            ];

            return Inertia::render('Personnel::Admin/Index', [
                'admin' => $admin,
                'pays' => Pays::select('id', 'libelle')->get(),
                'filters' => $filters,
                'paysCurrent' => $paysCurrent,
                'statuts' => $statuts,
                'kycStatuts' => $kycStatuts,
                'userStatuts' => config('appconstants.user_statut'),
                'kycStatutsConst' => config('appconstants.user_kyc_status'),
            ]);
        } catch (\Throwable $e) {
            log_error("Admin", "index", $e->getMessage());
            return redirect()->route('home')->with('error', trans('Erreur'));
        }
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        try {
            $paysCurrent = auth()->user()->pays_id;

            $payss = Pays::select('id', 'libelle', 'code')->orderBy('libelle')->get();

            $typePieces = self::getTranslatedConstants('type_piece', 'type_piece_label');

            return Inertia::render('Personnel::Admin/Create', [
                'pays' => $payss,
                'typePieces' => $typePieces,
                'paysCurrent' => $paysCurrent,
            ]);
        } catch (\Throwable $e) {
            log_error("Admin", "create", $e->getMessage());
            return redirect()->route('admin.index')->with('error', trans('Erreuraffichage'));
        }
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $paysCurrent = auth()->user()->pays_id;

        $validatedData = $request->validate([
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
            'photoprofile' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'pieceverso' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'piecerecto' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        try {
            DB::beginTransaction();
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

            $paysId = $paysCurrent ?? $validatedData['pays_id'];
            $pays = Pays::findOrFail($paysId);
            $indicatif = $pays->code;

            // Création de l'utilisateur
            $admin = Admin::create([
                'nom' => $validatedData['nom'],
                'prenoms' => $validatedData['prenoms'],
                'email' => $validatedData['email'],
                'login' => $validatedData['tel'],
                'full_login' => $indicatif . $validatedData['tel'],
                'password' => Hash::make($password),
                'pays_id' => $paysId,
                'uuid' => Generator::uuid(),
                'role' => 'admin',
                'alias_smil' => Generator::generateAliasSmil($validatedData['nom'], $validatedData['prenoms']),
                'lieu_delivrance' => $validatedData['lieu_delivrance'] ?? null,
                'date_delivrance' => $validatedData['date_delivrance'] ?? null,
                'date_naissance' => $validatedData['date_naissance'] ?? null,
                'lieu_naissance' => $validatedData['lieu_naissance'] ?? null,
                'type_piece' => $validatedData['type_piece'] ?? null,
                'numero_piece' => $validatedData['numero_piece'] ?? null,
                'code_owner' => Generator::codeOwner(),
                'qr_data' => Generator::QrCode($validatedData['tel']),
                'photoprofile_id' => $fileIds['photoprofile_id'] ?? null,
                'piecerecto_id' => $fileIds['piecerecto_id'] ?? null,
                'pieceverso_id' => $fileIds['pieceverso_id'] ?? null,
            ]);

            // Assignation du rôle
            $role = Role::findByName(config('appconstants.role.admin'), config('appconstants.guard.web', 'web'));
            $admin->assignRole($role);

            DB::commit();
            PasswordResetService::sendResetLinkSms($admin);

            return redirect()->route('admin.index')->with('success', __('enregistrementsucces'));
        } catch (\Throwable $e) {
            DB::rollback();
            log_error("Admin", "store", $e->getMessage());
            return redirect()->route('admin.create')->with('error', __('Erreur') . ': ' . $e->getMessage());
        }
    }

    /**
     * Display the specified user.
     */
    public function show($uuid)
    {
        try {
            $admin = Admin::with(['pays'])->where('uuid', $uuid)->firstOrFail();

            // Passer le KYC en "en_attente" si le statut actuel est "non_verifie"
            if ($admin->kyc_status === config('appconstants.user_kyc_status.non_verifie')) {
                $admin->update(['kyc_status' => config('appconstants.user_kyc_status.en_attente')]);
                $admin->refresh();
            }

            $typePieces = self::getTranslatedConstants('type_piece', 'type_piece_label');
            $payss = Pays::select('id', 'libelle')->get();

            return Inertia::render('Personnel::Admin/Show', [
                'admin' => $this->formatAdminForVue($admin),
                'pays' => $payss,
                'typePieces' => $typePieces,
                'kycStatuts' => config('appconstants.user_kyc_status'),
                'userStatuts' => config('appconstants.user_statut'),
            ]);
        } catch (\Throwable $e) {
            log_error("Admin", "show", $e->getMessage());
            return redirect()->route('admin.index')->with('error', trans('Erreuraffichage'));
        }
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit($uuid)
    {
        try {
            $admin = Admin::with(['pays'])->where('uuid', $uuid)->firstOrFail();
            $paysCurrent = auth()->user()->pays_id;
            $payss = Pays::select('id', 'libelle', 'code')->get();

            $typePieces = self::getTranslatedConstants('type_piece', 'type_piece_label');

            return Inertia::render('Personnel::Admin/Edit', [
                'admin' => $this->formatAdminForVue($admin),
                'pays' => $payss,
                'typePieces' => $typePieces,
                'paysCurrent' => $paysCurrent,
            ]);
        } catch (\Throwable $e) {
            log_error("Admin", "edit", $e->getMessage());
            return redirect()->route('admin.index')->with('error', trans('Erreuraffichage'));
        }
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $uuid)
    {
        $paysCurrent = auth()->user()->pays_id;

        try {
            $admin = Admin::where('uuid', $uuid)->firstOrFail();

            $validatedData = $request->validate([
                'nom' => 'required|string|max:255',
                'prenoms' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'tel' => 'required|string|max:255|unique:users,login,' . $admin->id,
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
            ]);
            DB::beginTransaction();

            // Gestion des fichiers
            $fileFields = [
                'photoprofile_id' => 'admin',
                'piecerecto_id' => 'admin',
                'pieceverso_id' => 'admin',
            ];
            $newFileIds = ['admin' => []];
            foreach ($fileFields as $inputName => $targetModel) {
                if ($request->hasFile($inputName)) {
                    $file = $request->file($inputName);
                    $fileName = time() . '-' . Str::random(10) . '.' . $file->extension();
                    $file->move(public_path('images'), $fileName);

                    $f = Fichier::create([
                        'nom' => $fileName,
                        'source' => 'images/' . $fileName,
                    ]);

                    $newFileIds[$targetModel][$inputName] = $f->id;
                }
            }
            // Préparer les données de mise à jour
            $paysId = $paysCurrent ?? $validatedData['pays_id'] ?? $admin->pays_id;
            $pays = Pays::find($paysId);
            $indicatif = $pays?->code ?? '';
            $admin->update([
                'nom' => $validatedData['nom'],
                'prenoms' => $validatedData['prenoms'],
                'email' => $validatedData['email'],
                'login' => $validatedData['tel'],
                'full_login' => $indicatif . $validatedData['tel'],
                'type_piece' => $validatedData['type_piece'],
                'numero_piece' => $validatedData['numero_piece'] ?? $admin->numero_piece,
                'date_delivrance' => $validatedData['date_delivrance'] ?? $admin->date_delivrance,
                'date_naissance' => $validatedData['date_naissance'] ?? $admin->date_naissance,
                'lieu_naissance' => $validatedData['lieu_naissance'] ?? $admin->lieu_naissance,
                'lieu_delivrance' => $validatedData['lieu_delivrance'] ?? $admin->lieu_delivrance,
                'pays_id' => $paysId,
            ] + $newFileIds['admin']);

             // Mise à jour du rôle
            $role = Role::findByName(config('appconstants.role.admin'), config('appconstants.guard.web', 'web'));
            $admin->syncRoles([$role]);
            DB::commit();

            return redirect()->route('admin.index')->with('success', __('modifsucces'));
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error("Admin", "update", $e->getMessage());
            return redirect()->route('admin.edit', $uuid)->with('error', __('erreurmaj'));
        }
    }

    /**
     * Toggle user status (soft delete/restore).
     */
    public function statut($uuid)
    {
        try {
            $admin = Admin::where('uuid', $uuid)->firstOrFail();

            if ($admin->trashed()) {
                $admin->restore();
                $message = __('restaurationsucces');
            } else {
                $admin->delete();
                $message = __('suppressionsucces');
            }

            return redirect()->route('admin.index')->with('success', $message);
        } catch (\Throwable $e) {
            log_error("Admin", "statut", $e->getMessage());
            return redirect()->route('admin.index')->with('error', __('erreurmaj'));
        }
    }

    public function validation(Request $request, $uuid, $action)
    {
        try {
            $motif = $request->input('motif');
            $admin = Admin::where('uuid', $uuid)->firstOrFail();

            if ($action === 'valider') {
                $result = ValidationService::validateStatut($admin, null, false);
            } elseif ($action === 'rejeter') {
                $result = ValidationService::rejectStatut($admin, $motif, null, false);
            } else {
                return redirect()->route('admin.index')->with('error', trans('actionnonreconnue'));
            }

            if (!$result['success']) {
                return redirect()->route('admin.index')->with('error', $result['message']);
            }

            return redirect()->route('admin.index')->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("Admin", "validation", $e->getMessage());
            return redirect()->route('admin.index')->with('error', trans('Erreur'));
        }
    }

    public function kycValidation(Request $request, $uuid, $action)
    {
        try {
            $motif = $request->input('motif');
            $admin = Admin::where('uuid', $uuid)->firstOrFail();

            if ($action === 'valider') {
                $result = ValidationService::validateKyc($admin);
            } elseif ($action === 'rejeter') {
                $result = ValidationService::rejectKyc($admin, $motif);
            } else {
                return redirect()->route('admin.show', $uuid)->with('error', trans('actionnonreconnue'));
            }

            if (!$result['success']) {
                return redirect()->route('admin.show', $uuid)->with('error', $result['message']);
            }

            return redirect()->route('admin.show', $uuid)->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("Admin", "kycValidation", $e->getMessage());
            return redirect()->route('admin.show', $uuid)->with('error', trans('Erreur'));
        }
    }

    public function suspendre(Request $request, $uuid)
    {
        try {
            $motif = $request->input('motif');
            $admin = Admin::where('uuid', $uuid)->firstOrFail();

            $result = ValidationService::suspendUser($admin, $motif);

            if (!$result['success']) {
                return redirect()->route('admin.index')->with('error', $result['message']);
            }

            return redirect()->route('admin.index')->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("Admin", "suspendre", $e->getMessage());
            return redirect()->route('admin.show', $uuid)->with('error', trans('Erreur'));
        }
    }

    public function bloquer(Request $request, $uuid)
    {
        try {
            $motif = $request->input('motif');
            $admin = Admin::where('uuid', $uuid)->firstOrFail();

            $result = ValidationService::blockUser($admin, $motif);

            if (!$result['success']) {
                return redirect()->route('admin.index')->with('error', $result['message']);
            }

            return redirect()->route('admin.index')->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("Admin", "bloquer", $e->getMessage());
            return redirect()->route('admin.show', $uuid)->with('error', trans('Erreur'));
        }
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

        $query = \App\Models\User::where('alias_smil', $alias);

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
     * Format admin for Vue component.
     */
    private function formatAdminForVue(Admin $admin): array
    {
        // Récupérer les fichiers
        $photoprofile = $admin->photoprofile_id ? Fichier::find($admin->photoprofile_id) : null;
        $piecerecto = $admin->piecerecto_id ? Fichier::find($admin->piecerecto_id) : null;
        $pieceverso = $admin->pieceverso_id ? Fichier::find($admin->pieceverso_id) : null;
        return [
            'id' => $admin->id,
            'uuid' => $admin->uuid,
            'nom' => $admin->nom,
            'prenoms' => $admin->prenoms,
            'login' => $admin->login,
            'full_login' => $admin->full_login,
            'email' => $admin->email,
            'pays_id' => $admin->pays_id,
            'kyc_status' => $admin->kyc_status,
            'code_owner' => $admin->code_owner,
            'code_parrain' => $admin->code_parrain,
            'alias_smil' => $admin->alias_smil,
            'type_piece' => $admin->type_piece,
            'numero_piece' => $admin->numero_piece,
            'date_delivrance' => $admin->date_delivrance,
            'date_naissance' => $admin->date_naissance,
            'lieu_delivrance' => $admin->lieu_delivrance,
            'lieu_naissance' => $admin->lieu_naissance,
            'adresse' => $admin->adresse,
            'statut' => $admin->statut,
            'roles' => $admin->getRoleNames()->toArray(),
            'current_role' => $admin->getRoleNames()->first(),
            'photoprofile' => $photoprofile ? asset('images/' . $photoprofile->nom) : null,
            'piecerecto' => $piecerecto ? asset('images/' . $piecerecto->nom) : null,
            'pieceverso' => $pieceverso ? asset('images/' . $pieceverso->nom) : null,
        ];
    }
}
