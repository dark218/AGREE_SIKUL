<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academique\Entities\MoyenneMatiere;
use Modules\Academique\Entities\Bulletin;
use Modules\Parametrage\Entities\MatiereUnite;
use Modules\Academique\Entities\Note;
use Modules\Academique\Entities\Apprenant;

class MoyenneMatiereController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:moyennes-matieres-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:moyennes-matieres-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:moyennes-matieres-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:moyennes-matieres-delete', ['only' => ['destroy', 'statut']]);
    }

    public function checkExists(Request $request)
    {
        try {
            $apprenant_id = $request->input('apprenant_id');
            $matiere_id = $request->input('matiere_id');

            if (!$apprenant_id || !$matiere_id) {
                return response()->json(['exists' => false]);
            }

            $moyenne = MoyenneMatiere::where('apprenant_id', $apprenant_id)
                ->where('matiere_id', $matiere_id)
                ->whereNull('bulletin_id')  // Avant qu'elle soit associée à un bulletin
                ->first();

            return response()->json([
                'exists' => $moyenne !== null,
                'data' => $moyenne ? [
                    'id' => $moyenne->id,
                    'apprenant_id' => $moyenne->apprenant_id,
                    'matiere_id' => $moyenne->matiere_id,
                    'moyenne' => $moyenne->moyenne,
                ] : null,
            ]);
        } catch (\Throwable $th) {
            return response()->json(['exists' => false, 'error' => $th->getMessage()]);
        }
    }

    public function getAveragesByApprenant(Request $request)
    {
        try {
            $apprenant_id = $request->input('apprenant_id');

            if (!$apprenant_id) {
                return response()->json(['averages' => []]);
            }

            // Récupérer TOUTES les moyennes créées pour cet apprenant (avec ou sans bulletin)
            $moyennes = MoyenneMatiere::where('apprenant_id', $apprenant_id)
                ->with('matiere')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($m) {
                    return [
                        'id' => $m->id,
                        'matiere_id' => $m->matiere_id,
                        'matiere_libelle' => $m->matiere?->libelle ?? 'N/A',
                        'moyenne' => $m->moyenne,
                        'coefficient' => $m->coefficient,
                        'appreciation' => $m->appreciation,
                        'rang' => $m->rang,
                        'bulletin_id' => $m->bulletin_id,
                        'created_at' => $m->created_at->format('d/m/Y H:i'),
                    ];
                });

            return response()->json([
                'success' => true,
                'averages' => $moyennes,
                'total' => $moyennes->count(),
            ]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'averages' => [], 'error' => $th->getMessage()]);
        }
    }

    public function index(Request $request)
    {
        try {
            $query = MoyenneMatiere::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('matiere', function ($q) use ($search) {
                    $q->where('libelle', 'like', "%$search%")
                        ->orWhere('code', 'like', "%$search%");
                });
            }

            $moyennes = $query->with(['matiere', 'bulletin'])->paginate(10)->withQueryString();

            return Inertia::render('Academique::MoyennesMatieres/Index', [
                'title' => __('common.moyennes_matieres'),
                'moyennesMatieres' => $moyennes,
                'filters' => $request->only(['search']),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "MoyenneMatiereController::index", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function create()
    {
        try {
            // Load apprenants with user info for better display
            $apprenants = Apprenant::with('user', 'classe')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($a) {
                    $apprenantName = trim(($a->user?->prenoms ?? '') . ' ' . ($a->user?->nom ?? ''));
                    $classeName = $a->classe?->nom ?? 'N/A';
                    $matricule = $a->matricule ?? 'N/A';

                    return [
                        'id' => $a->id,
                        'libelle' => "{$apprenantName} ({$matricule}) | Classe: {$classeName}",
                    ];
                })
                ->values()
                ->toArray();

            // Load matieres with coefficient info for better display
            $matieres = MatiereUnite::select('id', 'libelle', 'coefficient')
                ->get()
                ->map(fn($m) => [
                    'id' => $m->id,
                    'libelle' => "{$m->libelle} (Coef: {$m->coefficient})",
                    'coefficient' => $m->coefficient
                ])
                ->toArray();

            return Inertia::render('Academique::MoyennesMatieres/Create', [
                'title' => __('actions.create'),
                'apprenants' => $apprenants,
                'matieres' => $matieres,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "MoyenneMatiereController::create", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            // Calculate max value for moyenne based on coefficient (pondérée moyenne)
            $maxMoyenne = 20 * (floatval($request->input('coefficient')) ?: 1);

            $validated = $request->validate([
                'apprenant_id' => 'required|exists:apprenants,id',
                'matiere_id' => 'required|exists:matieres_unites,id',
                'moyenne' => "required|numeric|min:0|max:$maxMoyenne",
                'coefficient' => 'required|numeric|min:0',
                'rang' => 'nullable|integer|min:1',
                'appreciation' => 'required|string',
                'bulletin_id' => 'nullable|exists:bulletins,id',  // Optionnel pour la Phase 1
            ]);

            MoyenneMatiere::create($validated);

            return redirect()->route('academique.moyennes_matieres.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "MoyenneMatiereController::store", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function show(MoyenneMatiere $moyenneMatiere)
    {
        try {
            $moyenneMatiere->load('matiere', 'bulletin');

            // Load bulletins with better display info
            $bulletins = Bulletin::with(['apprenant.user', 'classe', 'anneeScolaire'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($b) {
                    $apprenantName = trim(($b->apprenant?->user?->prenoms ?? '') . ' ' . ($b->apprenant?->user?->nom ?? ''));
                    $classeName = $b->classe?->nom ?? 'N/A';
                    $periodeName = __("fields.{$b->periode}") ?? $b->periode;
                    $anneeName = $b->anneeScolaire?->libelle ?? 'N/A';

                    return [
                        'id' => $b->id,
                        'libelle' => "Apprenant: {$apprenantName} | Classe: {$classeName} | Période: {$periodeName} | Année: {$anneeName}",
                        '_key' => $b->apprenant_id . '|' . $b->classe_id . '|' . ($b->periode ?? 'null') . '|' . ($b->annee_scolaire_id ?? 'null')
                    ];
                })
                // Filtrer les doublons en gardant seulement le plus récent
                ->unique('_key')
                ->map(function($b) {
                    unset($b['_key']); // Supprimer la clé temporaire
                    return $b;
                })
                ->values()
                ->toArray();

            // Load matieres with coefficient
            $matieres = MatiereUnite::select('id', 'libelle', 'coefficient')
                ->get()
                ->map(fn($m) => [
                    'id' => $m->id,
                    'libelle' => "{$m->libelle} (Coef: {$m->coefficient})",
                    'coefficient' => $m->coefficient
                ])
                ->toArray();

            // Load apprenants with user info for better display
            $apprenants = Apprenant::with('user', 'classe')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($a) {
                    $apprenantName = trim(($a->user?->prenoms ?? '') . ' ' . ($a->user?->nom ?? ''));
                    $classeName = $a->classe?->nom ?? 'N/A';
                    $matricule = $a->matricule ?? 'N/A';

                    return [
                        'id' => $a->id,
                        'libelle' => "{$apprenantName} ({$matricule}) | Classe: {$classeName}",
                    ];
                })
                ->values()
                ->toArray();

            return Inertia::render('Academique::MoyennesMatieres/Show', [
                'title' => __('actions.view'),
                'moyenneMatiere' => $moyenneMatiere,
                'apprenants' => $apprenants,
                'bulletins' => $bulletins,
                'matieres' => $matieres,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "MoyenneMatiereController::show", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function edit(MoyenneMatiere $moyenneMatiere)
    {
        try {
            // Load bulletins with better display info
            $bulletins = Bulletin::with(['apprenant.user', 'classe', 'anneeScolaire'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($b) {
                    $apprenantName = trim(($b->apprenant?->user?->prenoms ?? '') . ' ' . ($b->apprenant?->user?->nom ?? ''));
                    $classeName = $b->classe?->nom ?? 'N/A';
                    $periodeName = __("fields.{$b->periode}") ?? $b->periode;
                    $anneeName = $b->anneeScolaire?->libelle ?? 'N/A';

                    return [
                        'id' => $b->id,
                        'libelle' => "Apprenant: {$apprenantName} | Classe: {$classeName} | Période: {$periodeName} | Année: {$anneeName}",
                        '_key' => $b->apprenant_id . '|' . $b->classe_id . '|' . ($b->periode ?? 'null') . '|' . ($b->annee_scolaire_id ?? 'null')
                    ];
                })
                // Filtrer les doublons en gardant seulement le plus récent
                ->unique('_key')
                ->map(function($b) {
                    unset($b['_key']); // Supprimer la clé temporaire
                    return $b;
                })
                ->values()
                ->toArray();

            // Load matieres with coefficient info for better display
            $matieres = MatiereUnite::select('id', 'libelle', 'coefficient')
                ->get()
                ->map(fn($m) => [
                    'id' => $m->id,
                    'libelle' => "{$m->libelle} (Coef: {$m->coefficient})",
                    'coefficient' => $m->coefficient
                ])
                ->toArray();

            // Load apprenants with user info for better display
            $apprenants = Apprenant::with('user', 'classe')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($a) {
                    $apprenantName = trim(($a->user?->prenoms ?? '') . ' ' . ($a->user?->nom ?? ''));
                    $classeName = $a->classe?->nom ?? 'N/A';
                    $matricule = $a->matricule ?? 'N/A';

                    return [
                        'id' => $a->id,
                        'libelle' => "{$apprenantName} ({$matricule}) | Classe: {$classeName}",
                    ];
                })
                ->values()
                ->toArray();

            return Inertia::render('Academique::MoyennesMatieres/Edit', [
                'title' => __('actions.edit'),
                'moyenneMatiere' => $moyenneMatiere,
                'apprenants' => $apprenants,
                'bulletins' => $bulletins,
                'matieres' => $matieres,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "MoyenneMatiereController::edit", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function update(Request $request, MoyenneMatiere $moyenneMatiere)
    {
        try {
            // Calculate max value for moyenne based on coefficient (pondérée moyenne)
            $maxMoyenne = 20 * (floatval($request->input('coefficient')) ?: 1);

            $validated = $request->validate([
                'bulletin_id' => 'required|exists:bulletins,id',
                'matiere_id' => 'required|exists:matieres_unites,id',
                'moyenne' => "required|numeric|min:0|max:$maxMoyenne",
                'coefficient' => 'required|numeric|min:0',
                'rang' => 'nullable|integer|min:1',
                'appreciation' => 'required|string',
            ]);

            $moyenneMatiere->update($validated);

            return redirect()->route('academique.moyennes_matieres.show', $moyenneMatiere)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "MoyenneMatiereController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function destroy(MoyenneMatiere $moyenneMatiere)
    {
        try {
            $moyenneMatiere->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "MoyenneMatiereController::destroy", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function statut(Request $request, MoyenneMatiere $moyenneMatiere)
    {
        try {
            // Si un nouveau statut est fourni dans la requête, le mettre à jour
            if ($request->filled('new_statut')) {
                $newStatut = $request->input('new_statut');
                $validStatuts = ['actif', 'inactif', 'suspendu', 'archive'];

                if (in_array($newStatut, $validStatuts)) {
                    $moyenneMatiere->update(['statut' => $newStatut]);
                    return redirect()->back()
                        ->with('success', __('messages.status_changed'));
                }
            }

            // Sinon, faire un toggle soft-delete (compatibilité arrière)
            if ($moyenneMatiere->trashed()) {
                $moyenneMatiere->restore();
            } else {
                $moyenneMatiere->delete();
            }

            return redirect()->route('academique.moyennes_matieres.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Academique", "MoyenneMatiereController::statut", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }
}
