<?php

namespace Modules\Business\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Generator;
use App\Services\PasswordResetService;
use App\Services\ValidationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Modules\Administration\Http\Controllers\ErrorLogController;
use Modules\Business\Entities\Employe;
use Modules\Business\Entities\Marchand;
use Modules\Business\Entities\PointVente;
use Modules\Parametrage\Entities\Fichier;
use Modules\Parametrage\Entities\Pays;

class EmployeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:employe-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:employe-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:employe-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:employe-statut', ['only' => ['statut']]);
        $this->middleware('permission.check:employe-validate', ['only' => ['validation']]);
    }

    public function index(Request $request)
    {
        try {
            $user = auth()->user();

            $marchand = $user->marchand; // null si admin / autre

            $paysCurrent = auth()->user()->pays_id;

            $nom = $request->input('nom');
            $tel = $request->input('tel');
            $raison_sociale = $request->input('raison_sociale');
            $code_employe = $request->input('code_employe');
            $statut = $request->input('statut');
            $kyc_status = $request->input('kyc_status');
            $type_employe = $request->input('type_employe');
            $pays_id = $request->input('pays_id');

            $payss = Pays::select('id', 'libelle')->orderBy('libelle')->get();

            // Statuts de la migration employes: non_actif, actif, suspendu, bloque, supprime
            $statuts = [
                ['value' => 'non_actif', 'label' => __('statuts.non_actif')],
                ['value' => 'actif', 'label' => __('statuts.actif')],
                ['value' => 'suspendu', 'label' => __('statuts.suspendu')],
                ['value' => 'bloque', 'label' => __('statuts.bloque')],
                ['value' => 'supprime', 'label' => __('statuts.supprime')],
            ];

            $typeEmployes = [
                ['value' => 'caissier', 'label' => trans('modules.business.employes.types.caissier')],
                ['value' => 'manager', 'label' => trans('modules.business.employes.types.manager')],
            ];

            $kycStatuts = [
                ['value' => 'non_verifie', 'label' => __('kyc.non_verifie')],
                ['value' => 'en_attente', 'label' => __('kyc.en_attente')],
                ['value' => 'verifie', 'label' => __('kyc.verifie')],
                ['value' => 'rejete', 'label' => __('kyc.rejete')],
            ];

            $query = Employe::with(['user.pays', 'pointVente.marchand'])
                ->orderBy('created_at', 'DESC');

            if ($marchand) {
                $query->whereHas('pointVente', function ($q) use ($marchand) {
                    $q->where('marchand_id', $marchand->id);
                });
            }

            if (!empty($raison_sociale)) {
                $query->whereHas('pointVente.marchand', function ($q) use ($raison_sociale) {
                    $q->where('raison_sociale', 'LIKE', "%{$raison_sociale}%");
                });
            }

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

            if (!empty($pays_id) || !is_null($paysCurrent)) {
                $query->whereHas('user', function ($q) use ($pays_id, $paysCurrent) {
                    if (!empty($pays_id)) {
                        $q->where('pays_id', $pays_id);
                    } elseif (!is_null($paysCurrent)) {
                        $q->where('pays_id', $paysCurrent);
                    }
                });
            }

            if (!empty($statut)) {
                $query->whereHas('user', function ($q) use ($statut) {
                    $q->where('statut', $statut);
                });
            }

            if (!empty($kyc_status)) {
                $query->whereHas('user', function ($q) use ($kyc_status) {
                    $q->where('kyc_status', $kyc_status);
                });
            }

            $employes = $query->paginate(10)->withQueryString();

            $employes->getCollection()->transform(function ($employe) {
                return [
                    'id' => $employe->id,
                    'code_employe' => $employe->code_employe,
                    'type_employe' => $employe->type_employe,
                    'nom' => $employe->user?->nom ?? '',
                    'prenoms' => $employe->user?->prenoms ?? '',
                    'email' => $employe->user?->email ?? '',
                    'telephone' => $employe->user?->login ?? '',
                    'pays' => $employe->user?->pays?->libelle ?? '',
                    'raison_sociale' => $employe->pointVente?->marchand?->raison_sociale ?? '',
                    'point_vente' => $employe->pointVente?->nom ?? '',
                    'statut' => $employe->user?->statut ?? 'non_actif',
                    'kyc_status' => $employe->user?->kyc_status ?? 'non_verifie',
                ];
            });

            return Inertia::render('Business::Employe/Index', [
                'employes' => $employes,
                'pays' => $payss,
                'statuts' => $statuts,
                'kycStatuts' => $kycStatuts,
                'typeEmployes' => $typeEmployes,
                'filters' => [
                    'nom' => $nom,
                    'tel' => $tel,
                    'raison_sociale' => $raison_sociale,
                    'code_employe' => $code_employe,
                    'statut' => $statut,
                    'kyc_status' => $kyc_status,
                    'type_employe' => $type_employe,
                    'pays_id' => $pays_id,
                ],
                'paysCurrent' => $paysCurrent,
                'userStatuts' => config('appconstants.user_statut'),
                'kycStatutsConst' => config('appconstants.user_kyc_status'),
            ]);
        } catch (\Throwable $e) {
            log_error("Employe", "index", $e->getMessage());
            return redirect()->route('home')->with('error', trans('Erreur'));
        }
    }

    public function create(Request $request)
    {
        try {
            $user = auth()->user();
            $paysCurrent = auth()->user()->pays_id;
            $isMarchand = $user->marchand !== null;
            $marchandCurrent = $user->marchand;

            $typePieces = self::getTranslatedConstants('type_piece', 'type_piece_label');

            $typeEmployes = [
                ['value' => 'caissier', 'label' => trans('modules.business.employes.types.caissier')],
                ['value' => 'manager', 'label' => trans('modules.business.employes.types.manager')],
            ];

            $marchandQuery = Marchand::select('marchands.id', 'raison_sociale')
                ->join('users', 'marchands.proprietaire_id', '=', 'users.id')
                ->where('users.statut', config('appconstants.user_statut.actif'))
                ->orderBy('raison_sociale');

            if (!is_null($paysCurrent)) {
                $marchandQuery->where('users.pays_id', $paysCurrent);
            }

            $marchands = $marchandQuery->get()->map(function ($m) {
                return ['id' => $m->id, 'label' => $m->raison_sociale];
            });

            $pointsVente = [];
            // Charger les points de vente du marchand courant si c'est un marchand, sinon du marchand_id de la requête
            $marchandIdForPV = $isMarchand ? $marchandCurrent->id : $request->input('marchand_id');
            if ($marchandIdForPV) {
                $pointsVente = PointVente::where('marchand_id', $marchandIdForPV)
                    ->where('statut', config('appconstants.user_statut.actif'))
                    ->whereNull('parent_points_vente_id')
                    ->get()
                    ->map(function ($pv) {
                        return ['id' => $pv->id, 'label' => $pv->nom];
                    });
            }

            return Inertia::render('Business::Employe/Create', [
                'typePieces' => $typePieces,
                'typeEmployes' => $typeEmployes,
                'marchands' => $marchands,
                'is_marchand'=> $isMarchand,
                'marchand_current' => $marchandCurrent,
                'pointsVente' => $pointsVente,
                'marchand_id' => $request->input('marchand_id'),
                'points_vente_id' => $request->input('points_vente_id'),
            ]);
        } catch (\Throwable $e) {
            log_error("Employe", "create", $e->getMessage());
            return redirect()->route('employe.index')->with('error', trans('Erreuraffichage'));
        }
    }

    public function store(Request $request)
    {
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
        ]);

        try {
            DB::beginTransaction();

            $pointVente = PointVente::with('marchand.proprietaire.pays')->findOrFail($validatedData['points_vente_id']);
            $marchand = $pointVente->marchand;

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

            $roleCode = $validatedData['type_employe'] === 'caissier' ? 'caissier' : 'manager';

            $user = User::create([
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

            Employe::create([
                'points_vente_id' => $validatedData['points_vente_id'],
                'users_id' => $user->id,
                'code_employe' => $codeEmploye,
                'type_employe' => $validatedData['type_employe'],
                'date_embauche' => $validatedData['date_embauche'] ?? now(),
                'create_by' => auth()->user()->id,
            ]);

            $user->assignRole(config('appconstants.role.' . $roleCode));

            DB::commit();

            PasswordResetService::sendResetLinkSms($user);

            return redirect()->route('employe.index')->with('success', trans('enregistrementsucces'));
        } catch (\Throwable $e) {
            DB::rollback();
            log_error("Employe", "store", $e->getMessage());
            return redirect()->route('employe.create')->with('error', trans('Erreur') . ' : ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $employe = Employe::with(['user.pays', 'pointVente.marchand', 'pointVente.zone'])->findOrFail($id);

            // Mettre le KYC en attente automatiquement si non_verifie
            if ($employe->user) {
                ValidationService::setKycEnAttente($employe->user);
                $employe->refresh();
            }

            $typePieces = self::getTranslatedConstants('type_piece', 'type_piece_label');

            return Inertia::render('Business::Employe/Show', [
                'employe' => $this->transformEmploye($employe),
                'typePieces' => $typePieces,
                'kycStatuts' => config('appconstants.user_kyc_status'),
                'userStatuts' => config('appconstants.user_statut'),
            ]);
        } catch (\Throwable $e) {
            log_error("Employe", "show", $e->getMessage());
            return redirect()->route('employe.index')->with('error', trans('Erreuraffichage'));
        }
    }

    public function edit($id)
    {
        try {
            $user = auth()->user();
            $isMarchand = $user->marchand !== null;
            $marchandCurrent = $user->marchand;
            $paysCurrent = auth()->user()->pays_id;
            $employe = Employe::with(['user.pays', 'pointVente.marchand'])->findOrFail($id);

            $typePieces = self::getTranslatedConstants('type_piece', 'type_piece_label');

            $typeEmployes = [
                ['value' => 'caissier', 'label' => trans('modules.business.employes.types.caissier')],
                ['value' => 'manager', 'label' => trans('modules.business.employes.types.manager')],
            ];

            $marchandQuery = Marchand::select('marchands.id', 'raison_sociale')
                ->join('users', 'marchands.proprietaire_id', '=', 'users.id')
                ->where('users.statut', config('appconstants.user_statut.actif'))
                ->orderBy('raison_sociale');

            if (!is_null($paysCurrent)) {
                $marchandQuery->where('users.pays_id', $paysCurrent);
            }

            $marchands = $marchandQuery->get()->map(function ($m) {
                return ['id' => $m->id, 'label' => $m->raison_sociale];
            });

            $pointsVente = PointVente::where('marchand_id', $employe->pointVente->marchand_id)
                ->where('statut', config('appconstants.user_statut.actif'))
                ->whereNull('parent_points_vente_id')
                ->get()
                ->map(function ($pv) {
                    return ['id' => $pv->id, 'label' => $pv->nom];
                });

            return Inertia::render('Business::Employe/Edit', [
                'employe' => $this->transformEmploye($employe),
                'typePieces' => $typePieces,
                'typeEmployes' => $typeEmployes,
                'is_marchand'=> $isMarchand,
                'marchand_current'=> $marchandCurrent,
                'marchands' => $marchands,
                'pointsVente' => $pointsVente,
            ]);
        } catch (\Throwable $e) {
            log_error("Employe", "edit", $e->getMessage());
            return redirect()->route('employe.index')->with('error', trans('Erreuraffichage'));
        }
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $employe = Employe::findOrFail($id);
            $user = User::findOrFail($employe->users_id);

            $validatedData = $request->validate([
                'points_vente_id' => 'required|exists:points_vente,id',
                'type_employe' => 'required|in:caissier,manager',
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
            ]);

            // Récupérer le point de vente et le marchand pour obtenir le pays
            $pointVente = PointVente::with('marchand.proprietaire.pays')->findOrFail($validatedData['points_vente_id']);
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
                'role' => $validatedData['type_employe'],
            ] + $newFileIds);

            $employe->update([
                'points_vente_id' => $validatedData['points_vente_id'],
                'type_employe' => $validatedData['type_employe'],
                'date_embauche' => $validatedData['date_embauche'] ?? $employe->date_embauche,
            ]);

            DB::commit();

            return redirect()->route('employe.index')->with('success', trans('modifsucces'));
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error("Employe", "update", $e->getMessage());
            return redirect()->route('employe.edit', $id)->with('error', trans('erreurmaj') . ' : ' . $e->getMessage());
        }
    }

    public function statut($id)
    {
        try {
            DB::beginTransaction();

            $employe = Employe::withTrashed()->findOrFail($id);
            $user = User::withTrashed()->find($employe->users_id);

            if ($employe->trashed()) {
                $employe->restore();
                if ($user && $user->trashed()) {
                    $user->restore();
                }
                $message = trans('restaurationsucces');
            } else {
                $employe->delete();
                if ($user && !$user->trashed()) {
                    $user->delete();
                }
                $message = trans('suppressionsucces');
            }

            DB::commit();
            return redirect()->route('employe.index')->with('success', $message);
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error("Employe", "statut", $e->getMessage());
            return redirect()->route('employe.index')->with('error', trans('Erreur'));
        }
    }

    public function validation(Request $request, $id, $action)
    {
        try {
            $motif = $request->input('motif');
            $employe = Employe::withTrashed()->findOrFail($id);
            $user = User::withTrashed()->find($employe->users_id);

            if ($action === 'valider') {
                $result = ValidationService::validateStatut($employe, $user, false);
            } elseif ($action === 'rejeter') {
                $result = ValidationService::rejectStatut($employe, $motif, $user, false);
            } else {
                return redirect()->route('employe.index')->with('error', trans('actionnonreconnue'));
            }

            if (!$result['success']) {
                return redirect()->route('employe.index')->with('error', $result['message']);
            }

            return redirect()->route('employe.index')->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("Employe", "validation", $e->getMessage());
            return redirect()->route('employe.index')->with('error', trans('Erreur'));
        }
    }

    public function getPointsVenteByMarchand($marchandId)
    {
        try {
            $pointsVente = PointVente::where('marchand_id', $marchandId)
                ->whereNull('parent_points_vente_id')
                ->where('statut', config('appconstants.pointvente_statut.actif'))
                ->get()
                ->map(function ($pv) {
                    return ['id' => $pv->id, 'label' => $pv->nom];
                });

            return response()->json(['pointsVente' => $pointsVente]);
        } catch (\Throwable $e) {
            log_error("Employe", "getPointsVenteByMarchand", $e->getMessage());
            return response()->json(['error' => 'Erreur'], 500);
        }
    }

    public function kycValidation(Request $request, $id, $action)
    {
        try {
            $motif = $request->input('motif');
            $employe = Employe::findOrFail($id);
            $user = User::find($employe->users_id);

            if (!$user) {
                return redirect()->back()->with('error', trans('Erreur'));
            }

            if ($action === 'valider') {
                $result = ValidationService::validateKyc($user);
            } elseif ($action === 'rejeter') {
                $result = ValidationService::rejectKyc($user, $motif);
            } else {
                return redirect()->back()->with('error', trans('actionnonreconnue'));
            }

            if (!$result['success']) {
                return redirect()->back()->with('error', $result['message']);
            }

            return redirect()->back()->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("Employe", "kycValidation", $e->getMessage());
            return redirect()->back()->with('error', trans('Erreur'));
        }
    }

    public function suspendre(Request $request, $id)
    {
        try {
            $motif = $request->input('motif');
            $employe = Employe::findOrFail($id);
            $user = User::find($employe->users_id);

            if (!$user) {
                return redirect()->route('employe.index')->with('error', trans('Erreur'));
            }

            $result = ValidationService::suspendUser($user, $motif);

            if (!$result['success']) {
                return redirect()->route('employe.index')->with('error', $result['message']);
            }

            return redirect()->route('employe.index')->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("Employe", "suspendre", $e->getMessage());
            return redirect()->route('employe.show', $id)->with('error', trans('Erreur'));
        }
    }

    public function bloquer(Request $request, $id)
    {
        try {
            $motif = $request->input('motif');
            $employe = Employe::findOrFail($id);
            $user = User::find($employe->users_id);

            if (!$user) {
                return redirect()->route('employe.index')->with('error', trans('Erreur'));
            }

            $result = ValidationService::blockUser($user, $motif);

            if (!$result['success']) {
                return redirect()->route('employe.index')->with('error', $result['message']);
            }

            return redirect()->route('employe.index')->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("Employe", "bloquer", $e->getMessage());
            return redirect()->route('employe.show', $id)->with('error', trans('Erreur'));
        }
    }

    private function transformEmploye(Employe $employe): array
    {
        return [
            'id' => $employe->id,
            'code_employe' => $employe->code_employe,
            'type_employe' => $employe->type_employe,
            'date_embauche' => $employe->date_embauche ? Carbon::parse($employe->date_embauche)->format('Y-m-d') : null,
            'points_vente_id' => $employe->points_vente_id,
            'point_vente' => $employe->pointVente?->nom ?? '',
            'marchand_id' => $employe->pointVente?->marchand_id ?? null,
            'marchand' => $employe->pointVente?->marchand?->raison_sociale ?? '',
            'statut' => $employe->user?->statut ?? 'non_actif',
            'user' => $employe->user ? [
                'id' => $employe->user->id,
                'nom' => $employe->user->nom,
                'prenoms' => $employe->user->prenoms,
                'email' => $employe->user->email,
                'login' => $employe->user->login,
                'type_piece' => $employe->user->type_piece,
                'numero_piece' => $employe->user->numero_piece,
                'date_delivrance' => $employe->user->date_delivrance ? Carbon::parse($employe->user->date_delivrance)->format('Y-m-d') : null,
                'date_naissance' => $employe->user->date_naissance ? Carbon::parse($employe->user->date_naissance)->format('Y-m-d') : null,
                'lieu_delivrance' => $employe->user->lieu_delivrance,
                'lieu_naissance' => $employe->user->lieu_naissance,
                'pays_id' => $employe->user->pays_id,
                'pays' => $employe->user->pays?->libelle ?? '',
                'kyc_status' => $employe->user->kyc_status,
                'photoprofile' => $employe->user->photoprofile?->source ?? null,
                'piecerecto' => $employe->user->piecerecto?->source ?? null,
                'pieceverso' => $employe->user->pieceverso?->source ?? null,
            ] : null,
        ];
    }
}
