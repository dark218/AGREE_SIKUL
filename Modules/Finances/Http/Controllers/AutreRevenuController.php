<?php

namespace Modules\Finances\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Finances\Entities\AutreRevenu;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\NiveauEtude;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Campus;

class AutreRevenuController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:autres-revenus-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:autres-revenus-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:autres-revenus-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:autres-revenus-delete', ['only' => ['destroy', 'statut']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = AutreRevenu::query();

        // Filters
        if ($request->filled('ecole_id')) {
            $query->where('ecole_id', $request->input('ecole_id'));
        }

        if ($request->filled('annee_scolaire_id')) {
            $query->where('annee_scolaire_id', $request->input('annee_scolaire_id'));
        }

        if ($request->filled('niveau_id')) {
            $query->where('niveau_id', $request->input('niveau_id'));
        }

        if ($request->filled('etat')) {
            $query->where('etat', $request->input('etat'));
        }

        $autresRevenus = $query->with(['ecole', 'niveauEtude', 'anneeScolaire', 'campus'])
            ->orderBy('created_at', 'DESC')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($ar) {
                return [
                    'id' => $ar->id,
                    'ecole' => $ar->ecole,
                    'niveauEtude' => $ar->niveauEtude,
                    'anneeScolaire' => $ar->anneeScolaire,
                    'campus' => $ar->campus,
                    'uniforme' => $ar->uniforme,
                    'tenue_mercredi' => $ar->tenue_mercredi,
                    'tenue_sport' => $ar->tenue_sport,
                    'autre1' => $ar->autre1,
                    'autre2' => $ar->autre2,
                    'autre3' => $ar->autre3,
                    'autre4' => $ar->autre4,
                    'autre5' => $ar->autre5,
                    'autre6' => $ar->autre6,
                    'etat' => $ar->etat,
                    'created_at' => $ar->created_at,
                    'updated_at' => $ar->updated_at,
                ];
            });

        return Inertia::render('Finances::AutresRevenus/Index', [
            'autresRevenus' => $autresRevenus,
            'filters' => $request->only(['ecole_id', 'annee_scolaire_id', 'niveau_id', 'etat']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $anneesScolaires = AnneeScolaire::select('id', 'libelle')
            ->where('etat', 'actif')
            ->orderBy('libelle', 'DESC')
            ->get();

        $niveauxEtudes = NiveauEtude::select('id', 'libelle')
            ->where('etat', 'actif')
            ->orderBy('libelle')
            ->get();

        $ecoles = Ecole::select('id', 'nom')
            ->where('statut', 'actif')
            ->orderBy('nom')
            ->get();

        $campuses = Campus::select('id', 'nom')
            ->where('statut', 'actif')
            ->orderBy('nom')
            ->get();

        return Inertia::render('Finances::AutresRevenus/Create', [
            'anneesScolaires' => $anneesScolaires,
            'niveauxEtudes' => $niveauxEtudes,
            'ecoles' => $ecoles,
            'campuses' => $campuses,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
            'niveau_id' => 'nullable|exists:niveaux_etudes,id',
            'ecole_id' => 'nullable|exists:ecoles,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'uniforme' => 'nullable|numeric',
            'tenue_mercredi' => 'nullable|numeric',
            'tenue_sport' => 'nullable|numeric',
            'autre1' => 'nullable|numeric',
            'autre2' => 'nullable|numeric',
            'autre3' => 'nullable|numeric',
            'autre4' => 'nullable|numeric',
            'autre5' => 'nullable|numeric',
            'autre6' => 'nullable|numeric',
            'etat' => 'required|in:actif,inactif',
        ]);

        $validated['creation_username'] = auth()->user()->name ?? 'system';

        AutreRevenu::create($validated);

        return redirect()->route('finances.autres-revenus.index')
            ->with('success', __('Autre revenu créé avec succès'));
    }

    /**
     * Display the specified resource.
     */
    public function show(AutreRevenu $autreRevenu): Response
    {
        $autreRevenu->load(['ecole', 'niveauEtude', 'anneeScolaire', 'campus']);

        $anneesScolaires = AnneeScolaire::select('id', 'libelle')
            ->where('etat', 'actif')
            ->orderBy('libelle', 'DESC')
            ->get();

        $niveauxEtudes = NiveauEtude::select('id', 'libelle')
            ->where('etat', 'actif')
            ->orderBy('libelle')
            ->get();

        $ecoles = Ecole::select('id', 'nom')
            ->where('statut', 'actif')
            ->orderBy('nom')
            ->get();

        $campuses = Campus::select('id', 'nom')
            ->where('statut', 'actif')
            ->orderBy('nom')
            ->get();

        return Inertia::render('Finances::AutresRevenus/Show', [
            'autreRevenu' => $autreRevenu,
            'anneesScolaires' => $anneesScolaires,
            'niveauxEtudes' => $niveauxEtudes,
            'ecoles' => $ecoles,
            'campuses' => $campuses,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AutreRevenu $autreRevenu): Response
    {
        $autreRevenu->load(['ecole', 'niveauEtude', 'anneeScolaire', 'campus']);

        $anneesScolaires = AnneeScolaire::select('id', 'libelle')
            ->where('etat', 'actif')
            ->orderBy('libelle', 'DESC')
            ->get();

        $niveauxEtudes = NiveauEtude::select('id', 'libelle')
            ->where('etat', 'actif')
            ->orderBy('libelle')
            ->get();

        $ecoles = Ecole::select('id', 'nom')
            ->where('statut', 'actif')
            ->orderBy('nom')
            ->get();

        $campuses = Campus::select('id', 'nom')
            ->where('statut', 'actif')
            ->orderBy('nom')
            ->get();

        return Inertia::render('Finances::AutresRevenus/Edit', [
            'autreRevenu' => $autreRevenu,
            'anneesScolaires' => $anneesScolaires,
            'niveauxEtudes' => $niveauxEtudes,
            'ecoles' => $ecoles,
            'campuses' => $campuses,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AutreRevenu $autreRevenu)
    {
        $validated = $request->validate([
            'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
            'niveau_id' => 'nullable|exists:niveaux_etudes,id',
            'ecole_id' => 'nullable|exists:ecoles,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'uniforme' => 'nullable|numeric',
            'tenue_mercredi' => 'nullable|numeric',
            'tenue_sport' => 'nullable|numeric',
            'autre1' => 'nullable|numeric',
            'autre2' => 'nullable|numeric',
            'autre3' => 'nullable|numeric',
            'autre4' => 'nullable|numeric',
            'autre5' => 'nullable|numeric',
            'autre6' => 'nullable|numeric',
            'etat' => 'required|in:actif,inactif',
        ]);

        $validated['modification_username'] = auth()->user()->name ?? 'system';

        $autreRevenu->update($validated);

        return redirect()->route('finances.autres-revenus.index')
            ->with('success', __('Autre revenu modifié avec succès'));
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(AutreRevenu $autreRevenu)
    {
        $autreRevenu->delete();

        return redirect()->route('finances.autres-revenus.index')
            ->with('success', __('Autre revenu supprimé avec succès'));
    }

    /**
     * Toggle the status (etat) of the specified resource.
     */
    public function statut(AutreRevenu $autreRevenu)
    {
        $autreRevenu->etat = $autreRevenu->etat === 'actif' ? 'inactif' : 'actif';
        $autreRevenu->modification_username = auth()->user()->name ?? 'system';
        $autreRevenu->save();

        return redirect()->route('finances.autres-revenus.index')
            ->with('success', __('Statut modifié avec succès'));
    }
}
