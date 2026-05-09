<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\MoyenPaiement;
use Modules\Parametrage\Entities\FournisseurPaiement;
use Illuminate\Foundation\Validation\ValidatesRequests;

class MoyensPaiementController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-moyenspaiement-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-moyenspaiement-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-moyenspaiement-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-moyenspaiement-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-moyenspaiement-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = MoyenPaiement::query();

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $moyensPaiements = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::MoyensPaiement/Index', [
                'moyens_paiement' => $moyensPaiements,
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
            $fournisseurs = FournisseurPaiement::select('id', 'nom')->orderBy('nom')->get()->toArray();
            return Inertia::render('Parametrage::MoyensPaiement/Create', [
                'fournisseurs' => $fournisseurs,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:moyens_paiement,code',
                'libelle' => 'required|string|max:255',
                'type_mode' => 'nullable|string|max:100',
                'necessite_reference' => 'nullable|boolean',
                'delai_compensation_jours' => 'nullable|integer|min:0',
                'frais_pourcentage' => 'nullable|numeric|min:0|max:100',
                'frais_fixe' => 'nullable|numeric|min:0',
                'montant_min' => 'nullable|numeric|min:0',
                'montant_max' => 'nullable|numeric|min:0',
                'fournisseur_paiement_id' => 'nullable|exists:fournisseur_paiements,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['necessite_reference'] = $validated['necessite_reference'] ?? false;
            $validated['created_by'] = auth()->id();
            MoyenPaiement::create($validated);

            return redirect()
                ->route('parametrage.moyens_paiement.index')
                ->with('success', 'Créé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la création');
        }
    }

    public function show(MoyenPaiement $modesPaiement)
    {
        try {
            $fournisseurs = FournisseurPaiement::select('id', 'nom')->orderBy('nom')->get()->toArray();
            return Inertia::render('Parametrage::MoyensPaiement/Show', [
                'modesPaiement' => $modesPaiement,
                'fournisseurs' => $fournisseurs,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(MoyenPaiement $modesPaiement)
    {
        try {
            $fournisseurs = FournisseurPaiement::select('id', 'nom')->orderBy('nom')->get()->toArray();
            return Inertia::render('Parametrage::MoyensPaiement/Edit', [
                'item' => $modesPaiement,
                'fournisseurs' => $fournisseurs,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function update(Request $request, MoyenPaiement $modesPaiement)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:moyens_paiement,code,' . $modesPaiement->id,
                'libelle' => 'required|string|max:255',
                'type_mode' => 'nullable|string|max:100',
                'necessite_reference' => 'nullable|boolean',
                'delai_compensation_jours' => 'nullable|integer|min:0',
                'frais_pourcentage' => 'nullable|numeric|min:0|max:100',
                'frais_fixe' => 'nullable|numeric|min:0',
                'montant_min' => 'nullable|numeric|min:0',
                'montant_max' => 'nullable|numeric|min:0',
                'fournisseur_paiement_id' => 'nullable|exists:fournisseur_paiements,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? $modesPaiement->etat;
            $validated['necessite_reference'] = $validated['necessite_reference'] ?? false;
            $validated['updated_by'] = auth()->id();
            $modesPaiement->update($validated);

            return redirect()
                ->route('parametrage.moyens_paiement.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(MoyenPaiement $modesPaiement)
    {
        try {
            $modesPaiement->deleted_by = auth()->id();
            $modesPaiement->save();
            $modesPaiement->delete();

            return redirect()->route('parametrage.moyens_paiement.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.moyens_paiement.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(MoyenPaiement $modesPaiement)
    {
        try {
            \Log::debug('🔍 DEBUG: activate() called for modesPaiement ID: ' . $modesPaiement->id);
            \Log::debug('🔍 Current etat: ' . $modesPaiement->etat);

            $newEtat = $modesPaiement->etat === 'actif' ? 'inactif' : 'actif';
            $modesPaiement->etat = $newEtat;
            $modesPaiement->updated_by = auth()->id() ?? 1;

            \Log::debug('🔍 New etat: ' . $newEtat);
            \Log::debug('🔍 Updated by: ' . ($modesPaiement->updated_by ?? 'null'));

            $modesPaiement->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            \Log::info('✅ Mode paiement ' . $message . ': ' . $modesPaiement->id);
            return redirect()->route('parametrage.moyens_paiement.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            \Log::error('❌ Erreur dans activate(): ' . $e->getMessage());
            \Log::error('❌ Stack trace: ' . $e->getTraceAsString());
            return redirect()->route('parametrage.moyens_paiement.index')->with('error', 'Erreur lors du changement de statut: ' . $e->getMessage());
        }
    }
}
