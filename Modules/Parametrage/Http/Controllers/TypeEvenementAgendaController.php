<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\TypeEvenementAgenda;
use Illuminate\Foundation\Validation\ValidatesRequests;

class TypeEvenementAgendaController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-typeevenementagenda-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-typeevenementagenda-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-typeevenementagenda-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-typeevenementagenda-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-typeevenementagenda-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = TypeEvenementAgenda::query();

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $typeEvenementAgendas = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::TypesÉvenement/Index', [
                'title' => 'Types d\'Évènements',
                'typeEvenementAgendas' => $typeEvenementAgendas,
                'filters' => $request->only(['code', 'libelle', 'etat']),
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Parametrage::TypesÉvenement/Create', [
                'title' => 'Créer un Type d\'Évènement',
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function store(Request $request)
    {
        try {
            \Log::info('🔍 TypeEvenement Store - Request Data:', ['data' => $request->all()]);

            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:type_evenements_agenda,code',
                'libelle' => 'required|string|max:255',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            \Log::info('✅ TypeEvenement Validation Passed:', ['validated' => $validated]);

            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['created_by'] = auth()->id();

            \Log::info('📝 TypeEvenement Before Create:', ['data' => $validated]);

            $record = TypeEvenementAgenda::create($validated);

            \Log::info('✅ TypeEvenement Created Successfully:', ['id' => $record->id, 'code' => $record->code]);

            return redirect()
                ->route('parametrage.types_evenement.index')
                ->with('success', 'Créé avec succès');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('❌ TypeEvenement Validation Error:', ['errors' => $e->errors()]);
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('❌ TypeEvenement Create Error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            // Logging handled by exception handler
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function show(TypeEvenementAgenda $typeEvenementAgenda)
    {
        try {
            return Inertia::render('Parametrage::TypesÉvenement/Show', [
                'title' => 'Détails Type d\'Évènement',
                'item' => $typeEvenementAgenda,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(TypeEvenementAgenda $typeEvenementAgenda)
    {
        try {
            return Inertia::render('Parametrage::TypesÉvenement/Edit', [
                'title' => 'Modifier Type d\'Évènement',
                'item' => $typeEvenementAgenda,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function update(Request $request, TypeEvenementAgenda $typeEvenementAgenda)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:type_evenements_agenda,code,' . $typeEvenementAgenda->id,
                'libelle' => 'required|string|max:255',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? $typeEvenementAgenda->etat;
            $validated['updated_by'] = auth()->id();
            $typeEvenementAgenda->update($validated);

            return redirect()
                ->route('parametrage.types_evenement.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(TypeEvenementAgenda $typeEvenementAgenda)
    {
        try {
            $typeEvenementAgenda->deleted_by = auth()->id();
            $typeEvenementAgenda->save();
            $typeEvenementAgenda->delete();

            return redirect()->route('parametrage.types_evenement.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.types_evenement.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(TypeEvenementAgenda $typeEvenementAgenda)
    {
        try {
            $newEtat = $typeEvenementAgenda->etat === 'actif' ? 'inactif' : 'actif';
            $typeEvenementAgenda->etat = $newEtat;
            $typeEvenementAgenda->updated_by = auth()->id();
            $typeEvenementAgenda->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.types_evenement.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.types_evenement.index')->with('error', 'Erreur lors du changement de statut');
        }
    }
}
