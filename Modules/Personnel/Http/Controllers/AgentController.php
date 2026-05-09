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
use Modules\Parametrage\Entities\PaysDevise;
use Modules\Personnel\Entities\Agent;
use Modules\Wallet\Entities\Wallet;
use Modules\Wallet\Services\WalletService;
use Spatie\Permission\Models\Role;

class AgentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:agent-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:agent-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:agent-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:agent-statut', ['only' => ['statut', 'suspendre', 'bloquer']]);
    }

    /**
     * Display a listing of agent.
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
            $query = Agent::with(['pays'])
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

            $agent = $query->paginate(10)->withQueryString();

            // Transformer les utilisateurs pour Vue
            $agent->getCollection()->transform(function ($agent) {
                return [
                    'id' => $agent->id,
                    'uuid' => $agent->uuid,
                    'nom' => $agent->nom,
                    'prenoms' => $agent->prenoms,
                    'login' => $agent->login,
                    'email' => $agent->email,
                    'kyc_status' => $agent->kyc_status,
                    'statut' => $agent->statut,
                    'pays' => $agent->pays ? $agent->pays->libelle : null,
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

            return Inertia::render('Personnel::Agent/Index', [
                'agent' => $agent,
                'pays' => Pays::select('id', 'libelle')->get(),
                'filters' => $filters,
                'paysCurrent' => $paysCurrent,
                'statuts' => $statuts,
                'kycStatuts' => $kycStatuts,
                'userStatuts' => config('appconstants.user_statut'),
                'kycStatutsConst' => config('appconstants.user_kyc_status'),
            ]);
        } catch (\Throwable $e) {
            log_error("Agent", "index", $e->getMessage());
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

            return Inertia::render('Personnel::Agent/Create', [
                'pays' => $payss,
                'typePieces' => $typePieces,
                'paysCurrent' => $paysCurrent,
            ]);
        } catch (\Throwable $e) {
            log_error("Agent", "create", $e->getMessage());
            return redirect()->route('agent.index')->with('error', trans('Erreuraffichage'));
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

            // Création de l'utilisateur
            $agent = Agent::create([
                'nom' => $validatedData['nom'],
                'prenoms' => $validatedData['prenoms'],
                'email' => $validatedData['email'],
                'login' => $validatedData['tel'],
                'full_login' => $indicatif . $validatedData['tel'],
                'password' => Hash::make($password),
                'pays_id' => $paysId,
                'role' => 'agent',
                'uuid' => Generator::uuid(),
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
            $role = Role::findByName(config('appconstants.role.agent'), config('appconstants.guard.web', 'web'));
            $agent->assignRole($role);

            // Création des wallets
            WalletService::createWalletsForOwner($agent->id, 'agent', $paysId);

            DB::commit();
            PasswordResetService::sendResetLinkSms($agent);

            return redirect()->route('agent.index')->with('success', __('enregistrementsucces'));
        } catch (\Throwable $e) {
            DB::rollback();
            log_error("Agent", "store", $e->getMessage());
            return redirect()->route('agent.create')->with('error', __('Erreur') . ': ' . $e->getMessage());
        }
    }

    /**
     * Display the specified user.
     */
    public function show($uuid)
    {
        try {
            $agent = Agent::with(['pays'])->where('uuid', $uuid)->firstOrFail();

            // Passer le KYC en "en_attente" si le statut actuel est "non_verifie"
            if ($agent->kyc_status === config('appconstants.user_kyc_status.non_verifie')) {
                $agent->update(['kyc_status' => config('appconstants.user_kyc_status.en_attente')]);
                $agent->refresh();
            }

            $typePieces = self::getTranslatedConstants('type_piece', 'type_piece_label');
            $payss = Pays::select('id', 'libelle')->get();
            return Inertia::render('Personnel::Agent/Show', [
                'agent' => $this->formatAgentForVue($agent),
                'pays' => $payss,
                'typePieces' => $typePieces,
                'kycStatuts' => config('appconstants.user_kyc_status'),
                'userStatuts' => config('appconstants.user_statut'),
            ]);
        } catch (\Throwable $e) {
            log_error("Agent", "show", $e->getMessage());
            return redirect()->route('agent.index')->with('error', trans('Erreuraffichage'));
        }
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit($uuid)
    {
        try {
            $agent = Agent::with(['pays'])->where('uuid', $uuid)->firstOrFail();
            $paysCurrent = auth()->user()->pays_id;
            $payss = Pays::select('id', 'libelle', 'code')->get();

            $typePieces = self::getTranslatedConstants('type_piece', 'type_piece_label');

            return Inertia::render('Personnel::Agent/Edit', [
                'agent' => $this->formatAgentForVue($agent),
                'pays' => $payss,
                'typePieces' => $typePieces,
                'paysCurrent' => $paysCurrent,
            ]);
        } catch (\Throwable $e) {
            log_error("Agent", "edit", $e->getMessage());
            return redirect()->route('agent.index')->with('error', trans('Erreuraffichage'));
        }
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $uuid)
    {
        $paysCurrent = auth()->user()->pays_id;
        try {
            $agent = Agent::where('uuid', $uuid)->firstOrFail();

            $validatedData = $request->validate([
                'nom' => 'required|string|max:255',
                'prenoms' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'tel' => 'required|string|max:255|unique:users,login,' . $agent->id,
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
                'photoprofile_id' => 'agent',
                'piecerecto_id' => 'agent',
                'pieceverso_id' => 'agent',
            ];
            $newFileIds = ['agent' => []];
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
            $paysId = $paysCurrent ?? $validatedData['pays_id'] ?? $agent->pays_id;
            $pays = Pays::find($paysId);
            $indicatif = $pays?->code ?? '';
            $agent->update([
                'nom' => $validatedData['nom'],
                'prenoms' => $validatedData['prenoms'],
                'email' => $validatedData['email'],
                'login' => $validatedData['tel'],
                'full_login' => $indicatif . $validatedData['tel'],
                'type_piece' => $validatedData['type_piece'],
                'numero_piece' => $validatedData['numero_piece'] ?? $agent->numero_piece,
                'date_delivrance' => $validatedData['date_delivrance'] ?? $agent->date_delivrance,
                'date_naissance' => $validatedData['date_naissance'] ?? $agent->date_naissance,
                'lieu_naissance' => $validatedData['lieu_naissance'] ?? $agent->lieu_naissance,
                'lieu_delivrance' => $validatedData['lieu_delivrance'] ?? $agent->lieu_delivrance,
                'pays_id' => $paysId,
            ] + $newFileIds['agent']);
            // Vérifier si l'agent a des wallets, sinon les créer
            if (!Wallet::where('owner_type', 'agent')
                ->where('owner_id', $agent->id)
                ->exists()) {
                WalletService::createWalletsForOwner($agent->id, 'agent', $paysId);
            }

            // Mise à jour du rôle
            $role = Role::findByName(config('appconstants.role.agent'), config('appconstants.guard.web', 'web'));
            $agent->syncRoles([$role]);
            DB::commit();

            return redirect()->route('agent.index')->with('success', __('modifsucces'));
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error("Agent", "update", $e->getMessage());
            return redirect()->route('agent.edit', $uuid)->with('error', __('erreurmaj'));
        }
    }

    /**
     * Toggle user status (soft delete/restore).
     */
    public function statut($uuid)
    {
        try {
            $agent = Agent::where('uuid', $uuid)->firstOrFail();

            if ($agent->trashed()) {
                $agent->restore();
                $message = __('restaurationsucces');
            } else {
                $agent->delete();
                $message = __('suppressionsucces');
            }

            return redirect()->route('agent.index')->with('success', $message);
        } catch (\Throwable $e) {
            log_error("Agent", "statut", $e->getMessage());
            return redirect()->route('agent.index')->with('error', __('erreurmaj'));
        }
    }

    public function validation(Request $request, $uuid, $action)
    {
        try {
            $motif = $request->input('motif');
            $agent = Agent::where('uuid', $uuid)->firstOrFail();

            if ($action === 'valider') {
                $result = ValidationService::validateStatut($agent, null, false);
            } elseif ($action === 'rejeter') {
                $result = ValidationService::rejectStatut($agent, $motif, null, false);
            } else {
                return redirect()->route('agent.index')->with('error', trans('actionnonreconnue'));
            }

            if (!$result['success']) {
                return redirect()->route('agent.index')->with('error', $result['message']);
            }

            return redirect()->route('agent.index')->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("Agent", "validation", $e->getMessage());
            return redirect()->route('agent.index')->with('error', trans('Erreur'));
        }
    }

    public function kycValidation(Request $request, $uuid, $action)
    {
        try {
            $motif = $request->input('motif');
            $agent = Agent::where('uuid', $uuid)->firstOrFail();

            if ($action === 'valider') {
                $result = ValidationService::validateKyc($agent);
            } elseif ($action === 'rejeter') {
                $result = ValidationService::rejectKyc($agent, $motif);
            } else {
                return redirect()->route('agent.show', $uuid)->with('error', trans('actionnonreconnue'));
            }

            if (!$result['success']) {
                return redirect()->route('agent.show', $uuid)->with('error', $result['message']);
            }

            return redirect()->route('agent.show', $uuid)->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("Agent", "kycValidation", $e->getMessage());
            return redirect()->route('agent.show', $uuid)->with('error', trans('Erreur'));
        }
    }

    public function suspendre(Request $request, $uuid)
    {
        try {
            $motif = $request->input('motif');
            $agent = Agent::where('uuid', $uuid)->firstOrFail();

            $result = ValidationService::suspendUser($agent, $motif);

            if (!$result['success']) {
                return redirect()->route('agent.index')->with('error', $result['message']);
            }

            return redirect()->route('agent.index')->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("Agent", "suspendre", $e->getMessage());
            return redirect()->route('agent.show', $uuid)->with('error', trans('Erreur'));
        }
    }

    public function bloquer(Request $request, $uuid)
    {
        try {
            $motif = $request->input('motif');
            $agent = Agent::where('uuid', $uuid)->firstOrFail();

            $result = ValidationService::blockUser($agent, $motif);

            if (!$result['success']) {
                return redirect()->route('agent.index')->with('error', $result['message']);
            }

            return redirect()->route('agent.index')->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("Agent", "bloquer", $e->getMessage());
            return redirect()->route('agent.show', $uuid)->with('error', trans('Erreur'));
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
     * Format agent for Vue component.
     */
    private function formatAgentForVue(Agent $agent): array
    {
        // Récupérer les fichiers
        $photoprofile = $agent->photoprofile_id ? Fichier::find($agent->photoprofile_id) : null;
        $piecerecto = $agent->piecerecto_id ? Fichier::find($agent->piecerecto_id) : null;
        $pieceverso = $agent->pieceverso_id ? Fichier::find($agent->pieceverso_id) : null;
        return [
            'id' => $agent->id,
            'uuid' => $agent->uuid,
            'nom' => $agent->nom,
            'prenoms' => $agent->prenoms,
            'login' => $agent->login,
            'full_login' => $agent->full_login,
            'email' => $agent->email,
            'pays_id' => $agent->pays_id,
            'kyc_status' => $agent->kyc_status,
            'code_owner' => $agent->code_owner,
            'code_parrain' => $agent->code_parrain,
            'alias_smil' => $agent->alias_smil,
            'type_piece' => $agent->type_piece,
            'numero_piece' => $agent->numero_piece,
            'date_delivrance' => $agent->date_delivrance,
            'date_naissance' => $agent->date_naissance,
            'lieu_delivrance' => $agent->lieu_delivrance,
            'lieu_naissance' => $agent->lieu_naissance,
            'adresse' => $agent->adresse,
            'statut' => $agent->statut,
            'roles' => $agent->getRoleNames()->toArray(),
            'current_role' => $agent->getRoleNames()->first(),
            'photoprofile' => $photoprofile ? asset('images/' . $photoprofile->nom) : null,
            'piecerecto' => $piecerecto ? asset('images/' . $piecerecto->nom) : null,
            'pieceverso' => $pieceverso ? asset('images/' . $pieceverso->nom) : null,
        ];
    }
}
