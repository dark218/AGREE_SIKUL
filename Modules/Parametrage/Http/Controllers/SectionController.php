<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\Section;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\NiveauEtude;
use Modules\Parametrage\Entities\AnneeScolaire;
use Illuminate\Foundation\Validation\ValidatesRequests;

class SectionController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-section-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-section-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-section-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-section-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-section-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Section::with(['ecole', 'niveauEtude', 'anneeScolaire']);

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $sections = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::Sections/Index', [
                'sections' => $sections,
                'filters' => $request->only(['code', 'libelle', 'etat']),
            ]);
        } catch (\Exception $e) {
            \Log::error('SectionController@error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        return Inertia::render('Parametrage::Sections/Create');
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:sections,code',
                'libelle' => 'required|string|max:255',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['created_by'] = auth()->id();
            $validated['etat'] = $validated['etat'] ?? 'actif';

            Section::create($validated);

            return redirect()
                ->route('parametrage.sections.index')
                ->with('success', 'Créé avec succès');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('SectionController@store: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la création: ' . $e->getMessage());
        }
    }

    public function show(Section $section)
    {
        try {
            $ecoles = Ecole::actif()->orderBy('nom')->get(['id', 'nom']);

            $niveaux = NiveauEtude::actif()->orderBy('libelle')->get(['id', 'libelle'])->map(function($niveau) {
                return [
                    'id' => $niveau->id,
                    'libelle' => $niveau->libelle,
                ];
            });

            $anneesScolaires = AnneeScolaire::where('etat', 'actif')->orderBy('libelle')->get(['id', 'libelle'])->map(function($annee) {
                return [
                    'id' => $annee->id,
                    'libelle' => $annee->libelle,
                ];
            });

            return Inertia::render('Parametrage::Sections/Show', [
                'section' => $section,
                'ecoles' => $ecoles,
                'niveaux' => $niveaux,
                'anneesScolaires' => $anneesScolaires,
            ]);
        } catch (\Exception $e) {
            \Log::error('SectionController@error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(Section $section)
    {
        return Inertia::render('Parametrage::Sections/Edit', [
            'section' => $section,
        ]);
    }

    public function update(Request $request, Section $section)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:sections,code,' . $section->id,
                'libelle' => 'required|string|max:255',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['updated_by'] = auth()->id();
            $validated['etat'] = $validated['etat'] ?? $section->etat;
            $section->update($validated);

            return redirect()
                ->route('parametrage.sections.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            \Log::error('SectionController@update: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(Section $section)
    {
        try {
            $section->deleted_by = auth()->id();
            $section->save();
            $section->delete();

            return redirect()->route('parametrage.sections.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            \Log::error('SectionController@error: ' . $e->getMessage());
            return redirect()->route('parametrage.sections.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(Section $section)
    {
        try {
            $newEtat = $section->etat === 'actif' ? 'inactif' : 'actif';
            $section->etat = $newEtat;
            $section->updated_by = auth()->id();
            $section->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.sections.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            \Log::error('SectionController@error: ' . $e->getMessage());
            return redirect()->route('parametrage.sections.index')->with('error', 'Erreur lors du changement de statut');
        }
    }
}
