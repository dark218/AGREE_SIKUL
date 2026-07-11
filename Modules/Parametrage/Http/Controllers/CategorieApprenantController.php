<?php

namespace Modules\Parametrage\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Parametrage\Entities\CategorieApprenant;

/**
 * §UX Phase 2 : CRUD minimal du référentiel `categorie_apprenants`.
 * Form : Code (unique), Libellé (obligatoire), Statut (actif/inactif).
 */
class CategorieApprenantController extends Controller
{
    public function index(Request $request)
    {
        $query = CategorieApprenant::query();
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('libelle', 'like', "%$search%")
                  ->orWhere('code', 'like', "%$search%");
            });
        }
        if ($request->filled('etat')) {
            $query->where('etat', $request->input('etat'));
        }

        return Inertia::render('Parametrage::CategoriesApprenants/Index', [
            'items'   => $query->orderBy('libelle')->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'etat']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Parametrage::CategoriesApprenants/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'        => 'nullable|string|max:50|unique:categorie_apprenants,code',
            'libelle'     => 'required|string|max:125|unique:categorie_apprenants,libelle',
            'description' => 'nullable|string|max:500',
            'etat'        => 'nullable|in:actif,inactif',
        ]);
        $validated['etat'] = $validated['etat'] ?? 'actif';
        CategorieApprenant::create($validated);
        return redirect()->route('parametrage.categorie_apprenants.index')
            ->with('success', 'Catégorie apprenant créée avec succès.');
    }

    public function show(CategorieApprenant $categorieApprenant)
    {
        return Inertia::render('Parametrage::CategoriesApprenants/Show', [
            'item' => $categorieApprenant,
        ]);
    }

    public function edit(CategorieApprenant $categorieApprenant)
    {
        return Inertia::render('Parametrage::CategoriesApprenants/Edit', [
            'item' => $categorieApprenant,
        ]);
    }

    public function update(Request $request, CategorieApprenant $categorieApprenant)
    {
        $validated = $request->validate([
            'code'        => 'nullable|string|max:50|unique:categorie_apprenants,code,' . $categorieApprenant->id,
            'libelle'     => 'required|string|max:125|unique:categorie_apprenants,libelle,' . $categorieApprenant->id,
            'description' => 'nullable|string|max:500',
            'etat'        => 'nullable|in:actif,inactif',
        ]);
        $categorieApprenant->update($validated);
        return redirect()->route('parametrage.categorie_apprenants.index')
            ->with('success', 'Catégorie apprenant mise à jour.');
    }

    public function destroy(CategorieApprenant $categorieApprenant)
    {
        $categorieApprenant->delete();
        return back()->with('success', 'Catégorie apprenant supprimée.');
    }

    public function statut(CategorieApprenant $categorieApprenant)
    {
        $categorieApprenant->etat = $categorieApprenant->etat === 'actif' ? 'inactif' : 'actif';
        $categorieApprenant->save();
        return back()->with('success', 'Statut mis à jour.');
    }
}
