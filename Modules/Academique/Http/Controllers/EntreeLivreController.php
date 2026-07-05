<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academique\Entities\EntreeLivre;
use Modules\Academique\Entities\Bibliotheque;
use Modules\Academique\Entities\BibliothequeStructure;

class EntreeLivreController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:entrees-livres-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:entrees-livres-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:entrees-livres-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:entrees-livres-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = EntreeLivre::with(['livre', 'structure']);

            if ($request->filled('type_entree')) {
                $query->where('type_entree', $request->input('type_entree'));
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

            $entrees = $query->orderByDesc('date_entree')->paginate(10)->withQueryString();

            return Inertia::render('Academique::EntreesLivres/Index', array_merge($this->lookups(), [
                'entrees' => $entrees,
                'filters' => $request->only(['search', 'type_entree', 'bibliotheque_structure_id']),
            ]));
        } catch (\Throwable $th) {
            log_error('Academique', 'EntreeLivreController::index', $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        return Inertia::render('Academique::EntreesLivres/Create', $this->lookups());
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->rules());
            $validated['creation_username'] = auth()->user()->name ?? 'system';
            EntreeLivre::create($validated);

            return redirect()->route('academique.entrees-livres.index')
                ->with('success', __('messages.created_successfully'));
        } catch (\Throwable $th) {
            log_error('Academique', 'EntreeLivreController::store', $th->getMessage());
            return back()->withErrors(['error' => $th->getMessage()])->withInput();
        }
    }

    public function show(EntreeLivre $entreesLivre)
    {
        $entreesLivre->load(['livre', 'structure']);
        return Inertia::render('Academique::EntreesLivres/Show', array_merge($this->lookups(), [
            'entree' => $entreesLivre,
        ]));
    }

    public function edit(EntreeLivre $entreesLivre)
    {
        $entreesLivre->load(['livre', 'structure']);
        return Inertia::render('Academique::EntreesLivres/Edit', array_merge($this->lookups(), [
            'entree' => $entreesLivre,
        ]));
    }

    public function update(Request $request, EntreeLivre $entreesLivre)
    {
        try {
            $validated = $request->validate($this->rules());
            $validated['modification_username'] = auth()->user()->name ?? 'system';
            $entreesLivre->update($validated);

            return redirect()->route('academique.entrees-livres.index')
                ->with('success', __('messages.updated_successfully'));
        } catch (\Throwable $th) {
            log_error('Academique', 'EntreeLivreController::update', $th->getMessage());
            return back()->withErrors(['error' => $th->getMessage()])->withInput();
        }
    }

    public function destroy(EntreeLivre $entreesLivre)
    {
        try {
            $entreesLivre->delete();
            return back()->with('success', __('messages.deleted_successfully'));
        } catch (\Throwable $th) {
            log_error('Academique', 'EntreeLivreController::destroy', $th->getMessage());
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
            'type_entree' => 'required|in:emprunt,achat,don',
            'date_entree' => 'nullable|date',
            'quantite' => 'required|integer|min:1',
            'date_retour' => 'nullable|date',
            'tiers' => 'nullable|string|max:255',
            'etat_physique' => 'nullable|string|max:100',
            'etat' => 'required|in:actif,inactif',
        ];
    }
}
