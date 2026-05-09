<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Academique\Entities\ListeManuels;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Section;
use Modules\Parametrage\Entities\Pays;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ListeManuelsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:listes-manuels-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:listes-manuels-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:listes-manuels-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:listes-manuels-delete', ['only' => ['destroy', 'statut']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = ListeManuels::query();

            // Search filter
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('titre_manuel', 'like', "%$search%")
                        ->orWhere('auteurs', 'like', "%$search%")
                        ->orWhere('editeur', 'like', "%$search%");
                });
            }

            // Section filter
            if ($request->filled('section_id')) {
                $query->where('section_id', $request->input('section_id'));
            }

            // Etat filter
            if ($request->filled('etat')) {
                $query->where('etat', $request->input('etat'));
            }

            // Annee scolaire filter
            if ($request->filled('annee_scolaire_id')) {
                $query->where('annee_scolaire_id', $request->input('annee_scolaire_id'));
            }

            $listes = $query->with(['anneeScolaire', 'ecole', 'section', 'pays'])
                ->paginate(10)
                ->withQueryString();

            return Inertia::render('Academique::ListesManuels/Index', [
                'listes' => $listes,
                'filters' => $request->only(['search', 'section_id', 'etat', 'annee_scolaire_id']),
            ]);
        } catch (\Throwable $th) {
            log_error('Academique', 'ListeManuelsController::index', $th->getMessage());
            return back()->withErrors(['error' => 'Erreur lors du chargement de la liste']);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            $anneesScolaires = AnneeScolaire::select('id', 'libelle')
                ->where('etat', 'actif')
                ->orderBy('libelle', 'desc')
                ->get();

            $ecoles = Ecole::select('id', 'nom as libelle')
                ->where('etat', 'actif')
                ->orderBy('libelle')
                ->get();

            $sections = Section::select('id', 'nom as libelle')
                ->where('etat', 'actif')
                ->orderBy('libelle')
                ->get();

            $pays = Pays::select('id', 'libelle')
                ->where('etat', 'actif')
                ->orderBy('libelle')
                ->get();

            return Inertia::render('Academique::ListesManuels/Create', [
                'anneesScolaires' => $anneesScolaires,
                'ecoles' => $ecoles,
                'sections' => $sections,
                'pays' => $pays,
            ]);
        } catch (\Throwable $th) {
            log_error('Academique', 'ListeManuelsController::create', $th->getMessage());
            return back()->withErrors(['error' => 'Erreur lors de l\'accès au formulaire']);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'section_id' => 'nullable|exists:sections,id',
                'type_manuel' => 'nullable|string|max:255',
                'titre_manuel' => 'required|string|max:255',
                'auteurs' => 'nullable|string|max:500',
                'editeur' => 'nullable|string|max:255',
                'annee_edition' => 'nullable|integer|min:1900|max:' . date('Y') + 10,
                'pays_id' => 'nullable|exists:pays,id',
                'etat' => 'required|in:actif,inactif',
            ]);

            ListeManuels::create($validated);

            return redirect()->route('academique.listes-manuels.index')
                ->with('success', 'Manuel ajouté avec succès');
        } catch (\Throwable $th) {
            log_error('Academique', 'ListeManuelsController::store', $th->getMessage());
            return back()->withErrors(['error' => $th->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ListeManuels $listeManuels)
    {
        try {
            $listeManuels->load(['anneeScolaire', 'ecole', 'section', 'pays']);

            $anneesScolaires = AnneeScolaire::select('id', 'libelle')
                ->where('etat', 'actif')
                ->orderBy('libelle', 'desc')
                ->get();

            $ecoles = Ecole::select('id', 'nom as libelle')
                ->where('etat', 'actif')
                ->orderBy('libelle')
                ->get();

            $sections = Section::select('id', 'nom as libelle')
                ->where('etat', 'actif')
                ->orderBy('libelle')
                ->get();

            $pays = Pays::select('id', 'libelle')
                ->where('etat', 'actif')
                ->orderBy('libelle')
                ->get();

            return Inertia::render('Academique::ListesManuels/Show', [
                'manuel' => $listeManuels,
                'anneesScolaires' => $anneesScolaires,
                'ecoles' => $ecoles,
                'sections' => $sections,
                'pays' => $pays,
            ]);
        } catch (\Throwable $th) {
            log_error('Academique', 'ListeManuelsController::show', $th->getMessage());
            return back()->withErrors(['error' => 'Erreur lors de l\'affichage du manuel']);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ListeManuels $listeManuels)
    {
        try {
            $listeManuels->load(['anneeScolaire', 'ecole', 'section', 'pays']);

            $anneesScolaires = AnneeScolaire::select('id', 'libelle')
                ->where('etat', 'actif')
                ->orderBy('libelle', 'desc')
                ->get();

            $ecoles = Ecole::select('id', 'nom as libelle')
                ->where('etat', 'actif')
                ->orderBy('libelle')
                ->get();

            $sections = Section::select('id', 'nom as libelle')
                ->where('etat', 'actif')
                ->orderBy('libelle')
                ->get();

            $pays = Pays::select('id', 'libelle')
                ->where('etat', 'actif')
                ->orderBy('libelle')
                ->get();

            return Inertia::render('Academique::ListesManuels/Edit', [
                'manuel' => $listeManuels,
                'anneesScolaires' => $anneesScolaires,
                'ecoles' => $ecoles,
                'sections' => $sections,
                'pays' => $pays,
            ]);
        } catch (\Throwable $th) {
            log_error('Academique', 'ListeManuelsController::edit', $th->getMessage());
            return back()->withErrors(['error' => 'Erreur lors de l\'accès au formulaire']);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ListeManuels $listeManuels)
    {
        try {
            $validated = $request->validate([
                'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'section_id' => 'nullable|exists:sections,id',
                'type_manuel' => 'nullable|string|max:255',
                'titre_manuel' => 'required|string|max:255',
                'auteurs' => 'nullable|string|max:500',
                'editeur' => 'nullable|string|max:255',
                'annee_edition' => 'nullable|integer|min:1900|max:' . date('Y') + 10,
                'pays_id' => 'nullable|exists:pays,id',
                'etat' => 'required|in:actif,inactif',
            ]);

            $listeManuels->update($validated);

            return redirect()->route('academique.listes-manuels.index')
                ->with('success', 'Manuel modifié avec succès');
        } catch (\Throwable $th) {
            log_error('Academique', 'ListeManuelsController::update', $th->getMessage());
            return back()->withErrors(['error' => $th->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(ListeManuels $listeManuels)
    {
        try {
            $listeManuels->delete();
            return redirect()->route('academique.listes-manuels.index')
                ->with('success', 'Manuel supprimé avec succès');
        } catch (\Throwable $th) {
            log_error('Academique', 'ListeManuelsController::destroy', $th->getMessage());
            return back()->withErrors(['error' => 'Erreur lors de la suppression']);
        }
    }

    /**
     * Toggle the status (etat) of the specified resource.
     */
    public function statut(ListeManuels $listeManuels)
    {
        try {
            $listeManuels->etat = $listeManuels->etat === 'actif' ? 'inactif' : 'actif';
            $listeManuels->save();

            return redirect()->route('academique.listes-manuels.index')
                ->with('success', 'Statut modifié avec succès');
        } catch (\Throwable $th) {
            log_error('Academique', 'ListeManuelsController::statut', $th->getMessage());
            return back()->withErrors(['error' => 'Erreur lors de la modification du statut']);
        }
    }
}
