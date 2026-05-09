<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academique\Entities\Seance;

class SeanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:seances', ['only' => ['index', 'show', 'create', 'store', 'edit', 'update', 'destroy', 'activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Seance::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('titre', 'like', "%$search%")
                    ->orWhere('sujet', 'like', "%$search%");
            }

            $seances = $query->with('cours')->paginate(10)->withQueryString();

            // DEBUG: Log avant transform
            $first = $seances->first();
            \Log::info('🔴 AVANT TRANSFORM:', [
                'has_cours' => $first && $first->cours ? true : false,
                'cours_id' => $first?->cours_id,
                'cours_object' => $first?->cours,
                'cours_titre' => $first?->cours?->titre,
            ]);

            // Transformer manuellement chaque seance
            $transformed = $seances->getCollection()->map(function($seance) {
                $row = [
                    'id' => $seance->id,
                    'titre' => $seance->titre,
                    'cours' => $seance->cours?->titre ?? '-',
                    'date' => $seance->date?->format('Y-m-d'),
                    'heure_debut' => $seance->heure_debut,
                    'heure_fin' => $seance->heure_fin,
                    'statut' => $seance->statut,
                    'deleted_at' => $seance->deleted_at,
                ];
                \Log::info('✅ TRANSFORMED ROW:', $row);
                return $row;
            });

            // Remplacer la collection
            $seances->setCollection($transformed);

            \Log::info('📤 ENVOI A INERTIA:', [
                'seances_data' => $seances->toArray(),
            ]);

            return Inertia::render('Academique::Seances/Index', [
                'title' => __('common.seances'),
                'seances' => $seances,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "SeanceController::index", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function create()
    {
        try {
            \Log::info('🖱️ SeanceController::create() called');

            $cours = \DB::table('cours')
                ->select('id', 'titre as libelle')
                ->where('statut', 'actif')
                ->get()
                ->toArray();

            \Log::info('📚 Cours loaded:', ['count' => count($cours), 'data' => $cours]);

            $salles = \DB::table('salles')
                ->select('id', 'libelle')
                ->where('statut', 'actif')
                ->get()
                ->toArray();

            \Log::info('🏢 Salles loaded:', ['count' => count($salles), 'data' => $salles]);

            \Log::info('✅ Create page rendering with data', ['cours_count' => count($cours), 'salles_count' => count($salles)]);

            return Inertia::render('Academique::Seances/Create', [
                'title' => __('actions.create'),
                'cours' => $cours,
                'salles' => $salles,
            ]);
        } catch (\Throwable $th) {
            \Log::error('❌ SeanceController::create error:', ['message' => $th->getMessage(), 'file' => $th->getFile(), 'line' => $th->getLine()]);
            log_error("Academique", "SeanceController::create", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            \Log::info('📤 SeanceController::store() called');
            \Log::info('Request data:', $request->all());

            $validated = $request->validate([
                'cours_id' => 'required|exists:cours,id',
                'salle_id' => 'nullable|exists:salles,id',
                'titre' => 'required|string|max:255',
                'sujet' => 'nullable|string',
                'date' => 'required|date',
                'heure_debut' => 'required|date_format:H:i',
                'heure_fin' => 'required|date_format:H:i',
                'duree' => 'required|numeric|min:0',
                'statut' => 'required|in:planifiee,realisee,annulee',
            ]);

            \Log::info('✅ Validation passed:', $validated);

            $seance = Seance::create($validated);

            \Log::info('✅ Seance created successfully:', ['id' => $seance->id]);

            return redirect()->route('academique.seances.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            \Log::error('❌ SeanceController::store error:', ['message' => $th->getMessage(), 'file' => $th->getFile(), 'line' => $th->getLine()]);
            log_error("Academique", "SeanceController::store", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function show(Seance $seance)
    {
        try {
            $seance->load('cours', 'salle', 'presences');

            // DEBUG: Log date format
            \Log::info('📅 SeanceController::show() - Date Debug', [
                'date_raw' => $seance->date,
                'date_type' => gettype($seance->date),
                'date_class' => get_class($seance->date),
                'date_formatted_Ymd' => $seance->date?->format('Y-m-d'),
                'date_formatted_dmy' => $seance->date?->format('d/m/Y'),
                'seance_id' => $seance->id,
            ]);

            $cours = \DB::table('cours')
                ->select('id', 'titre as libelle')
                ->where('statut', 'actif')
                ->get()
                ->toArray();

            $salles = \DB::table('salles')
                ->select('id', 'libelle')
                ->where('statut', 'actif')
                ->get()
                ->toArray();

            // Transform seance for proper date formatting
            $seanceData = [
                'id' => $seance->id,
                'code' => $seance->code,
                'titre' => $seance->titre,
                'sujet' => $seance->sujet,
                'date' => $seance->date?->format('Y-m-d'),
                'heure_debut' => $seance->heure_debut,
                'heure_fin' => $seance->heure_fin,
                'duree' => $seance->duree,
                'cours_id' => $seance->cours_id,
                'classe_id' => $seance->classe_id,
                'matiere_id' => $seance->matiere_id,
                'enseignant_id' => $seance->enseignant_id,
                'salle_id' => $seance->salle_id,
                'type_seance' => $seance->type_seance,
                'statut' => $seance->statut,
                'deleted_at' => $seance->deleted_at,
                'cours' => $seance->cours,
                'salle' => $seance->salle,
                'presences' => $seance->presences,
            ];

            \Log::info('✅ Seance data transformed for Show page', ['data' => $seanceData]);

            return Inertia::render('Academique::Seances/Show', [
                'title' => __('actions.view'),
                'seance' => $seanceData,
                'cours' => $cours,
                'salles' => $salles,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "SeanceController::show", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function edit(Seance $seance)
    {
        try {
            $seance->load('cours', 'salle');

            $cours = \DB::table('cours')
                ->select('id', 'titre as libelle')
                ->where('statut', 'actif')
                ->get()
                ->toArray();

            $salles = \DB::table('salles')
                ->select('id', 'libelle')
                ->where('statut', 'actif')
                ->get()
                ->toArray();

            // Transform seance for proper date formatting
            $seanceData = [
                'id' => $seance->id,
                'code' => $seance->code,
                'titre' => $seance->titre,
                'sujet' => $seance->sujet,
                'date' => $seance->date?->format('Y-m-d'),
                'heure_debut' => $seance->heure_debut,
                'heure_fin' => $seance->heure_fin,
                'duree' => $seance->duree,
                'cours_id' => $seance->cours_id,
                'classe_id' => $seance->classe_id,
                'matiere_id' => $seance->matiere_id,
                'enseignant_id' => $seance->enseignant_id,
                'salle_id' => $seance->salle_id,
                'type_seance' => $seance->type_seance,
                'statut' => $seance->statut,
                'deleted_at' => $seance->deleted_at,
                'cours' => $seance->cours,
                'salle' => $seance->salle,
            ];

            return Inertia::render('Academique::Seances/Edit', [
                'title' => __('actions.edit'),
                'seance' => $seanceData,
                'cours' => $cours,
                'salles' => $salles,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "SeanceController::edit", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function update(Request $request, Seance $seance)
    {
        try {
            $validated = $request->validate([
                'cours_id' => 'required|exists:cours,id',
                'salle_id' => 'nullable|exists:salles,id',
                'titre' => 'required|string|max:255',
                'sujet' => 'nullable|string',
                'date' => 'required|date',
                'heure_debut' => 'required|date_format:H:i',
                'heure_fin' => 'required|date_format:H:i',
                'duree' => 'required|numeric|min:0',
                'statut' => 'required|in:planifiee,realisee,annulee',
            ]);

            $seance->update($validated);

            return redirect()->route('academique.seances.show', $seance)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "SeanceController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function destroy(Seance $seance)
    {
        try {
            $seance->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "SeanceController::destroy", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function statut(Seance $seance)
    {
        try {
            if ($seance->trashed()) {
                $seance->restore();
            } else {
                $seance->delete();
            }

            return redirect()->route('academique.seances.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Academique", "SeanceController::statut", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }
}
