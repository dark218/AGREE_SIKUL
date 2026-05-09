<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academique\Entities\PresenceSeance;
use Modules\Academique\Entities\Seance;
use Modules\Academique\Entities\Apprenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PresenceSeanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:presence-seances-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:presence-seances-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:presence-seances-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:presence-seances-delete', ['only' => ['destroy', 'activate']]);
    }

    public function index(Request $request)
    {
        try {
            \Log::info('🔍 PresenceSeanceController::index() called');

            $query = PresenceSeance::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('seance', function ($q) use ($search) {
                    $q->where('titre', 'like', "%$search%");
                });
            }

            $presenceSeances = $query->with(['seance', 'apprenant'])->paginate(10)->withQueryString();

            // Map collection to plain arrays for Inertia serialization
            $presenceSeances->setCollection(
                $presenceSeances->getCollection()->map(function($item) {
                    return [
                        'id' => $item->id,
                        'nom' => trim(($item->apprenant?->prenoms ?? '') . ' ' . ($item->apprenant?->nom ?? '')),
                        'titre' => $item->seance?->cours?->titre ?? 'Sans cours',
                        'date_presence' => $item->date_presence?->format('d/m/Y') ?? '-',
                        'heure_arrivee' => $item->heure_arrivee ?? '-',
                        'heure_depart' => $item->heure_depart ?? '-',
                        'apprenant_id' => $item->apprenant_id,
                        'seance_id' => $item->seance_id,
                        'statut' => $item->statut,
                        'deleted_at' => $item->deleted_at,
                    ];
                })
            );

            \Log::info('✅ PresenceSeances loaded successfully', [
                'total' => $presenceSeances->total(),
                'per_page' => $presenceSeances->perPage(),
            ]);

            return Inertia::render('Academique::PresencesSeances/Index', [
                'title' => __('common.presences_seances'),
                'presencesSeances' => $presenceSeances,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            \Log::error('❌ PresenceSeanceController::index() ERROR', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            log_error("Academique", "PresenceSeanceController::index", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function create()
    {
        try {
            \Log::info('🎯🎯🎯 PresenceSeanceController::create() CALLED! 🎯🎯🎯');
            \Log::info('   User ID: ' . Auth::id());
            \Log::info('   User Email: ' . Auth::user()?->email);
            \Log::info('   Has permission presence-seances-create: ' . (Auth::user()?->can('presence-seances-create') ? 'YES' : 'NO'));

            \Log::info('📊 Loading seances...');
            $seances = Seance::with('cours:id,titre')
                ->select('id', 'date', 'heure_debut', 'heure_fin', 'cours_id', 'salle')
                ->get()
                ->map(fn($seance) => [
                    'id' => $seance->id,
                    'titre' => ($seance->cours ? $seance->cours->titre : 'Sans cours') . ' - ' . $seance->date->format('d/m/Y'),
                    'date' => $seance->date->format('Y-m-d'),
                ])
                ->toArray();
            \Log::info('✅ Seances loaded', ['count' => count($seances)]);

            \Log::info('📊 Loading apprenants...');
            $apprenants = Apprenant::select('id', 'prenoms', 'nom', 'matricule')
                ->get()
                ->map(fn($apprenant) => [
                    'id' => $apprenant->id,
                    'libelle' => $apprenant->prenoms . ' ' . $apprenant->nom . ' (' . $apprenant->matricule . ')',
                ])
                ->toArray();
            \Log::info('✅ Apprenants loaded', ['count' => count($apprenants)]);

            \Log::info('📋 Rendering Inertia page');
            return Inertia::render('Academique::PresencesSeances/Create', [
                'title' => __('actions.create'),
                'seances' => $seances,
                'apprenants' => $apprenants,
            ]);
        } catch (\Throwable $th) {
            \Log::error('❌ PresenceSeanceController::create() ERROR', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);
            log_error("Academique", "PresenceSeanceController::create", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            \Log::info('🔍 PresenceSeanceController::store() called', [
                'all_inputs' => $request->all(),
            ]);

            $validated = $request->validate([
                'seance_id' => 'required|exists:seances,id',
                'apprenant_id' => 'required|exists:apprenants,id',
                'date_presence' => 'required|date',
                'heure_arrivee' => 'nullable|date_format:H:i',
                'heure_depart' => 'nullable|date_format:H:i',
                'statut' => 'required|in:present,absent,retard,justifie',
                'remarques' => 'nullable|string',
            ]);

            \Log::info('✅ Validation passed', ['validated' => $validated]);

            PresenceSeance::create($validated);

            \Log::info('✅ PresenceSeance created successfully');

            return redirect()->route('academique.presences_seances.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            \Log::error('❌ PresenceSeanceController::store() ERROR', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            log_error("Academique", "PresenceSeanceController::store", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function show(PresenceSeance $presenceSeance)
    {
        try {
            $presenceSeance->load('seance', 'apprenant');

            // Format date for display
            $presenceSeance->date_presence_formatted = $presenceSeance->date_presence?->format('Y-m-d');

            $seances = Seance::with('cours:id,titre')
                ->select('id', 'date', 'heure_debut', 'heure_fin', 'cours_id', 'salle')
                ->get()
                ->map(fn($seance) => [
                    'id' => $seance->id,
                    'titre' => ($seance->cours ? $seance->cours->titre : 'Sans cours') . ' - ' . $seance->date->format('d/m/Y'),
                    'date' => $seance->date->format('Y-m-d'),
                ])
                ->toArray();
            $apprenants = Apprenant::select('id', 'prenoms', 'nom', 'matricule')
                ->get()
                ->map(fn($apprenant) => [
                    'id' => $apprenant->id,
                    'libelle' => $apprenant->prenoms . ' ' . $apprenant->nom . ' (' . $apprenant->matricule . ')',
                ])
                ->toArray();

            return Inertia::render('Academique::PresencesSeances/Show', [
                'title' => __('actions.view'),
                'presenceSeance' => $presenceSeance,
                'seances' => $seances,
                'apprenants' => $apprenants,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "PresenceSeanceController::show", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function edit(PresenceSeance $presenceSeance)
    {
        try {
            $presenceSeance->load('seance', 'apprenant');

            // Format date for display
            $presenceSeance->date_presence_formatted = $presenceSeance->date_presence?->format('Y-m-d');

            $seances = Seance::with('cours:id,titre')
                ->select('id', 'date', 'heure_debut', 'heure_fin', 'cours_id', 'salle')
                ->get()
                ->map(fn($seance) => [
                    'id' => $seance->id,
                    'titre' => ($seance->cours ? $seance->cours->titre : 'Sans cours') . ' - ' . $seance->date->format('d/m/Y'),
                    'date' => $seance->date->format('Y-m-d'),
                ])
                ->toArray();
            $apprenants = Apprenant::select('id', 'prenoms', 'nom', 'matricule')
                ->get()
                ->map(fn($apprenant) => [
                    'id' => $apprenant->id,
                    'libelle' => $apprenant->prenoms . ' ' . $apprenant->nom . ' (' . $apprenant->matricule . ')',
                ])
                ->toArray();

            return Inertia::render('Academique::PresencesSeances/Edit', [
                'title' => __('actions.edit'),
                'presenceSeance' => $presenceSeance,
                'seances' => $seances,
                'apprenants' => $apprenants,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "PresenceSeanceController::edit", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function update(Request $request, PresenceSeance $presenceSeance)
    {
        try {
            $validated = $request->validate([
                'seance_id' => 'required|exists:seances,id',
                'apprenant_id' => 'required|exists:apprenants,id',
                'date_presence' => 'required|date',
                'heure_arrivee' => 'nullable|date_format:H:i',
                'heure_depart' => 'nullable|date_format:H:i',
                'statut' => 'required|in:present,absent,retard,justifie',
                'remarques' => 'nullable|string',
            ]);

            $presenceSeance->update($validated);

            return redirect()->route('academique.presences_seances.show', $presenceSeance)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "PresenceSeanceController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function destroy(PresenceSeance $presenceSeance)
    {
        try {
            $presenceSeance->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "PresenceSeanceController::destroy", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function statut(PresenceSeance $presenceSeance)
    {
        try {
            if ($presenceSeance->trashed()) {
                $presenceSeance->restore();
            } else {
                $presenceSeance->delete();
            }

            return redirect()->route('academique.presences_seances.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Academique", "PresenceSeanceController::statut", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }
}
