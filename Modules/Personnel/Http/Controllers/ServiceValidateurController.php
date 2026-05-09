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
use Modules\Personnel\Entities\ServiceValidateur;
use Spatie\Permission\Models\Role;

class ServiceValidateurController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:service_validateur-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:service_validateur-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:service_validateur-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:service_validateur-statut', ['only' => ['statut', 'suspendre', 'bloquer']]);
    }

    /**
     * Display a listing of service_validateur.
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
            $query = ServiceValidateur::with(['pays'])
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

            $service_validateur = $query->paginate(10)->withQueryString();

            // Transformer les utilisateurs pour Vue
            $service_validateur->getCollection()->transform(function ($service_validateur) {
                return [
                    'id' => $service_validateur->id,
                    'uuid' => $service_validateur->uuid,
                    'nom' => $service_validateur->nom,
                    'prenoms' => $service_validateur->prenoms,
                    'login' => $service_validateur->login,
                    'email' => $service_validateur->email,
                    'kyc_status' => $service_validateur->kyc_status,
                    'statut' => $service_validateur->statut,
                    'pays' => $service_validateur->pays ? $service_validateur->pays->libelle : null,
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

            return Inertia::render('Personnel::ServiceValidateur/Index', [
                'serviceValidateur' => $service_validateur,
                'pays' => Pays::select('id', 'libelle')->get(),
                'filters' => $filters,
                'paysCurrent' => $paysCurrent,
                'statuts' => $statuts,
                'kycStatuts' => $kycStatuts,
                'userStatuts' => config('appconstants.user_statut'),
                'kycStatutsConst' => config('appconstants.user_kyc_status'),
            ]);
        } catch (\Throwable $e) {
            log_error("ServiceValidateur", "index", $e->getMessage());
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

            return Inertia::render('Personnel::ServiceValidateur/Create', [
                'pays' => $payss,
                'typePieces' => $typePieces,
                'paysCurrent' => $paysCurrent,
            ]);
        } catch (\Throwable $e) {
            log_error("ServiceValidateur", "create", $e->getMessage());
            return redirect()->route('service_validateur.index')->with('error', trans('Erreuraffichage'));
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
            $service_validateur = ServiceValidateur::create([
                'nom' => $validatedData['nom'],
                'prenoms' => $validatedData['prenoms'],
                'email' => $validatedData['email'],
                'login' => $validatedData['tel'],
                'full_login' => $indicatif . $validatedData['tel'],
                'password' => Hash::make($password),
                'pays_id' => $paysId,
                'uuid' => Generator::uuid(),
                'role' => 'service_validateur',
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
            $role = Role::findByName(config('appconstants.role.service_validateur'), config('appconstants.guard.web', 'web'));
            $service_validateur->assignRole($role);

            DB::commit();
            PasswordResetService::sendResetLinkSms($service_validateur);

            return redirect()->route('service_validateur.index')->with('success', __('enregistrementsucces'));
        } catch (\Throwable $e) {
            DB::rollback();
            log_error("ServiceValidateur", "store", $e->getMessage());
            return redirect()->route('service_validateur.create')->with('error', __('Erreur') . ': ' . $e->getMessage());
        }
    }

    /**
     * Display the specified user.
     */
    public function show($uuid)
    {
        try {
            $service_validateur = ServiceValidateur::with(['pays'])->where('uuid', $uuid)->firstOrFail();

            // Passer le KYC en "en_attente" si le statut actuel est "non_verifie"
            if ($service_validateur->kyc_status === config('appconstants.user_kyc_status.non_verifie')) {
                $service_validateur->update(['kyc_status' => config('appconstants.user_kyc_status.en_attente')]);
                $service_validateur->refresh();
            }

            $typePieces = self::getTranslatedConstants('type_piece', 'type_piece_label');
            $payss = Pays::select('id', 'libelle')->get();
            return Inertia::render('Personnel::ServiceValidateur/Show', [
                'serviceValidateur' => $this->formatServiceValidateurForVue($service_validateur),
                'pays' => $payss,
                'typePieces' => $typePieces,
                'kycStatuts' => config('appconstants.user_kyc_status'),
                'userStatuts' => config('appconstants.user_statut'),
            ]);
        } catch (\Throwable $e) {
            log_error("ServiceValidateur", "show", $e->getMessage());
            return redirect()->route('service_validateur.index')->with('error', trans('Erreuraffichage'));
        }
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit($uuid)
    {
        try {
            $service_validateur = ServiceValidateur::with(['pays'])->where('uuid', $uuid)->firstOrFail();
            $paysCurrent = auth()->user()->pays_id;
            $payss = Pays::select('id', 'libelle', 'code')->get();

            $typePieces = self::getTranslatedConstants('type_piece', 'type_piece_label');

            return Inertia::render('Personnel::ServiceValidateur/Edit', [
                'serviceValidateur' => $this->formatServiceValidateurForVue($service_validateur),
                'pays' => $payss,
                'typePieces' => $typePieces,
                'paysCurrent' => $paysCurrent,
            ]);
        } catch (\Throwable $e) {
            log_error("ServiceValidateur", "edit", $e->getMessage());
            return redirect()->route('service_validateur.index')->with('error', trans('Erreuraffichage'));
        }
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $uuid)
    {
        $paysCurrent = auth()->user()->pays_id;
        try {
            $service_validateur = ServiceValidateur::where('uuid', $uuid)->firstOrFail();

            $validatedData = $request->validate([
                'nom' => 'required|string|max:255',
                'prenoms' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'tel' => 'required|string|max:255|unique:users,login,' . $service_validateur->id,
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
                'photoprofile_id' => 'serviceValidateur',
                'piecerecto_id' => 'serviceValidateur',
                'pieceverso_id' => 'serviceValidateur',
            ];
            $newFileIds = ['serviceValidateur' => []];
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
            $paysId = $paysCurrent ?? $validatedData['pays_id'] ?? $service_validateur->pays_id;
            $pays = Pays::find($paysId);
            $indicatif = $pays?->code ?? '';
            $service_validateur->update([
                'nom' => $validatedData['nom'],
                'prenoms' => $validatedData['prenoms'],
                'email' => $validatedData['email'],
                'login' => $validatedData['tel'],
                'full_login' => $indicatif . $validatedData['tel'],
                'type_piece' => $validatedData['type_piece'],
                'numero_piece' => $validatedData['numero_piece'] ?? $service_validateur->numero_piece,
                'date_delivrance' => $validatedData['date_delivrance'] ?? $service_validateur->date_delivrance,
                'date_naissance' => $validatedData['date_naissance'] ?? $service_validateur->date_naissance,
                'lieu_naissance' => $validatedData['lieu_naissance'] ?? $service_validateur->lieu_naissance,
                'lieu_delivrance' => $validatedData['lieu_delivrance'] ?? $service_validateur->lieu_delivrance,
                'pays_id' => $paysId,
            ] + $newFileIds['serviceValidateur']);

            // Mise à jour du rôle
            $role = Role::findByName(config('appconstants.role.service_validateur'), config('appconstants.guard.web', 'web'));
            $service_validateur->syncRoles([$role]);
            DB::commit();

            return redirect()->route('service_validateur.index')->with('success', __('modifsucces'));
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error("ServiceValidateur", "update", $e->getMessage());
            return redirect()->route('service_validateur.edit', $uuid)->with('error', __('erreurmaj'));
        }
    }

    /**
     * Toggle user status (soft delete/restore).
     */
    public function statut($uuid)
    {
        try {
            $service_validateur = ServiceValidateur::where('uuid', $uuid)->firstOrFail();

            if ($service_validateur->trashed()) {
                $service_validateur->restore();
                $message = __('restaurationsucces');
            } else {
                $service_validateur->delete();
                $message = __('suppressionsucces');
            }

            return redirect()->route('service_validateur.index')->with('success', $message);
        } catch (\Throwable $e) {
            log_error("ServiceValidateur", "statut", $e->getMessage());
            return redirect()->route('service_validateur.index')->with('error', __('erreurmaj'));
        }
    }

    public function validation(Request $request, $uuid, $action)
    {
        try {
            $motif = $request->input('motif');
            $service_validateur = ServiceValidateur::where('uuid', $uuid)->firstOrFail();

            if ($action === 'valider') {
                $result = ValidationService::validateStatut($service_validateur, null, false);
            } elseif ($action === 'rejeter') {
                $result = ValidationService::rejectStatut($service_validateur, $motif, null, false);
            } else {
                return redirect()->route('service_validateur.index')->with('error', trans('actionnonreconnue'));
            }

            if (!$result['success']) {
                return redirect()->route('service_validateur.index')->with('error', $result['message']);
            }

            return redirect()->route('service_validateur.index')->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("ServiceValidateur", "validation", $e->getMessage());
            return redirect()->route('service_validateur.index')->with('error', trans('Erreur'));
        }
    }

    public function kycValidation(Request $request, $uuid, $action)
    {
        try {
            $motif = $request->input('motif');
            $service_validateur = ServiceValidateur::where('uuid', $uuid)->firstOrFail();

            if ($action === 'valider') {
                $result = ValidationService::validateKyc($service_validateur);
            } elseif ($action === 'rejeter') {
                $result = ValidationService::rejectKyc($service_validateur, $motif);
            } else {
                return redirect()->route('service_validateur.show', $uuid)->with('error', trans('actionnonreconnue'));
            }

            if (!$result['success']) {
                return redirect()->route('service_validateur.show', $uuid)->with('error', $result['message']);
            }

            return redirect()->route('service_validateur.show', $uuid)->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("ServiceValidateur", "kycValidation", $e->getMessage());
            return redirect()->route('service_validateur.show', $uuid)->with('error', trans('Erreur'));
        }
    }

    public function suspendre(Request $request, $uuid)
    {
        try {
            $motif = $request->input('motif');
            $service_validateur = ServiceValidateur::where('uuid', $uuid)->firstOrFail();

            $result = ValidationService::suspendUser($service_validateur, $motif);

            if (!$result['success']) {
                return redirect()->route('service_validateur.index')->with('error', $result['message']);
            }

            return redirect()->route('service_validateur.index')->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("ServiceValidateur", "suspendre", $e->getMessage());
            return redirect()->route('service_validateur.show', $uuid)->with('error', trans('Erreur'));
        }
    }

    public function bloquer(Request $request, $uuid)
    {
        try {
            $motif = $request->input('motif');
            $service_validateur = ServiceValidateur::where('uuid', $uuid)->firstOrFail();

            $result = ValidationService::blockUser($service_validateur, $motif);

            if (!$result['success']) {
                return redirect()->route('service_validateur.index')->with('error', $result['message']);
            }

            return redirect()->route('service_validateur.index')->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("ServiceValidateur", "bloquer", $e->getMessage());
            return redirect()->route('service_validateur.show', $uuid)->with('error', trans('Erreur'));
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
     * Format service_validateur for Vue component.
     */
    private function formatServiceValidateurForVue(ServiceValidateur $service_validateur): array
    {
        // Récupérer les fichiers
        $photoprofile = $service_validateur->photoprofile_id ? Fichier::find($service_validateur->photoprofile_id) : null;
        $piecerecto = $service_validateur->piecerecto_id ? Fichier::find($service_validateur->piecerecto_id) : null;
        $pieceverso = $service_validateur->pieceverso_id ? Fichier::find($service_validateur->pieceverso_id) : null;
        return [
            'id' => $service_validateur->id,
            'uuid' => $service_validateur->uuid,
            'nom' => $service_validateur->nom,
            'prenoms' => $service_validateur->prenoms,
            'login' => $service_validateur->login,
            'full_login' => $service_validateur->full_login,
            'email' => $service_validateur->email,
            'pays_id' => $service_validateur->pays_id,
            'kyc_status' => $service_validateur->kyc_status,
            'code_owner' => $service_validateur->code_owner,
            'code_parrain' => $service_validateur->code_parrain,
            'alias_smil' => $service_validateur->alias_smil,
            'type_piece' => $service_validateur->type_piece,
            'numero_piece' => $service_validateur->numero_piece,
            'date_delivrance' => $service_validateur->date_delivrance,
            'date_naissance' => $service_validateur->date_naissance,
            'lieu_delivrance' => $service_validateur->lieu_delivrance,
            'lieu_naissance' => $service_validateur->lieu_naissance,
            'adresse' => $service_validateur->adresse,
            'statut' => $service_validateur->statut,
            'roles' => $service_validateur->getRoleNames()->toArray(),
            'current_role' => $service_validateur->getRoleNames()->first(),
            'photoprofile' => $photoprofile ? asset('images/' . $photoprofile->nom) : null,
            'piecerecto' => $piecerecto ? asset('images/' . $piecerecto->nom) : null,
            'pieceverso' => $pieceverso ? asset('images/' . $pieceverso->nom) : null,
        ];
    }
}
