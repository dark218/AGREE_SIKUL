<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academique\Entities\SortieLivre;
use Modules\Academique\Entities\Bibliotheque;
use Modules\Academique\Entities\BibliothequeStructure;

class SortieLivreController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:sorties-livres-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:sorties-livres-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:sorties-livres-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:sorties-livres-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = SortieLivre::with(['livre', 'structure']);

            if ($request->filled('type_sortie')) {
                $query->where('type_sortie', $request->input('type_sortie'));
            }
            if ($request->filled('bibliotheque_structure_id')) {
                $query->where('bibliotheque_structure_id', $request->input('bibliotheque_structure_id'));
            }
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('tiers', 'like', "%$search%")
                        ->orWhereHas('livre', fn ($b) => $b->where('titre_manuel', 'like', "%$search%"));
                });
            }

            $sorties = $query->orderByDesc('date_sortie')->paginate(10)->withQueryString();

            return Inertia::render('Academique::SortiesLivres/Index', array_merge($this->lookups(), [
                'sorties' => $sorties,
                'filters' => $request->only(['search', 'type_sortie', 'bibliotheque_structure_id']),
            ]));
        } catch (\Throwable $th) {
            log_error('Academique', 'SortieLivreController::index', $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        return Inertia::render('Academique::SortiesLivres/Create', $this->lookups());
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->rules());
            $validated['creation_username'] = auth()->user()->name ?? 'system';
            SortieLivre::create($validated);

            return redirect()->route('academique.sorties-livres.index')
                ->with('success', __('messages.created_successfully'));
        } catch (\Throwable $th) {
            log_error('Academique', 'SortieLivreController::store', $th->getMessage());
            return back()->withErrors(['error' => $th->getMessage()])->withInput();
        }
    }

    public function show(SortieLivre $sortiesLivre)
    {
        $sortiesLivre->load(['livre', 'structure']);
        return Inertia::render('Academique::SortiesLivres/Show', array_merge($this->lookups(), [
            'sortie' => $sortiesLivre,
        ]));
    }

    public function edit(SortieLivre $sortiesLivre)
    {
        $sortiesLivre->load(['livre', 'structure']);
        return Inertia::render('Academique::SortiesLivres/Edit', array_merge($this->lookups(), [
            'sortie' => $sortiesLivre,
        ]));
    }

    public function update(Request $request, SortieLivre $sortiesLivre)
    {
        try {
            $validated = $request->validate($this->rules());
            $validated['modification_username'] = auth()->user()->name ?? 'system';
            $sortiesLivre->update($validated);

            return redirect()->route('academique.sorties-livres.index')
                ->with('success', __('messages.updated_successfully'));
        } catch (\Throwable $th) {
            log_error('Academique', 'SortieLivreController::update', $th->getMessage());
            return back()->withErrors(['error' => $th->getMessage()])->withInput();
        }
    }

    public function destroy(SortieLivre $sortiesLivre)
    {
        try {
            $sortiesLivre->delete();
            return back()->with('success', __('messages.deleted_successfully'));
        } catch (\Throwable $th) {
            log_error('Academique', 'SortieLivreController::destroy', $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    private function lookups(): array
    {
        return [
            'livres' => Bibliotheque::where('etat', 'actif')
                ->orderBy('titre_manuel')
                ->get(['id', 'titre_manuel', 'sujet', 'langue', 'auteurs', 'editeurs', 'annee_edition']),
            'structures' => BibliothequeStructure::where('etat', 'actif')
                ->orderBy('libelle')
                ->get(['id', 'libelle']),
        ];
    }

    private function rules(): array
    {
        return [
            'bibliotheque_id' => 'nullable|exists:bibliotheques,id',
            'bibliotheque_structure_id' => 'nullable|exists:bibliotheque_structures,id',
            'type_sortie' => 'required|in:pret,vente,don',
            'date_sortie' => 'nullable|date',
            'quantite' => 'required|integer|min:1',
            'date_retour' => 'nullable|date',
            'tiers' => 'nullable|string|max:255',
            'etat_physique' => 'nullable|string|max:100',
            'etat' => 'required|in:actif,inactif',
        ];
    }
}
