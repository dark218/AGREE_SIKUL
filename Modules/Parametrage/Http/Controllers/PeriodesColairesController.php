<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\PeriodeColaire;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\Ecole;
use Illuminate\Foundation\Validation\ValidatesRequests;

class PeriodesColairesController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-periodescolaires-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-periodescolaires-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-periodescolaires-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-periodescolaires-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-periodescolaires-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = PeriodeColaire::query()->with('anneeScolaire', 'ecole');

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            if ($request->filled('annee_scolaire_id')) {
                $query->where('annee_scolaire_id', $request->annee_scolaire_id);
            }

            $periodesColaires = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::PeriodesColaires/Index', [
                'periodesColaires' => $periodesColaires,
                'filters' => $request->only(['code', 'libelle', 'etat', 'annee_scolaire_id']),
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        try {
            $annees_scolaires = AnneeScolaire::actif()
                ->orderBy('libelle')
                ->get(['id', 'libelle', 'code'])
                ->toArray();

            $ecoles = Ecole::actif()
                ->orderBy('nom')
                ->get(['id', 'nom', 'code'])
                ->toArray();

            $cycles = \Modules\Parametrage\Entities\CycleEnseignement::orderBy('libelle')
                ->get(['id', 'libelle'])
                ->toArray();

            return Inertia::render('Parametrage::PeriodesColaires/Create', [
                'annees_scolaires' => $annees_scolaires,
                'ecoles' => $ecoles,
                'cycles' => $cycles,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:periodes_colaires,code',
                'libelle' => 'required|string|max:255',
                'cycle_id' => 'nullable|exists:cycles_enseignement,id',
                'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
                'date_debut' => 'required|date|date_format:Y-m-d',
                'date_fin' => 'required|date|date_format:Y-m-d|after:date_debut',
                'duree' => 'nullable|integer|min:0',
                'type_periode' => 'nullable|in:trimestre,semestre,quadrimestre,annuel',
                'numero_ordre' => 'nullable|integer|min:1',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'est_periode_evaluation' => 'nullable|boolean',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['est_periode_evaluation'] = $validated['est_periode_evaluation'] ?? false;
            $validated['created_by'] = auth()->id();

            // Auto-calcul de la durée en jours si non fournie
            if (empty($validated['duree']) && !empty($validated['date_debut']) && !empty($validated['date_fin'])) {
                $validated['duree'] = \Carbon\Carbon::parse($validated['date_debut'])
                    ->diffInDays(\Carbon\Carbon::parse($validated['date_fin']));
            }

            PeriodeColaire::create($validated);

            return redirect()
                ->route('parametrage.periodes_colaires.index')
                ->with('success', 'Période créée avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            \Log::error('PeriodeColaire Store Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Erreur lors de la création: ' . $e->getMessage())->withInput();
        }
    }

    public function show(PeriodeColaire $periodeColaire)
    {
        try {
            $annees_scolaires = AnneeScolaire::actif()
                ->orderBy('libelle')
                ->get(['id', 'libelle', 'code'])
                ->toArray();

            $ecoles = Ecole::actif()
                ->orderBy('nom')
                ->get(['id', 'nom', 'code'])
                ->toArray();

            $data = $periodeColaire->toArray();
            $data['date_debut'] = $periodeColaire->date_debut?->format('Y-m-d');
            $data['date_fin'] = $periodeColaire->date_fin?->format('Y-m-d');

            return Inertia::render('Parametrage::PeriodesColaires/Show', [
                'periodeColaire' => $data,
                'annees_scolaires' => $annees_scolaires,
                'ecoles' => $ecoles,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(PeriodeColaire $periodeColaire)
    {
        try {
            $annees_scolaires = AnneeScolaire::actif()
                ->orderBy('libelle')
                ->get(['id', 'libelle', 'code'])
                ->toArray();

            $ecoles = Ecole::actif()
                ->orderBy('nom')
                ->get(['id', 'nom', 'code'])
                ->toArray();

            $data = $periodeColaire->toArray();
            $data['date_debut'] = $periodeColaire->date_debut?->format('Y-m-d');
            $data['date_fin'] = $periodeColaire->date_fin?->format('Y-m-d');

            $cycles = \Modules\Parametrage\Entities\CycleEnseignement::orderBy('libelle')
                ->get(['id', 'libelle'])
                ->toArray();

            return Inertia::render('Parametrage::PeriodesColaires/Edit', [
                'item' => $data,
                'annees_scolaires' => $annees_scolaires,
                'ecoles' => $ecoles,
                'cycles' => $cycles,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function update(Request $request, PeriodeColaire $periodeColaire)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:periodes_colaires,code,' . $periodeColaire->id,
                'libelle' => 'required|string|max:255',
                'cycle_id' => 'nullable|exists:cycles_enseignement,id',
                'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
                'duree' => 'nullable|integer|min:0',
                'type_periode' => 'nullable|in:trimestre,semestre,quadrimestre,annuel',
                'numero_ordre' => 'nullable|integer|min:1',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'date_debut' => 'required|date|date_format:Y-m-d',
                'date_fin' => 'required|date|date_format:Y-m-d|after:date_debut',
                'est_periode_evaluation' => 'nullable|boolean',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? $periodeColaire->etat;
            $validated['est_periode_evaluation'] = $validated['est_periode_evaluation'] ?? false;
            $validated['updated_by'] = auth()->id();

            // Auto-recalcul durée si dates changent
            if (empty($validated['duree']) && !empty($validated['date_debut']) && !empty($validated['date_fin'])) {
                $validated['duree'] = \Carbon\Carbon::parse($validated['date_debut'])
                    ->diffInDays(\Carbon\Carbon::parse($validated['date_fin']));
            }

            $periodeColaire->update($validated);

            return redirect()
                ->route('parametrage.periodes_colaires.index')
                ->with('success', 'Période modifiée avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            \Log::error('PeriodeColaire Update Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Erreur lors de la modification: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(PeriodeColaire $periodeColaire)
    {
        try {
            $periodeColaire->deleted_by = auth()->id();
            $periodeColaire->save();
            $periodeColaire->delete();

            return redirect()->route('parametrage.periodes_colaires.index')->with('success', 'Période supprimée avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            \Log::error('PeriodeColaire Delete Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->route('parametrage.periodes_colaires.index')->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    public function activate(PeriodeColaire $periodeColaire)
    {
        try {
            $newEtat = $periodeColaire->etat === 'actif' ? 'inactif' : 'actif';
            $periodeColaire->etat = $newEtat;
            $periodeColaire->updated_by = auth()->id();
            $periodeColaire->save();

            $message = $newEtat === 'actif' ? 'Période activée' : 'Période désactivée';
            return redirect()->route('parametrage.periodes_colaires.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.periodes_colaires.index')->with('error', 'Erreur lors du changement de statut');
        }
    }
}
