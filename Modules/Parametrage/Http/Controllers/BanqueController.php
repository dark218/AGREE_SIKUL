<?php

namespace Modules\Parametrage\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Parametrage\Entities\Banque;
use Modules\Parametrage\Entities\Pays;

class BanqueController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:banque-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:banque-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:banque-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:banque-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:banque-activate', ['only' => ['activate']]);
    }

    /**
     * Display a listing of banques.
     */
    public function index(Request $request): Response
    {
        try {
            $query = Banque::with('pays');

            if ($request->filled('search')) {
                $query->where('code', 'like', '%' . $request->search . '%')
                    ->orWhere('libelle', 'like', '%' . $request->search . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $banques = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::Banques/Index', [
                'banques' => $banques,
                'filters' => $request->only(['search', 'etat']),
            ]);
        } catch (\Throwable $th) {
            \Log::error("Error");
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    /**
     * Show the form for creating a new banque.
     */
    public function create(): Response
    {
        try {
            $pays = Pays::all()->map(function($country) {
                return ['id' => $country->id, 'libelle' => $country->libelle];
            });

            return Inertia::render('Parametrage::Banques/Create', [
                'pays' => $pays,
            ]);
        } catch (\Throwable $th) {
            \Log::error("Error");
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    /**
     * Store a newly created banque.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:100|unique:banques,code',
            'libelle' => 'required|string|max:255',
            'pays_id' => 'required|exists:pays,id',
            'etat' => 'nullable|in:actif,inactif',
        ]);

        try {
            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['created_by'] = auth()->id();
            Banque::create($validated);

            return redirect()
                ->route('parametrage.banques.index')
                ->with('success', 'Créé avec succès');
        } catch (\Throwable $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la création');
        }
    }

    /**
     * Display the specified banque.
     */
    public function show(Banque $banque): Response
    {
        try {
            return Inertia::render('Parametrage::Banques/Show', [
                'banque' => $banque->load('pays'),
            ]);
        } catch (\Throwable $th) {
            \Log::error("Error");
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    /**
     * Show the form for editing the specified banque.
     */
    public function edit(Banque $banque): Response
    {
        try {
            $pays = Pays::all()->map(function($country) {
                return ['id' => $country->id, 'libelle' => $country->libelle];
            });

            return Inertia::render('Parametrage::Banques/Edit', [
                'banque' => $banque->load('pays'),
                'pays' => $pays,
            ]);
        } catch (\Throwable $th) {
            \Log::error("Error");
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    /**
     * Update the specified banque.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:100|unique:banques,code,' . $id,
            'libelle' => 'required|string|max:255',
            'pays_id' => 'required|exists:pays,id',
            'etat' => 'nullable|in:actif,inactif',
        ]);

        try {
            $banque = Banque::findOrFail($id);
            $validated['etat'] = $validated['etat'] ?? $banque->etat;
            $validated['updated_by'] = auth()->id();
            $banque->update($validated);

            return redirect()
                ->route('parametrage.banques.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Throwable $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    /**
     * Activate/Deactivate banque (soft delete/restore).
     */
    public function activate(Banque $banque)
    {
        try {
            $newEtat = $banque->etat === 'actif' ? 'inactif' : 'actif';
            $banque->etat = $newEtat;
            $banque->updated_by = auth()->id();
            $banque->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.banques.index')->with('success', $message . ' avec succès');
        } catch (\Throwable $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.banques.index')->with('error', 'Erreur lors du changement de statut');
        }
    }

    /**
     * Permanently delete a banque.
     */
    public function destroy($id)
    {
        try {
            $banque = Banque::withTrashed()->findOrFail($id);
            $banque->forceDelete();

            return redirect()->route('parametrage.banques.index')->with('success', 'Supprimé avec succès');
        } catch (\Throwable $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.banques.index')->with('error', 'Erreur lors de la suppression');
        }
    }
}