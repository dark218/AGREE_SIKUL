<?php

namespace Modules\Business\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\QrCodeService;
use App\Services\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Modules\Administration\Http\Controllers\ErrorLogController;
use Modules\Business\Entities\Marchand;
use Modules\Business\Entities\PointVente;
use Modules\Parametrage\Entities\Fichier;
use Modules\Parametrage\Entities\Pays;
use Modules\Parametrage\Entities\Zone;

class PointVenteController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:pointvente-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:pointvente-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:pointvente-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:pointvente-statut', ['only' => ['statut']]);
        $this->middleware('permission.check:pointvente-validate', ['only' => ['validation']]);
    }

    public function index(Request $request)
    {
        try {
            $user = auth()->user();

            $isMarchand = $user->marchand !== null;

            $paysCurrent = auth()->user()->pays_id;

            $nom = $request->input('nom');
            $tel = $request->input('tel');
            $zone_id = $request->input('zone_id');
            $adresse = $request->input('adresse');
            $raison_sociale = $request->input('raison_sociale');
            $statut = $request->input('statut');
            $pays_id = $request->input('pays_id');

            $payss = Pays::select('id', 'libelle')->orderBy('libelle')->get();

            $queryZone = Zone::select('id', 'libelle', 'pays_id')->orderBy('libelle');
            if (!is_null($paysCurrent)) {
                $queryZone->where('pays_id', $paysCurrent);
            } elseif (!is_null($pays_id)) {
                $queryZone->where('pays_id', $pays_id);
            }
            $zones = $queryZone->get();

            // Statuts de la migration points_vente: non_actif, actif, suspendu, bloque, supprime
            $statuts = [
                ['value' => 'non_actif', 'label' => trans('statuts.non_actif')],
                ['value' => 'actif', 'label' => trans('statuts.actif')],
                ['value' => 'suspendu', 'label' => trans('statuts.suspendu')],
                ['value' => 'bloque', 'label' => trans('statuts.bloque')],
                ['value' => 'supprime', 'label' => trans('statuts.supprime')],
            ];

            $query = PointVente::with(['marchand.proprietaire', 'zone', 'photo', 'employes.user'])
                ->withCount('employes')
                ->orderBy('created_at', 'DESC');
            $query->whereNull('parent_points_vente_id');
            if ($isMarchand) {
                $query->where('marchand_id', $user->marchand->id);
            }

            if (!empty($raison_sociale)) {
                $query->whereHas('marchand', function ($q) use ($raison_sociale) {
                    $q->where('raison_sociale', 'LIKE', "%{$raison_sociale}%");
                });
            }

            if (!empty($nom)) {
                $query->where('nom', 'LIKE', "%{$nom}%");
            }

            if (!empty($tel)) {
                $query->where('telephone', 'LIKE', "%{$tel}%");
            }

            if (!empty($pays_id) || !is_null($paysCurrent)) {
                $query->whereHas('marchand.proprietaire', function ($q) use ($pays_id, $paysCurrent) {
                    if (!empty($pays_id)) {
                        $q->where('pays_id', $pays_id);
                    } elseif (!is_null($paysCurrent)) {
                        $q->where('pays_id', $paysCurrent);
                    }
                });
            }

            if (!empty($zone_id)) {
                $query->where('zone_id', $zone_id);
            }

            if (!empty($adresse)) {
                $query->where('adresse', 'LIKE', "%{$adresse}%");
            }

            if (!empty($statut)) {
                $query->where('statut', $statut);
            }

            $pointventes = $query->paginate(10)->withQueryString();

            $pointventes->getCollection()->transform(function ($pv) {
                return [
                    'id' => $pv->id,
                    'nom' => $pv->nom,
                    'adresse' => $pv->adresse,
                    'telephone' => $pv->telephone,
                    'zone' => $pv->zone?->libelle ?? '',
                    'marchand' => $pv->marchand?->raison_sociale ?? '',
                    'pays' => $pv->marchand?->proprietaire?->pays?->libelle ?? '',
                    'statut' => $pv->statut,
                    'employes_count' => $pv->employes_count ?? 0,
                    'employes' => $pv->employes->map(function ($emp) {
                        return [
                            'id' => $emp->id,
                            'code_employe' => $emp->code_employe,
                            'type_employe' => $emp->type_employe,
                            'nom' => $emp->user?->nom ?? '',
                            'prenoms' => $emp->user?->prenoms ?? '',
                            'telephone' => $emp->user?->full_login ?? '',
                            'statut' => $emp->user?->statut ?? 'non_actif',
                        ];
                    }),
                ];
            });

            return Inertia::render('Business::PointsVente/Index', [
                'pointventes' => $pointventes,
                'pays' => $payss,
                'zones' => $zones,
                'statuts' => $statuts,
                'filters' => [
                    'nom' => $nom,
                    'tel' => $tel,
                    'zone_id' => $zone_id,
                    'adresse' => $adresse,
                    'raison_sociale' => $raison_sociale,
                    'statut' => $statut,
                    'pays_id' => $pays_id,
                ],
                'paysCurrent' => $paysCurrent,
                'userStatuts' => config('appconstants.user_statut'),
            ]);
        } catch (\Throwable $e) {
            log_error("PointVente", "index", $e->getMessage());
            return redirect()->route('home')->with('error', trans('Erreur'));
        }
    }

    public function create(Request $request)
    {
        try {
            $user = auth()->user();

            $isMarchand = $user->marchand !== null;
            $marchandCurrent = $user->marchand;

            $paysCurrent = auth()->user()->pays_id;

            $payss = Pays::select('id', 'libelle')->orderBy('libelle')->get();

            $queryZone = Zone::select('id', 'libelle', 'pays_id')->orderBy('libelle');
            if (!is_null($paysCurrent)) {
                $queryZone->where('pays_id', $paysCurrent);
            }
            $zones = $queryZone->get();

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


            return Inertia::render('Business::PointsVente/Create', [
                'pays' => $payss,
                'zones' => $zones,
                'is_marchand'=> $isMarchand,
                'marchands' => $marchands,
                'marchand_current' => $marchandCurrent,
                'paysCurrent' => $paysCurrent,
                'marchand_id' => $request->input('marchand_id'),
            ]);
        } catch (\Throwable $e) {
            log_error("PointVente", "create", $e->getMessage());
            return redirect()->route('pointvente.index')->with('error', trans('Erreuraffichage'));
        }
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'zone_id'   => 'required|exists:zones,id',
            'nom'       => 'required|string|max:255',
            'adresse'   => 'required|string|max:255',
            'telephone' => 'required|string|max:255|unique:points_vente,telephone',
            'longitude' => 'nullable|numeric',
            'latitude'  => 'nullable|numeric',
        ];

        // Admin / Superadmin uniquement
        if (!$user->marchand) {
            $rules['marchand_id'] = 'required|exists:marchands,id';
        }

        $validatedData = $request->validate($rules);

        try {
            DB::beginTransaction();
            $marchandId = $user->marchand
                ? $user->marchand->id
                : $validatedData['marchand_id'];

            $photoId = null;
            if ($request->hasFile('photo_id')) {
                $file = $request->file('photo_id');
                $fileName = time() . '-' . Str::random(10) . '.' . $file->extension();
                $file->move(public_path('images'), $fileName);

                $photoId = Fichier::create([
                    'nom' => $fileName,
                    'source' => 'images/' . $fileName,
                ])->id;
            }

           $pointVente = PointVente::create([
                'marchand_id' => $marchandId,
                'nom' => $validatedData['nom'],
                'adresse' => $validatedData['adresse'],
                'telephone' => $validatedData['telephone'],
                'longitude' => $validatedData['longitude'] ?? null,
                'latitude' => $validatedData['latitude'] ?? null,
                'zone_id' => $validatedData['zone_id'],
                'photo_id' => $photoId,
                'create_by' => auth()->user()->id,
               'param_pos_json' => [
                   'type' => 'point_vente',
               ],
            ]);
            QrCodeService::generateStatique(
                $pointVente->id, // ID POINT VENTE
                [
                    'type'          => 'point_vente',
                    'emplacement_id'=> $pointVente->id,
                    'nom'           => $pointVente->nom,
                ]
            );

            DB::commit();

            return redirect()->route('pointvente.index')->with('success', trans('enregistrementsucces'));
        } catch (\Throwable $e) {
            DB::rollback();
            log_error("PointVente", "store", $e->getMessage());
            return redirect()->route('pointvente.create')->with('error', trans('Erreur') . ' : ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $pointvente = PointVente::with(['marchand', 'zone', 'photo'])->findOrFail($id);

            return Inertia::render('Business::PointsVente/Show', [
                'pointvente' => $this->transformPointVente($pointvente),
                'userStatuts' => config('appconstants.user_statut'),
            ]);
        } catch (\Throwable $e) {
            log_error("PointVente", "show", $e->getMessage());
            return redirect()->route('pointvente.index')->with('error', trans('Erreuraffichage'));
        }
    }

    public function edit($id)
    {
        try {
            $user = auth()->user();

            $isMarchand = $user->marchand !== null;
            $marchandCurrent = $user->marchand;
            $paysCurrent = auth()->user()->pays_id;

            $pointvente = PointVente::with(['marchand', 'zone', 'photo'])->findOrFail($id);

            $payss = Pays::select('id', 'libelle')->get();

            $queryZone = Zone::select('id', 'libelle', 'pays_id')->orderBy('libelle');
            if (!is_null($paysCurrent)) {
                $queryZone->where('pays_id', $paysCurrent);
            }
            $zones = $queryZone->get();

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

            return Inertia::render('Business::PointsVente/Edit', [
                'pointvente' => $this->transformPointVente($pointvente),
                'pays' => $payss,
                'zones' => $zones,
                'is_marchand'=> $isMarchand,
                'marchand_current'=> $marchandCurrent,
                'marchands' => $marchands,
                'paysCurrent' => $paysCurrent,
                'userStatuts' => config('appconstants.user_statut'),
            ]);
        } catch (\Throwable $e) {
            log_error("PointVente", "edit", $e->getMessage());
            return redirect()->route('pointvente.index')->with('error', trans('Erreuraffichage'));
        }
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();


            $pointvente = PointVente::findOrFail($id);

            $user = auth()->user();

            /**
             * =========================
             * Validation conditionnelle
             * =========================
             */
            $rules = [
                'zone_id'   => 'required|exists:zones,id',
                'nom'       => 'required|string|max:255',
                'adresse'   => 'required|string|max:255',
                'telephone' => 'required|string|max:255|unique:points_vente,telephone,'.$id,
                'longitude' => 'nullable|numeric',
                'latitude'  => 'nullable|numeric',
            ];

            // Admin / Superadmin uniquement
            if (!$user->marchand) {
                $rules['marchand_id'] = 'required|exists:marchands,id';
            }

            $validatedData = $request->validate($rules);
            $marchandId = $user->marchand
                ? $user->marchand->id
                : $validatedData['marchand_id'];

            $photoId = $pointvente->photo_id;
            if ($request->hasFile('photo_id')) {
                $file = $request->file('photo_id');
                $fileName = time() . '-' . Str::random(10) . '.' . $file->extension();
                $file->move(public_path('images'), $fileName);

                $photoId = Fichier::create([
                    'nom' => $fileName,
                    'source' => 'images/' . $fileName,
                ])->id;
            }

            $pointvente->update([
                'marchand_id' => $marchandId,
                'zone_id' => $validatedData['zone_id'],
                'nom' => $validatedData['nom'],
                'adresse' => $validatedData['adresse'],
                'telephone' => $validatedData['telephone'],
                'longitude' => $validatedData['longitude'],
                'latitude' => $validatedData['latitude'],
                'photo_id' => $photoId,
            ]);

            DB::commit();

            return redirect()->route('pointvente.index')->with('success', trans('modifsucces'));
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error("PointVente", "update", $e->getMessage());
            return redirect()->route('pointvente.edit', $id)->with('error', trans('erreurmaj') . ' : ' . $e->getMessage());
        }
    }

    public function statut($id)
    {
        try {

            $pointvente = PointVente::withTrashed()->findOrFail($id);

            if ($pointvente->trashed()) {
                $pointvente->restore();
                $message = trans('restaurationsucces');
            } else {
                $pointvente->delete();
                $message = trans('suppressionsucces');
            }

            return redirect()->route('pointvente.index')->with('success', $message);
        } catch (\Throwable $e) {
            log_error("PointVente", "statut", $e->getMessage());
            return redirect()->route('pointvente.index')->with('error', trans('Erreur'));
        }
    }

    public function validation(Request $request, $id, $action)
    {
        try {
            $motif = $request->input('motif');
            $pointvente = PointVente::withTrashed()->findOrFail($id);

            if ($action === 'valider') {
                $result = ValidationService::validateEntityStatut($pointvente);
            } elseif ($action === 'rejeter') {
                $result = ValidationService::rejectEntityStatut($pointvente, $motif);
            } else {
                return redirect()->route('pointvente.index')->with('error', trans('actionnonreconnue'));
            }

            if (!$result['success']) {
                return redirect()->route('pointvente.index')->with('error', $result['message']);
            }

            return redirect()->route('pointvente.index')->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("PointVente", "validation", $e->getMessage());
            return redirect()->route('pointvente.index')->with('error', trans('Erreur'));
        }
    }

    public function suspendre(Request $request, $id)
    {
        try {
            $motif = $request->input('motif');
            $pointvente = PointVente::findOrFail($id);

           // $result = ValidationService::rejectEntityStatut($pointvente, $motif);

            // Mettre le statut à suspendu
            $pointvente->update([
                'statut' => config('appconstants.user_statut.suspendu'),
                'motif' => $motif,
                'suspended_by' => auth()->user()->id,
                'blocked_by' => null,
                'validated_by' => null,
                'validated_at' => null,
            ]);

            // if (!$result['success']) {
            //     return redirect()->route('pointvente.index')->with('error', $result['message']);
            // }

            return redirect()->route('pointvente.index')->with('success', trans('suspensionsucces'));
        } catch (\Throwable $e) {
            log_error("PointVente", "suspendre", $e->getMessage());
            return redirect()->route('pointvente.index')->with('error', trans('Erreur'));
        }
    }

    public function bloquer(Request $request, $id)
    {
        try {
            $motif = $request->input('motif');
            $pointvente = PointVente::findOrFail($id);

            $pointvente->update([
                'statut' => config('appconstants.user_statut.bloque'),
                'motif' => $motif,
                'blocked_by' => auth()->user()->id,
                'suspended_by' => null,
                'validated_by' => null,
                'validated_at' => null,
            ]);

            return redirect()->route('pointvente.index')->with('success', trans('blocagesucces'));
        } catch (\Throwable $e) {
            log_error("PointVente", "bloquer", $e->getMessage());
            return redirect()->route('pointvente.index')->with('error', trans('Erreur'));
        }
    }

    private function transformPointVente(PointVente $pv): array
    {
        return [
            'id' => $pv->id,
            'nom' => $pv->nom,
            'adresse' => $pv->adresse,
            'telephone' => $pv->telephone,
            'longitude' => $pv->longitude,
            'latitude' => $pv->latitude,
            'zone_id' => $pv->zone_id,
            'zone' => $pv->zone?->libelle ?? '',
            'marchand_id' => $pv->marchand_id,
            'marchand' => $pv->marchand?->raison_sociale ?? '',
            'statut' => $pv->statut,
            'photo' => $pv->photo?->source ?? null,
        ];
    }
}
