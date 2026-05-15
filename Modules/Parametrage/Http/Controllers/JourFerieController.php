<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\JourFerie;
use Modules\Parametrage\Entities\Pays;
use Illuminate\Foundation\Validation\ValidatesRequests;

class JourFerieController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-jourferie-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-jourferie-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-jourferie-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-jourferie-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-jourferie-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = JourFerie::query()->with('pays');

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            if ($request->filled('jour')) {
                $query->where('jour', $request->jour);
            }

            if ($request->filled('mois')) {
                $query->where('mois', $request->mois);
            }

            if ($request->filled('annee')) {
                $query->where('annee', $request->annee);
            }

            if ($request->filled('date')) {
                $query->where('date', 'like', '%' . $request->date . '%');
            }

            if ($request->filled('pays_id')) {
                $query->where('pays_id', $request->pays_id);
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $jourFeries = $query->paginate(10)->withQueryString();

            $pays = Pays::all()->map(function($country) {
                return ['id' => $country->id, 'libelle' => $country->libelle];
            });

            return Inertia::render('Parametrage::JoursFeries/Index', [
                'jours_feries' => $jourFeries,
                'filters' => $request->only(['code', 'libelle', 'jour', 'mois', 'annee', 'date', 'pays_id', 'etat']),
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        try {
            $pays = Pays::all()->map(function($country) {
                return ['id' => $country->id, 'libelle' => $country->libelle];
            });
            return Inertia::render('Parametrage::JoursFeries/Create', [
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:100|unique:jours_feries,code',
            'libelle' => 'required|string|max:255',
            'jour' => 'nullable|integer|min:1|max:31',
            'mois' => 'nullable|integer|min:1|max:12',
            'annee' => 'nullable|integer|min:1900',
            'date' => 'nullable|date',
            'pays_id' => 'nullable|exists:pays,id',
            'etat' => 'nullable|in:actif,inactif',
        ]);

        try {
            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['created_by'] = auth()->id();

            // Auto-calcul jour/mois/annee depuis la date si fournie (cohérence avec form simplifié)
            if (!empty($validated['date'])) {
                $d = \Carbon\Carbon::parse($validated['date']);
                $validated['jour'] = $validated['jour'] ?? $d->day;
                $validated['mois'] = $validated['mois'] ?? $d->month;
                $validated['annee'] = $validated['annee'] ?? $d->year;
            }

            JourFerie::create($validated);

            return redirect()
                ->route('parametrage.jours_feries.index')
                ->with('success', 'Créé avec succès');
        } catch (\Exception $e) {
            \Log::error('JourFerieController@store: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la création: ' . $e->getMessage());
        }
    }

    public function show(JourFerie $jourFerie)
    {
        try {
            $pays = Pays::all()->map(function($country) {
                return ['id' => $country->id, 'libelle' => $country->libelle];
            });
            return Inertia::render('Parametrage::JoursFeries/Show', [
                'jourFerie' => $jourFerie->load(['pays']),
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(JourFerie $jourFerie)
    {
        try {
            $pays = Pays::all()->map(function($country) {
                return ['id' => $country->id, 'libelle' => $country->libelle];
            });
            return Inertia::render('Parametrage::JoursFeries/Edit', [
                'item' => $jourFerie->load(['pays']),
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function update(Request $request, JourFerie $jourFerie)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:100|unique:jours_feries,code,' . $jourFerie->id,
            'libelle' => 'required|string|max:255',
            'jour' => 'nullable|integer|min:1|max:31',
            'mois' => 'nullable|integer|min:1|max:12',
            'annee' => 'nullable|integer|min:1900',
            'date' => 'nullable|date',
            'pays_id' => 'nullable|exists:pays,id',
            'etat' => 'nullable|in:actif,inactif',
        ]);

        try {
            $validated['etat'] = $validated['etat'] ?? $jourFerie->etat;
            $validated['updated_by'] = auth()->id();
            $jourFerie->update($validated);

            return redirect()
                ->route('parametrage.jours_feries.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(JourFerie $jourFerie)
    {
        try {
            $jourFerie->deleted_by = auth()->id();
            $jourFerie->save();
            $jourFerie->delete();

            return redirect()->route('parametrage.jours_feries.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.jours_feries.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(JourFerie $jourFerie)
    {
        try {
            $newEtat = $jourFerie->etat === 'actif' ? 'inactif' : 'actif';
            $jourFerie->etat = $newEtat;
            $jourFerie->updated_by = auth()->id();
            $jourFerie->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.jours_feries.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.jours_feries.index')->with('error', 'Erreur lors du changement de statut');
        }
    }
}
