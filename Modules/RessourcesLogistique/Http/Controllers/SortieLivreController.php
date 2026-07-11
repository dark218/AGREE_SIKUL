<?php

namespace Modules\RessourcesLogistique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\RessourcesLogistique\Entities\BibliothequeStructure;
use Modules\RessourcesLogistique\Entities\Ouvrage;
use Modules\RessourcesLogistique\Entities\SortieLivre;

/**
 * Bibliothèque › Sortie de livres (prêt / vente / don).
 */
class SortieLivreController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:sorties-livres-list',   ['only' => ['index', 'show']]);
        $this->middleware('permission.check:sorties-livres-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:sorties-livres-edit',   ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:sorties-livres-delete', ['only' => ['destroy']]);
    }

    private function rules(): array
    {
        return [
            'bibliotheque_structure_id' => 'nullable|exists:bibliotheque_structures,id',
            'ouvrage_id'                => 'nullable|exists:ouvrages,id',
            'type_sortie'               => 'required|in:pret,vente,don',
            'date_sortie'               => 'nullable|date',
            'quantite'                  => 'required|integer|min:1',
            'date_retour'               => 'nullable|date',
            'tiers'                     => 'nullable|string|max:255',
            'etat_physique'             => 'nullable|string|max:100',
            'etat'                      => 'nullable|in:actif,inactif',
        ];
    }

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
                    'sujet'             => $o->categorie,
                    'annee_publication' => $o->annee_publication,
                ]),
        ];
    }

    public function index(Request $request)
    {
        try {
            $query = SortieLivre::query()->with(['bibliothequeStructure', 'ouvrage']);

            if ($request->filled('search')) {
                $s = $request->input('search');
                $query->whereHas('ouvrage', fn ($q) => $q->where('titre', 'like', "%$s%")->orWhere('auteur', 'like', "%$s%"))
                      ->orWhere('tiers', 'like', "%$s%");
            }
            if ($request->filled('type_sortie')) {
                $query->where('type_sortie', $request->input('type_sortie'));
            }

            $sorties = $query->paginate(10)->withQueryString();

            return Inertia::render('RessourcesLogistique::SortiesLivres/Index', [
                'sorties' => $sorties,
                'filters' => $request->only(['search', 'type_sortie']),
            ]);
        } catch (\Throwable $th) {
            log_error('SortieLivre', 'index', $th->getMessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        return Inertia::render('RessourcesLogistique::SortiesLivres/Create', $this->options());
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->rules());
            $validated['etat'] = $validated['etat'] ?? 'actif';
            SortieLivre::create($validated);

            return redirect()->route('sorties-livres.index')->with('success', 'Sortie enregistrée avec succès');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error('SortieLivre', 'store', $th->getMessage());
            return back()->with('error', 'Erreur lors de l\'enregistrement : ' . $th->getMessage())->withInput();
        }
    }

    public function show(SortieLivre $sorties_livre)
    {
        return Inertia::render('RessourcesLogistique::SortiesLivres/Show', array_merge(
            $this->options(),
            ['sortie' => $sorties_livre->load(['bibliothequeStructure', 'ouvrage'])]
        ));
    }

    public function edit(SortieLivre $sorties_livre)
    {
        return Inertia::render('RessourcesLogistique::SortiesLivres/Edit', array_merge(
            $this->options(),
            ['sortie' => $sorties_livre->load(['bibliothequeStructure', 'ouvrage'])]
        ));
    }

    public function update(Request $request, SortieLivre $sorties_livre)
    {
        try {
            $validated = $request->validate($this->rules());
            $sorties_livre->update($validated);

            return redirect()->route('sorties-livres.index')->with('success', 'Sortie modifiée avec succès');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error('SortieLivre', 'update', $th->getMessage());
            return back()->with('error', 'Erreur lors de la modification : ' . $th->getMessage())->withInput();
        }
    }

    public function destroy(SortieLivre $sorties_livre)
    {
        try {
            $sorties_livre->delete();
            return redirect()->route('sorties-livres.index')->with('success', 'Sortie supprimée');
        } catch (\Throwable $th) {
            log_error('SortieLivre', 'destroy', $th->getMessage());
            return back()->with('error', 'Erreur lors de la suppression');
        }
    }
}
