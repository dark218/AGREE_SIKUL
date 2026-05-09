<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\Pays;
use Illuminate\Foundation\Validation\ValidatesRequests;

class AnneesScolairesController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-anneesscolaires-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-anneesscolaires-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-anneesscolaires-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-anneesscolaires-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-anneesscolaires-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = AnneeScolaire::with('pays');

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $anneesScolaires = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::AnneesScolaires/Index', [
                'anneesScolaires' => $anneesScolaires,
                'filters' => $request->only(['code', 'libelle', 'statut', 'etat']),
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Parametrage::AnneesScolaires/Create', [
                'pays' => Pays::all(),
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
                'code' => 'required|string|max:100|unique:annees_scolaires,code',
                'libelle' => 'required|string|max:255',
                'date_debut' => 'required|date',
                'date_fin' => 'required|date|after:date_debut',
                'duree' => 'required|integer|min:1',
                'pays_id' => 'nullable|exists:pays,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['created_by'] = auth()->id();
            AnneeScolaire::create($validated);

            return redirect()
                ->route('parametrage.annees_scolaires.index')
                ->with('success', 'Créé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            \Log::error('AnneeScolaire Store Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Erreur lors de la création: ' . $e->getMessage())->withInput();
        }
    }

    public function show(AnneeScolaire $anneeScolaire)
    {
        try {
            $data = $anneeScolaire->toArray();
            $data['date_debut'] = $anneeScolaire->date_debut?->format('Y-m-d');
            $data['date_fin'] = $anneeScolaire->date_fin?->format('Y-m-d');

            return Inertia::render('Parametrage::AnneesScolaires/Show', [
                'anneeScolaire' => $data,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(AnneeScolaire $anneeScolaire)
    {
        try {
            $data = $anneeScolaire->toArray();
            $data['date_debut'] = $anneeScolaire->date_debut?->format('Y-m-d');
            $data['date_fin'] = $anneeScolaire->date_fin?->format('Y-m-d');

            return Inertia::render('Parametrage::AnneesScolaires/Edit', [
                'annee_scolaire' => $data,
                'pays' => Pays::all(),
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function update(Request $request, AnneeScolaire $anneeScolaire)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:annees_scolaires,code,' . $anneeScolaire->id,
                'libelle' => 'required|string|max:255',
                'date_debut' => 'required|date',
                'date_fin' => 'required|date|after:date_debut',
                'duree' => 'required|integer|min:1',
                'pays_id' => 'nullable|exists:pays,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['updated_by'] = auth()->id();
            $anneeScolaire->update($validated);

            return redirect()
                ->route('parametrage.annees_scolaires.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            \Log::error('AnneeScolaire Update Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Erreur lors de la modification: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(AnneeScolaire $anneeScolaire)
    {
        try {
            $anneeScolaire->deleted_by = auth()->id();
            $anneeScolaire->save();
            $anneeScolaire->delete();

            return redirect()->route('parametrage.annees_scolaires.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.annees_scolaires.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(AnneeScolaire $anneeScolaire)
    {
        try {
            $newEtat = $anneeScolaire->etat === 'actif' ? 'inactif' : 'actif';
            $anneeScolaire->etat = $newEtat;
            $anneeScolaire->updated_by = auth()->id();
            $anneeScolaire->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.annees_scolaires.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.annees_scolaires.index')->with('error', 'Erreur lors du changement de statut');
        }
    }

    public function makeCurrent(AnneeScolaire $anneeScolaire)
    {
        try {
            // Désactiver l'année courante précédente
            AnneeScolaire::where('est_courante', true)->update(['est_courante' => false]);

            // Définir cette année comme courante
            $anneeScolaire->est_courante = true;
            $anneeScolaire->updated_by = auth()->id();
            $anneeScolaire->save();

            return redirect()->route('parametrage.annees_scolaires.index')->with('success', 'Année scolaire définie comme courante avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.annees_scolaires.index')->with('error', 'Erreur lors de la définition de l\'année courante');
        }
    }
}
