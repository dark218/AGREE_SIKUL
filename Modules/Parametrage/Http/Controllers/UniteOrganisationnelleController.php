<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\UniteOrganisationnelle;
use Modules\Parametrage\Entities\Ecole;
use App\Models\User;
use Illuminate\Foundation\Validation\ValidatesRequests;

class UniteOrganisationnelleController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-uniteorganisationnelle-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:parametrage-uniteorganisationnelle-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-uniteorganisationnelle-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-uniteorganisationnelle-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-uniteorganisationnelle-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = UniteOrganisationnelle::query()
                ->orderBy('created_at', 'desc');

            if ($request->filled('search')) {
                $query->where('code', 'like', '%' . $request->search . '%')
                    ->orWhere('libelle', 'like', '%' . $request->search . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $uniteOrganisationnelles = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::UniteOrganisationnelles/Index', [
                'uniteOrganisationnelles' => $uniteOrganisationnelles,
                'filters' => $request->only(['search', 'etat']),
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        try {
            $unites = UniteOrganisationnelle::all()
                ->map(fn($u) => ['id' => $u->id, 'libelle' => $u->libelle])
                ->values();
            $ecoles = Ecole::all(['id', 'nom']);
            $responsables = User::all(['id', 'nom', 'prenoms'])
                ->map(fn($u) => ['id' => $u->id, 'name' => trim($u->nom . ' ' . $u->prenoms)]);

            return Inertia::render('Parametrage::UniteOrganisationnelles/Create', [
                'title' => 'Créer une unité organisationnelle',
                'unites' => $unites,
                'ecoles' => $ecoles,
                'responsables' => $responsables,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in UniteOrganisationnelleController@create: ' . $e->getMessage(), [
                'exception' => $e,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Erreur lors du chargement du formulaire: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:unites_organisationnelles,code',
                'libelle' => 'required|string|max:255',
                'type_unite' => 'nullable|string|max:100',
                'responsable_id' => 'nullable|exists:users,id',
                'budget_annuel' => 'nullable|numeric|min:0',
                'effectif_max' => 'nullable|integer|min:0',
                'niveau_hierarchique' => 'nullable|integer|min:1',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'unite_mere_id' => 'nullable|exists:unites_organisationnelles,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['created_by'] = auth()->id();

            UniteOrganisationnelle::create($validated);

            return redirect()
                ->route('parametrage.unite_organisationnelles.index')
                ->with('success', 'Créé avec succès');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la création: ' . $e->getMessage())->withInput();
        }
    }

    public function show(UniteOrganisationnelle $uniteOrganisationnelle)
    {
        try {
            $unites = UniteOrganisationnelle::all()
                ->map(fn($u) => ['id' => $u->id, 'libelle' => $u->libelle])
                ->values();
            $ecoles = Ecole::all(['id', 'nom']);
            $responsables = User::all(['id', 'nom', 'prenoms'])
                ->map(fn($u) => ['id' => $u->id, 'name' => trim($u->nom . ' ' . $u->prenoms)]);

            return Inertia::render('Parametrage::UniteOrganisationnelles/Show', [
                'title' => 'Détails Unité Organisationnelle',
                'uniteOrganisationnelle' => $uniteOrganisationnelle,
                'unites' => $unites,
                'ecoles' => $ecoles,
                'responsables' => $responsables,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in UniteOrganisationnelleController@show: ' . $e->getMessage(), [
                'exception' => $e,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Erreur lors du chargement: ' . $e->getMessage());
        }
    }

    public function edit(UniteOrganisationnelle $uniteOrganisationnelle)
    {
        try {
            $unites = UniteOrganisationnelle::all()
                ->map(fn($u) => ['id' => $u->id, 'libelle' => $u->libelle])
                ->values();
            $ecoles = Ecole::all(['id', 'nom']);
            $responsables = User::all(['id', 'nom', 'prenoms'])
                ->map(fn($u) => ['id' => $u->id, 'name' => trim($u->nom . ' ' . $u->prenoms)]);

            return Inertia::render('Parametrage::UniteOrganisationnelles/Edit', [
                'title' => 'Modifier Unité Organisationnelle',
                'uniteOrganisationnelle' => $uniteOrganisationnelle,
                'unites' => $unites,
                'ecoles' => $ecoles,
                'responsables' => $responsables,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in UniteOrganisationnelleController@edit: ' . $e->getMessage(), [
                'exception' => $e,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Erreur lors du chargement du formulaire: ' . $e->getMessage());
        }
    }

    public function update(Request $request, UniteOrganisationnelle $uniteOrganisationnelle)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:unites_organisationnelles,code,' . $uniteOrganisationnelle->id,
                'libelle' => 'required|string|max:255',
                'type_unite' => 'nullable|string|max:100',
                'responsable_id' => 'nullable|exists:users,id',
                'budget_annuel' => 'nullable|numeric|min:0',
                'effectif_max' => 'nullable|integer|min:0',
                'niveau_hierarchique' => 'nullable|integer|min:1',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'unite_mere_id' => 'nullable|exists:unites_organisationnelles,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? $uniteOrganisationnelle->etat;
            $validated['updated_by'] = auth()->id();
            $uniteOrganisationnelle->update($validated);

            return redirect()
                ->route('parametrage.unite_organisationnelles.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la modification: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(UniteOrganisationnelle $uniteOrganisationnelle)
    {
        try {
            $uniteOrganisationnelle->deleted_by = auth()->id();
            $uniteOrganisationnelle->save();
            $uniteOrganisationnelle->delete();

            return redirect()->route('parametrage.unite_organisationnelles.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    public function activate(UniteOrganisationnelle $uniteOrganisationnelle)
    {
        try {
            $newEtat = $uniteOrganisationnelle->etat === 'actif' ? 'inactif' : 'actif';
            $uniteOrganisationnelle->etat = $newEtat;
            $uniteOrganisationnelle->updated_by = auth()->id();
            $uniteOrganisationnelle->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.unite_organisationnelles.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors du changement de statut: ' . $e->getMessage());
        }
    }
}
