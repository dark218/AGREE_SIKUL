<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Academique\Entities\Bibliotheque;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BibliothequeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:bibliotheques-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:bibliotheques-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:bibliotheques-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:bibliotheques-delete', ['only' => ['destroy', 'statut']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Bibliotheque::query();

            // Search filter
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('titre_manuel', 'like', "%$search%")
                        ->orWhere('auteurs', 'like', "%$search%")
                        ->orWhere('sujet', 'like', "%$search%");
                });
            }

            // Sujet filter
            if ($request->filled('sujet')) {
                $query->where('sujet', $request->input('sujet'));
            }

            // Type manuel filter
            if ($request->filled('type_manuel')) {
                $query->where('type_manuel', $request->input('type_manuel'));
            }

            // Etat filter
            if ($request->filled('etat')) {
                $query->where('etat', $request->input('etat'));
            }

            $bibliotheques = $query->paginate(10)
                ->withQueryString()
                ->through(fn ($item) => [
                    'id' => $item->id,
                    'titre_manuel' => $item->titre_manuel,
                    'sujet' => $item->sujet,
                    'type_manuel' => $item->type_manuel,
                    'auteurs' => $item->auteurs,
                    'quantite' => $item->quantite,
                    'disponibles' => $item->disponibles,
                    'etat' => $item->etat,
                ]);

            return Inertia::render('Academique::Bibliotheques/Index', [
                'bibliotheques' => $bibliotheques,
                'filters' => $request->only(['search', 'sujet', 'type_manuel', 'etat']),
            ]);
        } catch (\Throwable $th) {
            log_error('Academique', 'BibliothequeController::index', $th->getMessage());
            return back()->withErrors(['error' => 'Erreur lors du chargement de la liste']);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            $niveaux = \Modules\Parametrage\Entities\Niveau::select('id', 'libelle')
                ->whereNull('deleted_at')
                ->orderBy('libelle')
                ->get();

            return Inertia::render('Academique::Bibliotheques/Create', [
                'niveaux' => $niveaux,
            ]);
        } catch (\Throwable $th) {
            log_error('Academique', 'BibliothequeController::create', $th->getMessage());
            return back()->withErrors(['error' => 'Erreur lors de l\'accès au formulaire: ' . $th->getMessage()]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'sujet' => 'nullable|string|max:255',
                'langue' => 'nullable|string|max:255',
                'niveau_id' => 'nullable|exists:niveaux,id',
                'type_manuel' => 'nullable|string|max:255',
                'titre_manuel' => 'nullable|string|max:255',
                'auteurs' => 'nullable|string|max:500',
                'editeurs' => 'nullable|string|max:255',
                'annee_edition' => 'nullable|integer|min:1900|max:' . (date('Y') + 10),
                'quantite' => 'nullable|integer|min:0',
                'sorties' => 'nullable|integer|min:0',
                'disponibles' => 'nullable|integer|min:0',
                'etat' => 'required|in:actif,inactif',
            ]);

            \Log::info('Validation passed, validated data:', $validated);

            $validated['creation_username'] = auth()->user()->name;
            $validated['modification_username'] = auth()->user()->name;

            \Log::info('Creating bibliotheque with:', $validated);

            $created = Bibliotheque::create($validated);

            \Log::info('Bibliotheque created successfully:', ['id' => $created->id]);

            return redirect()->route('academique.bibliotheques.index')
                ->with('success', 'Manuel de bibliothèque ajouté avec succès');
        } catch (\Throwable $th) {
            \Log::error('=== BIBLIOTHEQUE STORE ERROR ===');
            \Log::error('Error message: ' . $th->getMessage());
            \Log::error('Error file: ' . $th->getFile() . ':' . $th->getLine());
            \Log::error('Stack trace: ' . $th->getTraceAsString());

            log_error('Academique', 'BibliothequeController::store', $th->getMessage());
            return back()->withErrors(['error' => $th->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Bibliotheque $bibliotheque)
    {
        try {
            $niveaux = \Modules\Parametrage\Entities\Niveau::select('id', 'libelle')
                ->whereNull('deleted_at')
                ->orderBy('libelle')
                ->get();

            return Inertia::render('Academique::Bibliotheques/Show', [
                'item' => $bibliotheque,
                'niveaux' => $niveaux,
            ]);
        } catch (\Throwable $th) {
            log_error('Academique', 'BibliothequeController::show', $th->getMessage());
            return back()->withErrors(['error' => 'Erreur lors du chargement']);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bibliotheque $bibliotheque)
    {
        try {
            $niveaux = \Modules\Parametrage\Entities\Niveau::select('id', 'libelle')
                ->whereNull('deleted_at')
                ->orderBy('libelle')
                ->get();

            return Inertia::render('Academique::Bibliotheques/Edit', [
                'item' => $bibliotheque,
                'niveaux' => $niveaux,
            ]);
        } catch (\Throwable $th) {
            log_error('Academique', 'BibliothequeController::edit', $th->getMessage());
            return back()->withErrors(['error' => 'Erreur lors de l\'accès au formulaire']);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bibliotheque $bibliotheque)
    {
        try {
            $validated = $request->validate([
                'sujet' => 'nullable|string|max:255',
                'langue' => 'nullable|string|max:255',
                'niveau_id' => 'nullable|exists:niveaux,id',
                'type_manuel' => 'nullable|string|max:255',
                'titre_manuel' => 'nullable|string|max:255',
                'auteurs' => 'nullable|string|max:500',
                'editeurs' => 'nullable|string|max:255',
                'annee_edition' => 'nullable|integer|min:1900|max:' . (date('Y') + 10),
                'quantite' => 'nullable|integer|min:0',
                'sorties' => 'nullable|integer|min:0',
                'disponibles' => 'nullable|integer|min:0',
                'etat' => 'required|in:actif,inactif',
            ]);

            $validated['modification_username'] = auth()->user()->name;

            $bibliotheque->update($validated);

            return redirect()->route('academique.bibliotheques.index')
                ->with('success', 'Manuel de bibliothèque modifié avec succès');
        } catch (\Throwable $th) {
            log_error('Academique', 'BibliothequeController::update', $th->getMessage());
            return back()->withErrors(['error' => $th->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bibliotheque $bibliotheque)
    {
        try {
            $bibliotheque->delete();

            return redirect()->route('academique.bibliotheques.index')
                ->with('success', 'Manuel de bibliothèque supprimé avec succès');
        } catch (\Throwable $th) {
            log_error('Academique', 'BibliothequeController::destroy', $th->getMessage());
            return back()->withErrors(['error' => $th->getMessage()]);
        }
    }

    /**
     * Toggle the statut of the resource.
     */
    public function statut(Request $request, Bibliotheque $bibliotheque)
    {
        try {
            $bibliotheque->etat = $bibliotheque->etat === 'actif' ? 'inactif' : 'actif';
            $bibliotheque->modification_username = auth()->user()->name;
            $bibliotheque->save();

            return redirect()->route('academique.bibliotheques.index')
                ->with('success', 'Statut du manuel mis à jour avec succès');
        } catch (\Throwable $th) {
            log_error('Academique', 'BibliothequeController::statut', $th->getMessage());
            return back()->withErrors(['error' => $th->getMessage()]);
        }
    }
}
