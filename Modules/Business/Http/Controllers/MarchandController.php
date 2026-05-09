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
use Modules\Business\Entities\Marchand;
use Modules\Business\Entities\PointVente;
use Modules\Parametrage\Entities\Fichier;
use Modules\Parametrage\Entities\Pays;
use Modules\Parametrage\Entities\Zone;
use Modules\Wallet\Entities\Wallet;
use Modules\Wallet\Services\WalletService;

class MarchandController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:marchand-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:marchand-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:marchand-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:marchand-statut', ['only' => ['statut']]);
        $this->middleware('permission.check:marchand-validate', ['only' => ['validation']]);
    }

    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $paysCurrent = auth()->user()->pays_id;

            $nom = $request->input('nom');
            $prenoms = $request->input('prenoms');
            $tel = $request->input('tel');
            $email = $request->input('email');
            $raison_sociale = $request->input('raison_sociale');
            $identifiant_fiscal = $request->input('identifiant_fiscal');
            $type = $request->input('type');
            $statut = $request->input('statut');
            $kyc_status = $request->input('kyc_status');
            $pays_id = $request->input('pays_id');

            $payss = Pays::select('id', 'libelle')->orderBy('libelle')->get();

            // Types de marchand
            $types = Marchand::getTypesForSelect();

            // Statuts de la migration marchands: actif, suspendu, bloque, supprime
            $statuts = [
                ['value' => 'non_actif', 'label' => trans('statuts.non_actif')],
                ['value' => 'actif', 'label' => trans('statuts.actif')],
                ['value' => 'suspendu', 'label' => trans('statuts.suspendu')],
                ['value' => 'bloque', 'label' => trans('statuts.bloque')],
                ['value' => 'supprime', 'label' => trans('statuts.supprime')],
            ];

            $kycStatuts = [
                ['value' => 'non_verifie', 'label' => trans('kyc.non_verifie')],
                ['value' => 'en_attente', 'label' => trans('kyc.en_attente')],
                ['value' => 'verifie', 'label' => trans('kyc.verifie')],
                ['value' => 'rejete', 'label' => trans('kyc.rejete')],
            ];

            $query = Marchand::with(['proprietaire', 'pointsVente'])
                ->orderBy('created_at', 'DESC');

            if (!empty($raison_sociale)) {
                $query->where('raison_sociale', 'LIKE', "%{$raison_sociale}%");
            }

            if (!empty($identifiant_fiscal)) {
                $query->where('identifiant_fiscal', 'LIKE', "%{$identifiant_fiscal}%");
            }

            if (!empty($type)) {
                $query->where('type', $type);
            }

            if (!empty($nom)) {
                $query->whereHas('proprietaire', function ($q) use ($nom) {
                    $q->where('nom', 'LIKE', "%{$nom}%");
                });
            }

            if (!empty($prenoms)) {
                $query->whereHas('proprietaire', function ($q) use ($prenoms) {
                    $q->where('prenoms', 'LIKE', "%{$prenoms}%");
                });
            }

            if (!empty($email)) {
                $query->whereHas('proprietaire', function ($q) use ($email) {
                    $q->where('email', 'LIKE', "%{$email}%");
                });
            }

            if (!empty($tel)) {
                $query->whereHas('proprietaire', function ($q) use ($tel) {
                    $q->where('login', 'LIKE', "%{$tel}%");
                });
            }

            if (!empty($pays_id)) {
                $query->whereHas('proprietaire', function ($q) use ($pays_id) {
                    $q->where('pays_id', $pays_id);
                });
            } elseif (!is_null($paysCurrent)) {
                $query->whereHas('proprietaire', function ($q) use ($paysCurrent) {
                    $q->where('pays_id', $paysCurrent);
                });
            }

            if (!empty($statut)) {
                $query->whereHas('proprietaire', function ($q) use ($statut) {
                    $q->where('statut', $statut);
                });
            }

            if (!empty($kyc_status)) {
                $query->whereHas('proprietaire', function ($q) use ($kyc_status) {
                    $q->where('kyc_status', $kyc_status);
                });
            }
            if ($user->marchand) {
                $query->where('id', $user->marchand->id);
            }
//              elseif (!in_array($user->role, [
//                config('appconstants.role.admin'),
//                config('appconstants.role.superadmin'),
//            ])) {
//                abort(403);
//            }


            $marchands = $query->paginate(10)->withQueryString();

            $marchands->getCollection()->transform(function ($marchand) {
                return [
                    'id' => $marchand->id,
                    'raison_sociale' => $marchand->raison_sociale,
                    'identifiant_fiscal' => $marchand->identifiant_fiscal ?? '',
                    'nom' => $marchand->proprietaire?->nom ?? '',
                    'prenoms' => $marchand->proprietaire?->prenoms ?? '',
                    'email' => $marchand->proprietaire?->email ?? '',
                    'type' => $marchand->type,
                    'telephone' => $marchand->proprietaire?->login ?? '',
                    'pays' => $marchand->proprietaire?->pays?->libelle ?? '',
                    'kyc_status' => $marchand->proprietaire?->kyc_status ?? 'none',
                    'statut' => $marchand->proprietaire?->statut ?? 'non_actif',
                    'points_vente_count' => $marchand->pointsVente->count(),
                    'points_vente' => $marchand->pointsVente->map(function ($pv) {
                        return [
                            'id' => $pv->id,
                            'nom' => $pv->nom,
                            'adresse' => $pv->adresse,
                            'telephone' => $pv->telephone,
                            'zone' => $pv->zone?->libelle ?? '',
                            'statut' => $pv->statut,
                        ];
                    }),
                ];
            });

            return Inertia::render('Business::Marchand/Index', [
                'marchands' => $marchands,
                'pays' => $payss,
                'types' => $types,
                'statuts' => $statuts,
                'kycStatuts' => $kycStatuts,
                'filters' => [
                    'nom' => $nom,
                    'prenoms' => $prenoms,
                    'tel' => $tel,
                    'email' => $email,
                    'raison_sociale' => $raison_sociale,
                    'identifiant_fiscal' => $identifiant_fiscal,
                    'type' => $type,
                    'statut' => $statut,
                    'kyc_status' => $kyc_status,
                    'pays_id' => $pays_id,
                ],
                'paysCurrent' => $paysCurrent,
                'userStatuts' => config('appconstants.user_statut'),
                'kycStatutsConst' => config('appconstants.user_kyc_status'),
            ]);
        } catch (\Throwable $e) {
            log_error("Marchand", "index", $e->getMessage());
            return redirect()->route('home')->with('error', trans('Erreur'));
        }
    }

    public function create()
    {
        try {
            $paysCurrent = auth()->user()->pays_id;

            $payss = Pays::select('id', 'libelle', 'code')->orderBy('libelle')->get();

            $typePieces = self::getTranslatedConstants('type_piece', 'type_piece_label');

            $zones = Zone::select('id', 'libelle', 'pays_id')->orderBy('libelle')->get();

            return Inertia::render('Business::Marchand/Create', [
                'pays' => $payss,
                'types' => Marchand::getTypesForSelect(),
                'typePieces' => $typePieces,
                'zones' => $zones,
                'paysCurrent' => $paysCurrent,
            ]);
        } catch (\Throwable $e) {
            log_error("Marchand", "create", $e->getMessage());
            return redirect()->route('marchand.index')->with('error', trans('Erreuraffichage'));
        }
    }

    public function store(Request $request)
    {
        $paysCurrent = auth()->user()->pays_id;

        $validatedData = $request->validate([
            'raison_sociale' => 'required|string|max:255',
            'identifiant_fiscal' => 'nullable|string|max:100',
            'type' => 'required|in:' . implode(',', array_keys(Marchand::getTypes())),
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
                'create_by' => auth()->user()->id,
            ]);

            $user->assignRole(config('appconstants.role.marchand'));

            // Création des wallets
            WalletService::createWalletsForOwner($marchand->id, 'marchand', $paysId);

            DB::commit();

            PasswordResetService::sendResetLinkSms($user);

            return redirect()->route('marchand.index')->with('success', trans('enregistrementsucces'));
        } catch (\Throwable $e) {
            DB::rollback();
            log_error("Marchand", "store", $e->getMessage());
            return redirect()->route('marchand.create')->with('error', trans('Erreur') . ' : ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $marchand = Marchand::with(['proprietaire.pays', 'rccm', 'dfe', 'pointsVente.zone'])->findOrFail($id);
            $payss = Pays::select('id', 'libelle')->get();

            // Mettre le KYC en attente automatiquement si non_verifie
            if ($marchand->proprietaire) {
                ValidationService::setKycEnAttente($marchand->proprietaire);
                $marchand->refresh();
            }

            $typePieces = self::getTranslatedConstants('type_piece', 'type_piece_label');

            return Inertia::render('Business::Marchand/Show', [
                'marchand' => $this->transformMarchand($marchand),
                'pays' => $payss,
                'typePieces' => $typePieces,
                'kycStatuts' => config('appconstants.user_kyc_status'),
                'userStatuts' => config('appconstants.user_statut'),
            ]);
        } catch (\Throwable $e) {
            log_error("Marchand", "show", $e->getMessage());
            return redirect()->route('marchand.index')->with('error', trans('Erreuraffichage'));
        }
    }

    public function edit($id)
    {
        try {
            $paysCurrent = auth()->user()->pays_id;
            $marchand = Marchand::with(['proprietaire.pays', 'rccm', 'dfe'])->findOrFail($id);
            $payss = Pays::select('id', 'libelle', 'code')->get();

            $typePieces = self::getTranslatedConstants('type_piece', 'type_piece_label');

            return Inertia::render('Business::Marchand/Edit', [
                'marchand' => $this->transformMarchand($marchand),
                'pays' => $payss,
                'types' => Marchand::getTypesForSelect(),
                'typePieces' => $typePieces,
                'paysCurrent' => $paysCurrent,
            ]);
        } catch (\Throwable $e) {
            log_error("Marchand", "edit", $e->getMessage());
            return redirect()->route('marchand.index')->with('error', trans('Erreuraffichage'));
        }
    }

    public function update(Request $request, $id)
    {
        $paysCurrent = auth()->user()->pays_id;

        try {
            DB::beginTransaction();

            $marchand = Marchand::findOrFail($id);
            $user = User::findOrFail($marchand->proprietaire_id);

            $validatedData = $request->validate([
                'raison_sociale' => 'required|string|max:255',
                'identifiant_fiscal' => 'nullable|string|max:100',
                'type' => 'required|in:' . implode(',', array_keys(Marchand::getTypes())),
                'nom' => 'required|string|max:255',
                'prenoms' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'tel' => 'required|string|max:255|unique:users,login,' . $user->id,
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
                    $fileName = time() . '-' . Str::random(10) . '.' . $file->extension();
                    $file->move(public_path('images'), $fileName);

                    $f = Fichier::create([
                        'nom' => $fileName,
                        'source' => 'images/' . $fileName,
                    ]);

                    $newFileIds[$targetModel][$inputName] = $f->id;
                }
            }

            $paysId = $paysCurrent ?? $validatedData['pays_id'] ?? $user->pays_id;
            $pays = Pays::find($paysId);
            $indicatif = $pays?->code ?? '';
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
            ] + $newFileIds['user']);

            $marchand->update([
                'raison_sociale' => $validatedData['raison_sociale'],
                'identifiant_fiscal' => $validatedData['identifiant_fiscal'] ?? $marchand->identifiant_fiscal,
                'type' => $validatedData['type'],
            ] + $newFileIds['marchand']);

            // Vérifier si le marchand a des wallets, sinon les créer
            if (!Wallet::where('owner_type', 'marchand')
                ->where('owner_id', $marchand->id)
                ->exists()) {
                WalletService::createWalletsForOwner($marchand->id, 'marchand', $paysId);
            }


            DB::commit();
            return redirect()->route('marchand.index')->with('success', trans('modifsucces'));
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error("Marchand", "update", $e->getMessage());
            return redirect()->route('marchand.edit', $id)->with('error', trans('erreurmaj') . ' : ' . $e->getMessage());
        }
    }

    public function statut($id)
    {
        try {
            DB::beginTransaction();

            $marchand = Marchand::withTrashed()->findOrFail($id);
            $user = User::withTrashed()->find($marchand->proprietaire_id);

            if ($marchand->trashed()) {
                $marchand->restore();
                if ($user && $user->trashed()) {
                    $user->restore();
                }
                $message = trans('restaurationsucces');
            } else {
                $marchand->delete();
                if ($user && !$user->trashed()) {
                    $user->delete();
                }
                $message = trans('suppressionsucces');
            }

            DB::commit();
            return redirect()->route('marchand.index')->with('success', $message);
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error("Marchand", "statut", $e->getMessage());
            return redirect()->route('marchand.index')->with('error', trans('Erreur'));
        }
    }

    public function validation(Request $request, $id, $action)
    {
        try {
            $motif = $request->input('motif');
            $marchand = Marchand::withTrashed()->findOrFail($id);
            $user = User::withTrashed()->find($marchand->proprietaire_id);

            if ($action === 'valider') {
                $result = ValidationService::validateStatut($marchand, $user, false);
            } elseif ($action === 'rejeter') {
                $result = ValidationService::rejectStatut($marchand, $motif, $user, false);
                $response = $this->getPointsVente($id);

                // récupérer les vraies données
                $pointsVente = $response->original['pointsVente'] ?? [];
                foreach ($pointsVente as $pv) {
                    PointVente::where('id', $pv['id'])->update([
                        'statut' => config('appconstants.user_statut.suspendu'),
                    ]);
                }
            } else {
                return redirect()->route('marchand.index')->with('error', trans('actionnonreconnue'));
            }

            if (!$result['success']) {
                return redirect()->route('marchand.index')->with('error', $result['message']);
            }

            return redirect()->route('marchand.index')->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("Marchand", "validation", $e->getMessage());
            return redirect()->route('marchand.index')->with('error', trans('Erreur'));
        }
    }

    public function kycValidation(Request $request, $id, $action)
    {
        try {
            $motif = $request->input('motif');
            $marchand = Marchand::withTrashed()->findOrFail($id);
            $user = User::withTrashed()->find($marchand->proprietaire_id);

            if (!$user) {
                return redirect()->route('marchand.show', $id)->with('error', trans('Erreur'));
            }

            if ($action === 'valider') {
                $result = ValidationService::validateKyc($user);
            } elseif ($action === 'rejeter') {
                $result = ValidationService::rejectKyc($user, $motif);
            } else {
                return redirect()->route('marchand.show', $id)->with('error', trans('actionnonreconnue'));
            }

            if (!$result['success']) {
                return redirect()->route('marchand.show', $id)->with('error', $result['message']);
            }

            return redirect()->route('marchand.show', $id)->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("Marchand", "kycValidation", $e->getMessage());
            return redirect()->route('marchand.show', $id)->with('error', trans('Erreur'));
        }
    }

    public function suspendre(Request $request, $id)
    {
        try {
            $motif = $request->input('motif');
            $marchand = Marchand::findOrFail($id);
            $user = User::find($marchand->proprietaire_id);

            if (!$user) {
                return redirect()->route('marchand.show', $id)->with('error', trans('Erreur'));
            }

            $result = ValidationService::suspendUser($user, $motif);

            if (!$result['success']) {
                return redirect()->route('marchand.index')->with('error', $result['message']);
            }

            return redirect()->route('marchand.index')->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("Marchand", "suspendre", $e->getMessage());
            return redirect()->route('marchand.show', $id)->with('error', trans('Erreur'));
        }
    }

    public function bloquer(Request $request, $id)
    {
        try {
            $motif = $request->input('motif');
            $marchand = Marchand::findOrFail($id);
            $user = User::find($marchand->proprietaire_id);

            if (!$user) {
                return redirect()->route('marchand.show', $id)->with('error', trans('Erreur'));
            }

            $result = ValidationService::blockUser($user, $motif);

            if (!$result['success']) {
                return redirect()->route('marchand.index')->with('error', $result['message']);
            }

            return redirect()->route('marchand.index')->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("Marchand", "bloquer", $e->getMessage());
            return redirect()->route('marchand.show', $id)->with('error', trans('Erreur'));
        }
    }

    public function getPointsVente($id)
    {
        try {
            $marchand = Marchand::with(['pointsVente.zone'])

                ->findOrFail($id);

            $pointsVente = $marchand->pointsVente
                ->whereNull('parent_points_vente_id')
                ->where('statut', config('appconstants.user_statut.actif'))->map(function ($pv) {
                return [
                    'id' => $pv->id,
                    'nom' => $pv->nom,
                    'label' => $pv->nom,
                    'adresse' => $pv->adresse,
                    'telephone' => $pv->telephone,
                    'zone' => $pv->zone?->libelle ?? '',
                    'statut' => $pv->statut,
                ];
            })->values();

            return response()->json(['pointsVente' => $pointsVente]);
        } catch (\Throwable $e) {
            log_error("Marchand", "getPointsVente", $e->getMessage());
            return response()->json(['error' => 'Erreur'], 500);
        }
    }

    private function transformMarchand(Marchand $marchand): array
    {
        return [
            'id' => $marchand->id,
            'raison_sociale' => $marchand->raison_sociale,
            'identifiant_fiscal' => $marchand->identifiant_fiscal,
            'type' => $marchand->type,
            'type_label' => $marchand->getTypeLabel(),
            'statut' => $marchand->proprietaire?->statut ?? 'non_actif',
            'proprietaire' => $marchand->proprietaire ? [
                'id' => $marchand->proprietaire->id,
                'nom' => $marchand->proprietaire->nom,
                'prenoms' => $marchand->proprietaire->prenoms,
                'email' => $marchand->proprietaire->email,
                'login' => $marchand->proprietaire->login,
                'type_piece' => $marchand->proprietaire->type_piece,
                'numero_piece' => $marchand->proprietaire->numero_piece,
                'date_delivrance' => $marchand->proprietaire->date_delivrance ? Carbon::parse($marchand->proprietaire->date_delivrance)->format('Y-m-d') : null,
                'date_naissance' => $marchand->proprietaire->date_naissance ? Carbon::parse($marchand->proprietaire->date_naissance)->format('Y-m-d') : null,
                'lieu_delivrance' => $marchand->proprietaire->lieu_delivrance,
                'lieu_naissance' => $marchand->proprietaire->lieu_naissance,
                'pays_id' => $marchand->proprietaire->pays_id,
                'pays' => $marchand->proprietaire->pays?->libelle ?? '',
                'kyc_status' => $marchand->proprietaire->kyc_status,
                'photoprofile' => $marchand->proprietaire->photoprofile?->source ?? null,
                'piecerecto' => $marchand->proprietaire->piecerecto?->source ?? null,
                'pieceverso' => $marchand->proprietaire->pieceverso?->source ?? null,
            ] : null,
            'rccm' => $marchand->rccm?->source ?? null,
            'dfe' => $marchand->dfe?->source ?? null,
            'points_vente' => $marchand->pointsVente->map(function ($pv) {
                return [
                    'id' => $pv->id,
                    'nom' => $pv->nom,
                    'adresse' => $pv->adresse,
                    'telephone' => $pv->telephone,
                    'zone' => $pv->zone?->libelle ?? '',
                    'statut' => $pv->statut,
                ];
            }),
        ];
    }
}
