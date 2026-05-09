<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\DevisePays;
use Modules\Parametrage\Entities\Pays;
use Modules\Parametrage\Entities\Devises;
use Illuminate\Foundation\Validation\ValidatesRequests;

class DevisePaysController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-devisepays-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:parametrage-devisepays-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-devisepays-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-devisepays-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-devisepays-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = DevisePays::query()->with(['pays', 'devise']);

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $devisesPays = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::DevisesPays/Index', [
                'devisesPays' => $devisesPays,
                'filters' => $request->only(['code', 'etat']),
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            $pays = Pays::all()->map(fn($p) => ['id' => $p->id, 'libelle' => $p->libelle]);
            $devises = Devises::all()->map(fn($d) => ['id' => $d->id, 'libelle' => $d->libelle]);

            return Inertia::render('Parametrage::DevisesPays/Create', [
                'pays' => $pays,
                'devises' => $devises,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:devises_pays,code',
                'pays_id' => 'required|exists:pays,id',
                'devise_id' => 'required|exists:devises,id',
                'taux_change' => 'nullable|numeric|min:0',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['created_by'] = auth()->id();

            DevisePays::create($validated);

            return redirect()->route('parametrage.devises_pays.index')->with('success', 'Créé avec succès');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function show(DevisePays $devisespay)
    {
        return Inertia::render('Parametrage::DevisesPays/Show', ['devisespay' => $devisespay->load(['pays', 'devise'])]);
    }

    public function edit(DevisePays $devisespay)
    {
        try {
            $pays = Pays::all()->map(fn($p) => ['id' => $p->id, 'libelle' => $p->libelle]);
            $devises = Devises::all()->map(fn($d) => ['id' => $d->id, 'libelle' => $d->libelle]);

            return Inertia::render('Parametrage::DevisesPays/Edit', [
                'item' => $devisespay->load(['pays', 'devise']),
                'pays' => $pays,
                'devises' => $devises,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function update(Request $request, DevisePays $devisespay)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:devises_pays,code,' . $devisespay->id,
                'pays_id' => 'required|exists:pays,id',
                'devise_id' => 'required|exists:devises,id',
                'taux_change' => 'nullable|numeric|min:0',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['updated_by'] = auth()->id();
            $devisespay->update($validated);

            return redirect()->route('parametrage.devises_pays.index')->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function destroy(DevisePays $devisespay)
    {
        try {
            $devisespay->deleted_by = auth()->id();
            $devisespay->save();
            $devisespay->delete();

            return redirect()->route('parametrage.devises_pays.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            return redirect()->route('parametrage.devises_pays.index')->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function activate(DevisePays $devisespay)
    {
        try {
            $newEtat = $devisespay->etat === 'actif' ? 'inactif' : 'actif';
            $devisespay->etat = $newEtat;
            $devisespay->updated_by = auth()->id();
            $devisespay->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.devises_pays.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            return redirect()->route('parametrage.devises_pays.index')->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
}
