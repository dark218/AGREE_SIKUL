<?php

namespace Modules\Finances\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Finances\Entities\Salaire;
use Illuminate\Foundation\Validation\ValidatesRequests;

class SalaireController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:finances-salaire-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:finances-salaire-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:finances-salaire-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:finances-salaire-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:finances-salaire-activate', ['only' => ['statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Salaire::query();

            if ($request->filled('nom')) {
                $query->where('nom', 'like', '%' . $request->nom . '%');
            }

            if ($request->filled('annee_scolaire_id')) {
                $query->where('annee_scolaire_id', $request->annee_scolaire_id);
            }

            if ($request->filled('ecole_id')) {
                $query->where('ecole_id', $request->ecole_id);
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $salaires = $query->with(['anneeScolaire', 'ecole', 'campus'])
                ->paginate(10)
                ->withQueryString()
                ->through(function ($salaire) {
                    return [
                        'id' => $salaire->id,
                        'nom' => $salaire->nom,
                        'mois_paie' => $salaire->mois_paie,
                        'annee_scolaire_id' => $salaire->annee_scolaire_id,
                        'annee_scolaire' => $salaire->anneeScolaire ? ['id' => $salaire->anneeScolaire->id, 'libelle' => $salaire->anneeScolaire->libelle] : null,
                        'ecole_id' => $salaire->ecole_id,
                        'ecole' => $salaire->ecole ? ['id' => $salaire->ecole->id, 'nom' => $salaire->ecole->nom] : null,
                        'campus_id' => $salaire->campus_id,
                        'campus' => $salaire->campus ? ['id' => $salaire->campus->id, 'nom' => $salaire->campus->nom] : null,
                        'date_paiement_integral' => $salaire->date_paiement_integral ? $salaire->date_paiement_integral->format('Y-m-d') : null,
                        'date_avance1' => $salaire->date_avance1 ? $salaire->date_avance1->format('Y-m-d') : null,
                        'date_avance2' => $salaire->date_avance2 ? $salaire->date_avance2->format('Y-m-d') : null,
                        'date_avance3' => $salaire->date_avance3 ? $salaire->date_avance3->format('Y-m-d') : null,
                        'date_avance4' => $salaire->date_avance4 ? $salaire->date_avance4->format('Y-m-d') : null,
                        'salaire_net' => $salaire->salaire_net,
                        'total_paye' => $salaire->total_paye,
                        'restant_a_payer' => $salaire->restant_a_payer,
                        'etat' => $salaire->etat,
                        'created_at' => $salaire->created_at,
                        'updated_at' => $salaire->updated_at,
                    ];
                });

            $anneesScolaires = \Modules\Parametrage\Entities\AnneeScolaire::where('etat', 'actif')->get();
            $ecoles = \Modules\Parametrage\Entities\Ecole::where('statut', 'actif')->get();

            return Inertia::render('Finances::Salaires/Index', [
                'salaires' => $salaires,
                'anneesScolaires' => $anneesScolaires,
                'ecoles' => $ecoles,
                'filters' => $request->only(['nom', 'annee_scolaire_id', 'ecole_id', 'etat']),
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            $anneesScolaires = \Modules\Parametrage\Entities\AnneeScolaire::where('etat', 'actif')->get();
            $ecoles = \Modules\Parametrage\Entities\Ecole::where('statut', 'actif')->get();
            $campuses = \Modules\Parametrage\Entities\Campus::where('statut', 'actif')->get();

            return Inertia::render('Finances::Salaires/Create', [
                'anneesScolaires' => $anneesScolaires,
                'ecoles' => $ecoles,
                'campuses' => $campuses,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
                'ecole_id' => 'required|exists:ecoles,id',
                'campus_id' => 'nullable|exists:campuses,id',
                'nom' => 'required|string|max:255',
                'institution' => 'nullable|string|max:255',
                'mois_paie' => 'nullable|string|max:50',
                'intitule' => 'nullable|string|max:255',
                'noms' => 'nullable|string|max:255',
                'prenoms' => 'nullable|string|max:255',
                'nom_restituer' => 'nullable|string|max:255',
                'matricule_interne' => 'nullable|string|max:100',
                'matricule_cnps' => 'nullable|string|max:100',
                'numero_identifiant' => 'nullable|string|max:100',
                'salaire_base' => 'nullable|numeric|min:0',
                'primes' => 'nullable|numeric|min:0',
                'indemnites' => 'nullable|numeric|min:0',
                'salaire_brut' => 'nullable|numeric|min:0',
                'retenues_fiscales' => 'nullable|numeric|min:0',
                'retenues_sociales' => 'nullable|numeric|min:0',
                'autres_retenues' => 'nullable|numeric|min:0',
                'saisies_oppositions' => 'nullable|numeric|min:0',
                'salaire_net' => 'nullable|numeric|min:0',
                'paiement_integral' => 'nullable|numeric|min:0',
                'date_paiement_integral' => 'nullable|date',
                'avance1' => 'nullable|numeric|min:0',
                'date_avance1' => 'nullable|date',
                'avance2' => 'nullable|numeric|min:0',
                'date_avance2' => 'nullable|date',
                'avance3' => 'nullable|numeric|min:0',
                'date_avance3' => 'nullable|date',
                'avance4' => 'nullable|numeric|min:0',
                'date_avance4' => 'nullable|date',
                'total_paye' => 'nullable|numeric|min:0',
                'restant_a_payer' => 'nullable|numeric|min:0',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['creation_username'] = auth()->user()->name ?? auth()->id();

            Salaire::create($validated);

            return redirect()->route('finances.salaires.index')->with('success', 'Créé avec succès');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function show(Salaire $salaire)
    {
        try {
            $salaire->load(['anneeScolaire', 'ecole', 'campus']);

            // Convert to array and format dates BEFORE casting interferes
            $item = $salaire->toArray();

            // Cast FK IDs back to int (toArray converts them to strings)
            $item['annee_scolaire_id'] = (int)$item['annee_scolaire_id'];
            $item['ecole_id'] = (int)$item['ecole_id'];
            $item['campus_id'] = (int)$item['campus_id'];

            if ($salaire->date_paiement_integral) {
                $item['date_paiement_integral'] = $salaire->date_paiement_integral->toDateString();
            }
            if ($salaire->date_avance1) {
                $item['date_avance1'] = $salaire->date_avance1->toDateString();
            }
            if ($salaire->date_avance2) {
                $item['date_avance2'] = $salaire->date_avance2->toDateString();
            }
            if ($salaire->date_avance3) {
                $item['date_avance3'] = $salaire->date_avance3->toDateString();
            }
            if ($salaire->date_avance4) {
                $item['date_avance4'] = $salaire->date_avance4->toDateString();
            }

            $anneesScolaires = \Modules\Parametrage\Entities\AnneeScolaire::where('etat', 'actif')->get();
            $ecoles = \Modules\Parametrage\Entities\Ecole::where('statut', 'actif')->get();
            $campuses = \Modules\Parametrage\Entities\Campus::where('statut', 'actif')->get();

            return Inertia::render('Finances::Salaires/Show', [
                'item' => $item,
                'anneesScolaires' => $anneesScolaires,
                'ecoles' => $ecoles,
                'campuses' => $campuses,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function edit(Salaire $salaire)
    {
        try {
            // Convert to array and format dates BEFORE casting interferes
            $item = $salaire->toArray();

            // Cast FK IDs back to int (toArray converts them to strings)
            $item['annee_scolaire_id'] = (int)$item['annee_scolaire_id'];
            $item['ecole_id'] = (int)$item['ecole_id'];
            $item['campus_id'] = (int)$item['campus_id'];

            if ($salaire->date_paiement_integral) {
                $item['date_paiement_integral'] = $salaire->date_paiement_integral->toDateString();
            }
            if ($salaire->date_avance1) {
                $item['date_avance1'] = $salaire->date_avance1->toDateString();
            }
            if ($salaire->date_avance2) {
                $item['date_avance2'] = $salaire->date_avance2->toDateString();
            }
            if ($salaire->date_avance3) {
                $item['date_avance3'] = $salaire->date_avance3->toDateString();
            }
            if ($salaire->date_avance4) {
                $item['date_avance4'] = $salaire->date_avance4->toDateString();
            }

            $anneesScolaires = \Modules\Parametrage\Entities\AnneeScolaire::where('etat', 'actif')->get();
            $ecoles = \Modules\Parametrage\Entities\Ecole::where('statut', 'actif')->get();
            $campuses = \Modules\Parametrage\Entities\Campus::where('statut', 'actif')->get();

            return Inertia::render('Finances::Salaires/Edit', [
                'item' => $item,
                'anneesScolaires' => $anneesScolaires,
                'ecoles' => $ecoles,
                'campuses' => $campuses,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Salaire $salaire)
    {
        try {
            $validated = $request->validate([
                'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
                'ecole_id' => 'required|exists:ecoles,id',
                'campus_id' => 'nullable|exists:campuses,id',
                'nom' => 'required|string|max:255',
                'institution' => 'nullable|string|max:255',
                'mois_paie' => 'nullable|string|max:50',
                'intitule' => 'nullable|string|max:255',
                'noms' => 'nullable|string|max:255',
                'prenoms' => 'nullable|string|max:255',
                'nom_restituer' => 'nullable|string|max:255',
                'matricule_interne' => 'nullable|string|max:100',
                'matricule_cnps' => 'nullable|string|max:100',
                'numero_identifiant' => 'nullable|string|max:100',
                'salaire_base' => 'nullable|numeric|min:0',
                'primes' => 'nullable|numeric|min:0',
                'indemnites' => 'nullable|numeric|min:0',
                'salaire_brut' => 'nullable|numeric|min:0',
                'retenues_fiscales' => 'nullable|numeric|min:0',
                'retenues_sociales' => 'nullable|numeric|min:0',
                'autres_retenues' => 'nullable|numeric|min:0',
                'saisies_oppositions' => 'nullable|numeric|min:0',
                'salaire_net' => 'nullable|numeric|min:0',
                'paiement_integral' => 'nullable|numeric|min:0',
                'date_paiement_integral' => 'nullable|date',
                'avance1' => 'nullable|numeric|min:0',
                'date_avance1' => 'nullable|date',
                'avance2' => 'nullable|numeric|min:0',
                'date_avance2' => 'nullable|date',
                'avance3' => 'nullable|numeric|min:0',
                'date_avance3' => 'nullable|date',
                'avance4' => 'nullable|numeric|min:0',
                'date_avance4' => 'nullable|date',
                'total_paye' => 'nullable|numeric|min:0',
                'restant_a_payer' => 'nullable|numeric|min:0',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['modification_username'] = auth()->user()->name ?? auth()->id();

            $salaire->update($validated);

            return redirect()->route('finances.salaires.index')->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function destroy(Salaire $salaire)
    {
        try {
            $salaire->delete();

            return redirect()->route('finances.salaires.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function statut(Salaire $salaire)
    {
        try {
            $salaire->etat = $salaire->etat === 'actif' ? 'inactif' : 'actif';
            $salaire->modification_username = auth()->user()->name ?? auth()->id();
            $salaire->save();

            return redirect()->route('finances.salaires.index')->with('success', 'Statut modifié avec succès');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
}
