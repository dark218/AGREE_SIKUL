<?php

namespace Modules\RessourcesLogistique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\RessourcesLogistique\Entities\BibliothequeStructure;
use Modules\RessourcesLogistique\Entities\EntreeLivre;
use Modules\RessourcesLogistique\Entities\Ouvrage;

/**
 * Bibliothèque › Entrée de livres (emprunt / achat / don).
 */
class EntreeLivreController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:entrees-livres-list',   ['only' => ['index', 'show']]);
        $this->middleware('permission.check:entrees-livres-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:entrees-livres-edit',   ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:entrees-livres-delete', ['only' => ['destroy']]);
    }

    private function rules(): array
    {
        return [
            'bibliotheque_structure_id' => 'nullable|exists:bibliotheque_structures,id',
            'ouvrage_id'                => 'nullable|exists:ouvrages,id',
            'type_entree'               => 'required|in:emprunt,achat,don',
            'date_entree'               => 'nullable|date',
            'quantite'                  => 'required|integer|min:1',
            'date_retour'               => 'nullable|date',
            'tiers'                     => 'nullable|string|max:255',
            'etat_physique'             => 'nullable|string|max:100',
            'etat'                      => 'nullable|in:actif,inactif',
        ];
    }

    /**
     * Options partagées : bibliothèques + catalogue d'ouvrages (avec les champs
     * livre pour l'auto-remplissage côté formulaire).
     */
    private function options(): array
    {
        return [
            'structures' => BibliothequeStructure::whereNull('deleted_at')->where('etat', 'actif')
                ->orderBy('libelle')->get(['id', 'libelle'])
                ->map(fn ($s) => ['id' => $s->id, 'libelle' => $s->libelle]),
            'ouvrages' => Ouvrage::whereNull('deleted_at')->orderBy('titre')
                ->get(['id', 'titre', 'auteur', 'editeur', 'langue', 'categorie', 'annee_publication'])
                ->map(fn ($o) => [
                    'id'                => $o->id,
                    'libelle'           => $o->titre,
                    'titre'             => $o->titre,
                    'auteur'            => $o->auteur,
                    'editeur'           => $o->editeur,
                    'langue'            => $o->langue,
                    'sujet'             => $o->categorie, // Sujet/Matière = catégorie du catalogue
                    'annee_publication' => $o->annee_publication,
                ]),
        ];
    }

    public function index(Request $request)
    {
        try {
            $query = EntreeLivre::query()->with(['bibliothequeStructure', 'ouvrage']);

            if ($request->filled('search')) {
                $s = $request->input('search');
                $query->whereHas('ouvrage', fn ($q) => $q->where('titre', 'like', "%$s%")->orWhere('auteur', 'like', "%$s%"))
                      ->orWhere('tiers', 'like', "%$s%");
            }
            if ($request->filled('type_entree')) {
                $query->where('type_entree', $request->input('type_entree'));
            }

            $entrees = $query->paginate(10)->withQueryString();

            return Inertia::render('RessourcesLogistique::EntreesLivres/Index', [
                'entrees' => $entrees,
                'filters' => $request->only(['search', 'type_entree']),
            ]);
        } catch (\Throwable $th) {
            log_error('EntreeLivre', 'index', $th->getMessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        return Inertia::render('RessourcesLogistique::EntreesLivres/Create', $this->options());
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->rules());
            $validated['etat'] = $validated['etat'] ?? 'actif';
            EntreeLivre::create($validated);

            return redirect()->route('entrees-livres.index')->with('success', 'Entrée enregistrée avec succès');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error('EntreeLivre', 'store', $th->getMessage());
            return back()->with('error', 'Erreur lors de l\'enregistrement : ' . $th->getMessage())->withInput();
        }
    }

    public function show(EntreeLivre $entrees_livre)
    {
        return Inertia::render('RessourcesLogistique::EntreesLivres/Show', array_merge(
            $this->options(),
            ['entree' => $entrees_livre->load(['bibliothequeStructure', 'ouvrage'])]
        ));
    }

    public function edit(EntreeLivre $entrees_livre)
    {
        return Inertia::render('RessourcesLogistique::EntreesLivres/Edit', array_merge(
            $this->options(),
            ['entree' => $entrees_livre->load(['bibliothequeStructure', 'ouvrage'])]
        ));
    }

    public function update(Request $request, EntreeLivre $entrees_livre)
    {
        try {
            $validated = $request->validate($this->rules());
            $entrees_livre->update($validated);

            return redirect()->route('entrees-livres.index')->with('success', 'Entrée modifiée avec succès');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error('EntreeLivre', 'update', $th->getMessage());
            return back()->with('error', 'Erreur lors de la modification : ' . $th->getMessage())->withInput();
        }
    }

    public function destroy(EntreeLivre $entrees_livre)
    {
        try {
            $entrees_livre->delete();
            return redirect()->route('entrees-livres.index')->with('success', 'Entrée supprimée');
        } catch (\Throwable $th) {
            log_error('EntreeLivre', 'destroy', $th->getMessage());
            return back()->with('error', 'Erreur lors de la suppression');
        }
    }
}
