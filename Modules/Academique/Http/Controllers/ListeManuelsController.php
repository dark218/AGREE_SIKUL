<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Academique\Entities\ListeManuels;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Section;
use Modules\Parametrage\Entities\Pays;
use Modules\Parametrage\Entities\NiveauEtude;
use Modules\Parametrage\Entities\CycleEnseignement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

            $listes = $query->with(['anneeScolaire', 'ecole', 'section', 'niveau', 'cycle', 'pays'])
                ->withCount(['livres', 'cahiers', 'fournitures'])
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
            return Inertia::render('Academique::ListesManuels/Create', $this->formLookups());
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
            $validated = $request->validate($this->validationRules());

            DB::transaction(function () use ($validated, $request) {
                $liste = ListeManuels::create($this->headerData($validated, $request));
                $this->syncBlocks($liste, $request);
            });

            return redirect()->route('academique.listes-manuels.index')
                ->with('success', 'Liste enregistrée avec succès');
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
            $listeManuels->load(['anneeScolaire', 'ecole', 'section', 'niveau', 'cycle', 'pays', 'livres', 'cahiers', 'fournitures']);

            return Inertia::render('Academique::ListesManuels/Show', array_merge($this->formLookups(), [
                'manuel' => $listeManuels,
            ]));
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
            $listeManuels->load(['anneeScolaire', 'ecole', 'section', 'niveau', 'cycle', 'pays', 'livres', 'cahiers', 'fournitures']);

            return Inertia::render('Academique::ListesManuels/Edit', array_merge($this->formLookups(), [
                'manuel' => $listeManuels,
            ]));
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
            $validated = $request->validate($this->validationRules());

            DB::transaction(function () use ($validated, $request, $listeManuels) {
                $listeManuels->update($this->headerData($validated, $request));
                $this->syncBlocks($listeManuels, $request);
            });

            return redirect()->route('academique.listes-manuels.index')
                ->with('success', 'Liste modifiée avec succès');
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

    /**
     * Listes d'options communes aux formulaires.
     */
    private function formLookups(): array
    {
        return [
            'anneesScolaires' => AnneeScolaire::select('id', 'libelle')->where('etat', 'actif')->orderBy('libelle', 'desc')->get(),
            'ecoles' => Ecole::select('id', 'nom as libelle')->where('etat', 'actif')->orderBy('libelle')->get(),
            // ecole_id + niveau_etude_id : permettent la cascade École → Section → Niveau côté formulaire.
            'sections' => Section::select('id', 'libelle', 'ecole_id', 'niveau_etude_id')->where('etat', 'actif')->orderBy('libelle')->get(),
            // cycle_id + pays_id : remontés automatiquement quand on choisit le niveau (via la section).
            'niveaux' => NiveauEtude::select('id', 'libelle', 'cycle_id', 'pays_id')->where('etat', 'actif')->get(),
            'cycles' => CycleEnseignement::whereNull('deleted_at')->orderBy('libelle')->get(['id', 'libelle']),
            'pays' => Pays::select('id', 'libelle')->where('etat', 'actif')->orderBy('libelle')->get(),
        ];
    }

    /**
     * Règles de validation (en-tête + 3 blocs répétables).
     */
    private function validationRules(): array
    {
        return [
            'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
            'ecole_id' => 'nullable|exists:ecoles,id',
            'section_id' => 'nullable|exists:sections,id',
            'niveau_id' => 'nullable|exists:niveaux_etudes,id',
            'cycle_id' => 'nullable|exists:cycles_enseignement,id',
            'pays_id' => 'nullable|exists:pays,id',
            'etat' => 'required|in:actif,inactif',

            'livres' => 'nullable|array',
            'livres.*.titre' => 'nullable|string|max:255',
            'livres.*.sujet' => 'nullable|string|max:255',
            'livres.*.langue' => 'nullable|string|max:100',
            'livres.*.auteurs' => 'nullable|string|max:500',
            'livres.*.editeurs' => 'nullable|string|max:255',
            'livres.*.annee_edition' => 'nullable|integer',

            'cahiers' => 'nullable|array',
            'cahiers.*.utilite' => 'nullable|string|max:255',
            'cahiers.*.type_cahier' => 'nullable|string|max:255',
            'cahiers.*.nombre_pages' => 'nullable|integer|min:0',
            'cahiers.*.quantite' => 'nullable|integer|min:0',

            'fournitures' => 'nullable|array',
            'fournitures.*.utilite' => 'nullable|string|max:255',
            'fournitures.*.designation' => 'nullable|string|max:255',
            'fournitures.*.quantite' => 'nullable|integer|min:0',
            'fournitures.*.fournisseur' => 'nullable|string|max:255',
        ];
    }

    /**
     * Prépare les données d'en-tête (titre_manuel conservé pour compat NOT NULL).
     */
    private function headerData(array $validated, Request $request): array
    {
        $titre = collect($request->input('livres', []) ?? [])
            ->pluck('titre')->filter()->first() ?? 'Liste de fournitures';

        return [
            'annee_scolaire_id' => $validated['annee_scolaire_id'] ?? null,
            'ecole_id' => $validated['ecole_id'] ?? null,
            'section_id' => $validated['section_id'] ?? null,
            'niveau_id' => $validated['niveau_id'] ?? null,
            'cycle_id' => $validated['cycle_id'] ?? null,
            'pays_id' => $validated['pays_id'] ?? null,
            'etat' => $validated['etat'],
            'titre_manuel' => $titre,
        ];
    }

    /**
     * Remplace les 3 blocs (livres, cahiers, fournitures) d'une liste.
     */
    private function syncBlocks(ListeManuels $liste, Request $request): void
    {
        $liste->livres()->withTrashed()->forceDelete();
        foreach (array_values($request->input('livres', []) ?? []) as $i => $row) {
            if (empty($row['titre'])) {
                continue;
            }
            $liste->livres()->create([
                'titre' => $row['titre'],
                'sujet' => $row['sujet'] ?? null,
                'langue' => $row['langue'] ?? null,
                'auteurs' => $row['auteurs'] ?? null,
                'editeurs' => $row['editeurs'] ?? null,
                'annee_edition' => $row['annee_edition'] ?? null,
                'ordre' => $i,
            ]);
        }

        $liste->cahiers()->withTrashed()->forceDelete();
        foreach (array_values($request->input('cahiers', []) ?? []) as $i => $row) {
            if (empty($row['utilite']) && empty($row['type_cahier']) && empty($row['nombre_pages'])) {
                continue;
            }
            $liste->cahiers()->create([
                'utilite' => $row['utilite'] ?? null,
                'type_cahier' => $row['type_cahier'] ?? null,
                'nombre_pages' => $row['nombre_pages'] ?? null,
                'quantite' => $row['quantite'] ?? 1,
                'ordre' => $i,
            ]);
        }

        $liste->fournitures()->withTrashed()->forceDelete();
        foreach (array_values($request->input('fournitures', []) ?? []) as $i => $row) {
            if (empty($row['designation']) && empty($row['utilite'])) {
                continue;
            }
            $liste->fournitures()->create([
                'utilite' => $row['utilite'] ?? null,
                'designation' => $row['designation'] ?? null,
                'quantite' => $row['quantite'] ?? 1,
                'fournisseur' => $row['fournisseur'] ?? null,
                'ordre' => $i,
            ]);
        }
    }
}
