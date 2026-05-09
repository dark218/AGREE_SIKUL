<?php

namespace Modules\Business\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Modules\Administration\Http\Controllers\ErrorLogController;
use Modules\Business\Entities\Caisse;
use Modules\Business\Entities\PointVente;

class CaisseController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:caisse-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:caisse-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:caisse-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:caisse-statut', ['only' => ['changerStatut']]);
    }

    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $marchand = $user->marchand; // null si admin / superadmin

            $pointVenteId = $request->input('point_vente_id');
            $nom = $request->input('nom');
            $code = $request->input('code');
            $type = $request->input('type');
            $statut = $request->input('statut');
            $pointsVenteQuery = PointVente::select('id', 'nom')
                ->where('statut', config('appconstants.pointvente_statut.actif'))
                ->whereNull('parent_points_vente_id')
                ->orderBy('nom');

            if ($marchand) {
                $pointsVenteQuery->where('marchand_id', $marchand->id);
            }

            $pointsVente = $pointsVenteQuery
                ->get()
                ->map(fn($pv) => ['value' => $pv->id, 'label' => $pv->nom]);


            $query = Caisse::with(['pointVente'])
                ->orderBy('created_at', 'DESC');

            if ($marchand) {
                $query->whereHas('pointVente', function ($q) use ($marchand) {
                    $q->where('marchand_id', $marchand->id);
                });
            }
            if (!empty($pointVenteId)) {
                $query->where('points_vente_id', $pointVenteId);
            }


            if (!empty($nom)) {
                $query->where('nom', 'LIKE', "%{$nom}%");
            }

            if (!empty($code)) {
                $query->where('code', 'LIKE', "%{$code}%");
            }

            if (!empty($type)) {
                $query->where('type', $type);
            }

            if (!empty($statut)) {
                $query->where('statut', $statut);
            }

            $caisses = $query->paginate(10)->withQueryString();

            $caisses->getCollection()->transform(function ($caisse) {
                return [
                    'id' => $caisse->id,
                    'code' => $caisse->code,
                    'nom' => $caisse->nom,
                    'type' => $caisse->type,
                    'type_label' => collect($this->getTypes())->firstWhere('value', $caisse->type)['label'] ?? $caisse->type,
                    'statut' => $caisse->statut,
                    'statut_label' => collect($this->getStatuts())->firstWhere('value', $caisse->statut)['label'] ?? $caisse->statut,
                    'point_vente' => $caisse->pointVente?->nom ?? '',
                    'created_at' => $caisse->created_at?->format('d/m/Y H:i'),
                    'updated_at' => $caisse->updated_at?->format('d/m/Y H:i'),
                ];
            });


            return Inertia::render('Business::Caisses/Index', [
                'caisses' => $caisses,
                'types' => $this->getTypes(),
                'statuts' => $this->getStatuts(),
                'pointsVente' => $pointsVente,
                'filters' => [
                    'point_vente_id' => $pointVenteId,
                    'nom' => $nom,
                    'code' => $code,
                    'type' => $type,
                    'statut' => $statut,
                ],
            ]);
        } catch (\Throwable $e) {
            log_error("Caisse", "index", $e->getMessage());
            return redirect()->route('home')->with('error', 'Une erreur est survenue lors du chargement des caisses.');
        }
    }

    public function create(Request $request)
    {
        try {
            $user = auth()->user();
            $marchand = $user->marchand;
            $pointVenteId = $request->input('point_vente_id');

            // Récupérer la liste des points de vente actifs
            $pointsVenteQuery = PointVente::select('id', 'nom')
                ->where('statut', config('appconstants.pointvente_statut.actif'))
                ->whereNull('parent_points_vente_id')
                ->orderBy('nom');

            if ($marchand) {
                $pointsVenteQuery->where('marchand_id', $marchand->id);
            }

            $pointsVente = $pointsVenteQuery
                ->get()
                ->map(fn($pv) => ['value' => $pv->id, 'label' => $pv->nom]);


            return Inertia::render('Business::Caisses/Create', [
                'types' => $this->getTypes(),
                'pointsVente' => $pointsVente,
                'point_vente_id' => $pointVenteId,
            ]);
        } catch (\Throwable $e) {
            log_error("Caisse", "create", $e->getMessage());
            return redirect()->route('caisse.index')->with('error', 'Erreur lors de l\'affichage du formulaire de création.');
        }
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'points_vente_id' => 'required|exists:points_vente,id',
                'code' => 'nullable|string|max:50|unique:caisses,code',
                'nom' => 'required|string|max:255',
                'type' => 'required|in:' . implode(',', array_column($this->getTypes(), 'value')),
                'parametres_json' => 'nullable|array',
            ]);
            //Point de vente parent
            $pointVente = PointVente::whereNull('parent_points_vente_id')
                ->findOrFail($validatedData['points_vente_id']);
            DB::beginTransaction();
            $emplacementCaisse = PointVente::create([
                'marchand_id' => $pointVente->marchand_id,
                'zone_id'     => $pointVente->zone_id,
                'statut'      => config('appconstants.pointvente_statut.actif'),
                'nom'         => $validatedData['nom'] . ' - ' . $pointVente->nom,
                'adresse'     => $pointVente->adresse,
                'parent_points_vente_id' => $pointVente->id,
                'param_pos_json' => [
                    'type' => 'caisse',
                ],
            ]);

            // Générer un code unique si non fourni
            if (empty($validatedData['code'])) {
                $validatedData['code'] = 'CAISSE-' . strtoupper(Str::random(8));
            }

            // Définir le statut par défaut
            $validatedData['statut'] = config('appconstants.caisse_statut.fermee');


            $caisse = Caisse::create($validatedData);
            QrCodeService::generateStatique(
                $emplacementCaisse->id,
                [
                    'type'        => 'caisse',
                    'caisse_code' => $caisse->code,
                ]
            );
            DB::commit();

            return redirect()
                ->route('caisse.index')
                ->with('success', 'La caisse a été créée avec succès.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error("Caisse", "store", $e->getMessage());
            return redirect()
                ->back()
                ->with('error', 'Une erreur est survenue lors de la création de la caisse.')
                ->withInput();
        }
    }

    public function show($id)
    {
        try {
            $caisse = Caisse::with(['pointVente'])->findOrFail($id);

            return Inertia::render('Business::Caisses/Show', [
                'caisse' => [
                    'id' => $caisse->id,
                    'code' => $caisse->code,
                    'nom' => $caisse->nom,
                    'type' => $caisse->type,
                    'type_label' => collect($this->getTypes())->firstWhere('value', $caisse->type)['label'] ?? $caisse->type,
                    'statut' => $caisse->statut,
                    'statut_label' => collect($this->getStatuts())->firstWhere('value', $caisse->statut)['label'] ?? $caisse->statut,
                    'point_vente' => $caisse->pointVente ? [
                        'id' => $caisse->pointVente->id,
                        'nom' => $caisse->pointVente->nom,
                    ] : null,
                    'parametres_json' => $caisse->parametres_json,
                    'created_at' => $caisse->created_at?->format('d/m/Y H:i'),
                    'updated_at' => $caisse->updated_at?->format('d/m/Y H:i'),
                ],
                'types' => $this->getTypes(),
                'statuts' => $this->getStatuts(),
            ]);
        } catch (\Throwable $e) {
            log_error("Caisse", "show", $e->getMessage());
            return redirect()->route('caisse.index')->with('error', 'Caisse non trouvée.');
        }
    }

    public function edit($id)
    {
        try {
            $caisse = Caisse::findOrFail($id);

            // Récupérer la liste des points de vente actifs
            $user = auth()->user();
            $marchand = $user->marchand;

            $pointsVenteQuery = PointVente::select('id', 'nom')
                ->where('statut', config('appconstants.pointvente_statut.actif'))
                ->whereNull('parent_points_vente_id')
                ->orderBy('nom');

            if ($marchand) {
                $pointsVenteQuery->where('marchand_id', $marchand->id);
            }

            $pointsVente = $pointsVenteQuery
                ->get()
                ->map(fn($pv) => ['value' => $pv->id, 'label' => $pv->nom]);


            return Inertia::render('Business::Caisses/Edit', [
                'caisse' => [
                    'id' => $caisse->id,
                    'points_vente_id' => $caisse->points_vente_id,
                    'code' => $caisse->code,
                    'nom' => $caisse->nom,
                    'type' => $caisse->type,
                    'statut' => $caisse->statut,
                    'parametres_json' => $caisse->parametres_json,
                ],
                'types' => $this->getTypes(),
                'statuts' => $this->getStatuts(),
                'pointsVente' => $pointsVente,
            ]);
        } catch (\Throwable $e) {
            log_error("Caisse", "edit", $e->getMessage());
            return redirect()->route('caisse.index')->with('error', 'Caisse non trouvée.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $caisse = Caisse::findOrFail($id);

            $validatedData = $request->validate([
                'points_vente_id' => 'required|exists:points_vente,id',
                'code' => 'required|string|max:50|unique:caisses,code,' . $id,
                'nom' => 'required|string|max:255',
                'type' => 'required|in:' . implode(',', array_column($this->getTypes(), 'value')),
                'statut' => 'required|in:' . implode(',', array_column($this->getStatuts(), 'value')),
                'parametres_json' => 'nullable|array',
            ]);

            $caisse->update($validatedData);

            return redirect()
                ->route('caisse.show', $caisse->id)
                ->with('success', 'La caisse a été mise à jour avec succès.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Throwable $e) {
            log_error("Caisse", "update", $e->getMessage());
            return redirect()
                ->back()
                ->with('error', 'Une erreur est survenue lors de la mise à jour de la caisse.')
                ->withInput();
        }
    }

    public function bloquer($id)
    {
        try {
            $caisse = Caisse::withTrashed()->findOrFail($id);

            $caisse->bloquer();

            return redirect()->route('caisse.index')->with('success', 'La caisse a été bloquée avec succès.');

        }catch (\Throwable $e) {
            log_error("Caisse", "bloquer", $e->getMessage());
            return redirect()->route('pointvente.index')->with('error', trans('Erreur'));
        }
    }

    public function statut( $id)
    {
        try {
            $caisse
                = Caisse::withTrashed()->findOrFail($id);

            if ($caisse
                ->trashed()) {
                $caisse
                    ->restore();
                $message = trans('restaurationsucces');
            } else {
                $caisse
                    ->delete();
                $message = trans('suppressionsucces');
            }

            return redirect()->route('caisse.index')->with('success', $message);

        } catch (\Throwable $e) {
            log_error("Caisse", "statut", $e->getMessage());
            return redirect()->route('caisse.index')->with('error', trans('Erreur'));

        }
    }

    /**
     * Méthodes utilitaires
     */

    /**
     * Retourne la liste des types de caisse
     */
    protected function getTypes(): array
    {
        return [
            ['value' => 'physique', 'label' => 'Physique'],
            ['value' => 'mobile', 'label' => 'Mobile'],
            ['value' => 'virtuelle', 'label' => 'Virtuelle'],
            ['value' => 'principale', 'label' => 'Principale'],
            ['value' => 'secondaire', 'label' => 'Secondaire'],
        ];
    }

    /**
     * Retourne la liste des statuts de caisse
     */
    protected function getStatuts(): array
    {
        return [
            ['value' => 'ouverte', 'label' => 'Ouverte'],
            ['value' => 'fermee', 'label' => 'Fermée'],
            ['value' => 'bloquee', 'label' => 'Bloquée'],
        ];
    }
}
