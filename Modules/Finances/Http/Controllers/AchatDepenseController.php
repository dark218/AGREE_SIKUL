<?php

namespace Modules\Finances\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Finances\Entities\AchatDepense;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\Section;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Campus;

class AchatDepenseController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:finances-achats-depenses-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:finances-achats-depenses-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:finances-achats-depenses-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:finances-achats-depenses-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:finances-achats-depenses-activate', ['only' => ['statut']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = AchatDepense::query();

        // Filters
        if ($request->filled('date_depense')) {
            $query->where('date_depense', $request->date_depense);
        }
        if ($request->filled('nature_depense')) {
            $query->where('nature_depense', 'like', '%' . $request->nature_depense . '%');
        }
        if ($request->filled('ecole_id')) {
            $query->where('ecole_id', $request->ecole_id);
        }
        if ($request->filled('annee_scolaire_id')) {
            $query->where('annee_scolaire_id', $request->annee_scolaire_id);
        }
        if ($request->filled('etat')) {
            $query->where('etat', $request->etat);
        }

        $achatsDepenses = $query->with(['anneeScolaire', 'section', 'ecole', 'campus'])
            ->paginate(10)
            ->withQueryString()
            ->through(function ($item) {
                return [
                    'id' => $item->id,
                    'date_depense' => $item->date_depense ? $item->date_depense->format('Y-m-d') : null,
                    'nature_depense' => $item->nature_depense,
                    'tiers_fournisseur' => $item->tiers_fournisseur,
                    'annee_scolaire_id' => $item->annee_scolaire_id,
                    'annee_scolaire' => $item->anneeScolaire ? ['id' => $item->anneeScolaire->id, 'libelle' => $item->anneeScolaire->libelle] : null,
                    'section_id' => $item->section_id,
                    'section' => $item->section ? ['id' => $item->section->id, 'libelle' => $item->section->libelle] : null,
                    'ecole_id' => $item->ecole_id,
                    'ecole' => $item->ecole ? ['id' => $item->ecole->id, 'nom' => $item->ecole->nom] : null,
                    'campus_id' => $item->campus_id,
                    'campus' => $item->campus ? ['id' => $item->campus->id, 'nom' => $item->campus->nom] : null,
                    'montant' => $item->montant,
                    'montant_total_paye' => $item->montant_total_paye,
                    'restant_a_payer' => $item->restant_a_payer,
                    'etat' => $item->etat,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            });

        // Load reference data
        $anneesScolaires = AnneeScolaire::where('etat', 'actif')->get(['id', 'libelle']);
        $ecoles = Ecole::where('statut', 'actif')->get(['id', 'nom']);

        return Inertia::render('Finances::AchatsDepenses/Index', [
            'achatsDepenses' => $achatsDepenses,
            'anneesScolaires' => $anneesScolaires,
            'ecoles' => $ecoles,
            'filters' => [
                'date_depense' => $request->date_depense ?? '',
                'nature_depense' => $request->nature_depense ?? '',
                'ecole_id' => $request->ecole_id ?? '',
                'annee_scolaire_id' => $request->annee_scolaire_id ?? '',
                'etat' => $request->etat ?? '',
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $anneesScolaires = AnneeScolaire::where('etat', 'actif')->get(['id', 'libelle']);
        $sections = Section::where('etat', 'actif')->get(['id', 'libelle']);
        $ecoles = Ecole::where('statut', 'actif')->get(['id', 'nom']);
        $campuses = Campus::where('statut', 'actif')->get(['id', 'nom']);

        return Inertia::render('Finances::AchatsDepenses/Create', [
            'anneesScolaires' => $anneesScolaires,
            'sections' => $sections,
            'ecoles' => $ecoles,
            'campuses' => $campuses,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
            'ecole_id' => 'required|exists:ecoles,id',
            'section_id' => 'nullable|exists:sections,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'date_depense' => 'date|nullable',
            'nature_depense' => 'string|nullable',
            'tiers_fournisseur' => 'string|nullable',
            'numero_identifiant' => 'string|nullable',
            'type_piece' => 'string|nullable',
            'reference_piece' => 'string|nullable',
            'intitule_operation' => 'string|nullable',
            'montant' => 'numeric|nullable',
            'mode_paiement' => 'string|nullable',
            'date_paiement_1' => 'date|nullable',
            'montant_paiement_1' => 'numeric|nullable',
            'date_paiement_2' => 'date|nullable',
            'montant_paiement_2' => 'numeric|nullable',
            'date_paiement_3' => 'date|nullable',
            'montant_paiement_3' => 'numeric|nullable',
            'date_paiement_4' => 'date|nullable',
            'montant_paiement_4' => 'numeric|nullable',
            'date_paiement_5' => 'date|nullable',
            'montant_paiement_5' => 'numeric|nullable',
            'date_paiement_6' => 'date|nullable',
            'montant_paiement_6' => 'numeric|nullable',
            'montant_total_paye' => 'numeric|nullable',
            'restant_a_payer' => 'numeric|nullable',
            'etat' => 'in:actif,inactif',
        ]);

        $validated['creation_username'] = Auth::user()->name ?? Auth::user()->login;

        AchatDepense::create($validated);

        return redirect()->route('finances.achats-depenses.index')
            ->with('message', trans('common.record_created'));
    }

    /**
     * Display the specified resource.
     */
    public function show($id): Response
    {
        $achatDepense = AchatDepense::findOrFail($id);

        // Convert to array and format dates BEFORE casting interferes
        $item = $achatDepense->toArray();

        // Cast FK IDs back to int (toArray converts them to strings)
        $item['annee_scolaire_id'] = (int)$item['annee_scolaire_id'];
        $item['section_id'] = (int)$item['section_id'];
        $item['ecole_id'] = (int)$item['ecole_id'];
        $item['campus_id'] = (int)$item['campus_id'];

        if ($achatDepense->date_depense) {
            $item['date_depense'] = $achatDepense->date_depense->toDateString();
        }
        if ($achatDepense->date_paiement_1) {
            $item['date_paiement_1'] = $achatDepense->date_paiement_1->toDateString();
        }
        if ($achatDepense->date_paiement_2) {
            $item['date_paiement_2'] = $achatDepense->date_paiement_2->toDateString();
        }
        if ($achatDepense->date_paiement_3) {
            $item['date_paiement_3'] = $achatDepense->date_paiement_3->toDateString();
        }
        if ($achatDepense->date_paiement_4) {
            $item['date_paiement_4'] = $achatDepense->date_paiement_4->toDateString();
        }
        if ($achatDepense->date_paiement_5) {
            $item['date_paiement_5'] = $achatDepense->date_paiement_5->toDateString();
        }
        if ($achatDepense->date_paiement_6) {
            $item['date_paiement_6'] = $achatDepense->date_paiement_6->toDateString();
        }

        $anneesScolaires = AnneeScolaire::where('etat', 'actif')->get(['id', 'libelle']);
        $sections = Section::where('etat', 'actif')->get(['id', 'libelle']);
        $ecoles = Ecole::where('statut', 'actif')->get(['id', 'nom']);
        $campuses = Campus::where('statut', 'actif')->get(['id', 'nom']);

        return Inertia::render('Finances::AchatsDepenses/Show', [
            'item' => $item,
            'anneesScolaires' => $anneesScolaires,
            'sections' => $sections,
            'ecoles' => $ecoles,
            'campuses' => $campuses,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id): Response
    {
        $achatDepense = AchatDepense::findOrFail($id);

        // Convert to array and format dates BEFORE casting interferes
        $item = $achatDepense->toArray();

        // Cast FK IDs back to int (toArray converts them to strings)
        $item['annee_scolaire_id'] = (int)$item['annee_scolaire_id'];
        $item['section_id'] = (int)$item['section_id'];
        $item['ecole_id'] = (int)$item['ecole_id'];
        $item['campus_id'] = (int)$item['campus_id'];

        if ($achatDepense->date_depense) {
            $item['date_depense'] = $achatDepense->date_depense->toDateString();
        }
        if ($achatDepense->date_paiement_1) {
            $item['date_paiement_1'] = $achatDepense->date_paiement_1->toDateString();
        }
        if ($achatDepense->date_paiement_2) {
            $item['date_paiement_2'] = $achatDepense->date_paiement_2->toDateString();
        }
        if ($achatDepense->date_paiement_3) {
            $item['date_paiement_3'] = $achatDepense->date_paiement_3->toDateString();
        }
        if ($achatDepense->date_paiement_4) {
            $item['date_paiement_4'] = $achatDepense->date_paiement_4->toDateString();
        }
        if ($achatDepense->date_paiement_5) {
            $item['date_paiement_5'] = $achatDepense->date_paiement_5->toDateString();
        }
        if ($achatDepense->date_paiement_6) {
            $item['date_paiement_6'] = $achatDepense->date_paiement_6->toDateString();
        }

        $anneesScolaires = AnneeScolaire::where('etat', 'actif')->get(['id', 'libelle']);
        $sections = Section::where('etat', 'actif')->get(['id', 'libelle']);
        $ecoles = Ecole::where('statut', 'actif')->get(['id', 'nom']);
        $campuses = Campus::where('statut', 'actif')->get(['id', 'nom']);

        return Inertia::render('Finances::AchatsDepenses/Edit', [
            'item' => $item,
            'anneesScolaires' => $anneesScolaires,
            'sections' => $sections,
            'ecoles' => $ecoles,
            'campuses' => $campuses,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $item = AchatDepense::findOrFail($id);

        $validated = $request->validate([
            'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
            'ecole_id' => 'required|exists:ecoles,id',
            'section_id' => 'nullable|exists:sections,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'date_depense' => 'date|nullable',
            'nature_depense' => 'string|nullable',
            'tiers_fournisseur' => 'string|nullable',
            'numero_identifiant' => 'string|nullable',
            'type_piece' => 'string|nullable',
            'reference_piece' => 'string|nullable',
            'intitule_operation' => 'string|nullable',
            'montant' => 'numeric|nullable',
            'mode_paiement' => 'string|nullable',
            'date_paiement_1' => 'date|nullable',
            'montant_paiement_1' => 'numeric|nullable',
            'date_paiement_2' => 'date|nullable',
            'montant_paiement_2' => 'numeric|nullable',
            'date_paiement_3' => 'date|nullable',
            'montant_paiement_3' => 'numeric|nullable',
            'date_paiement_4' => 'date|nullable',
            'montant_paiement_4' => 'numeric|nullable',
            'date_paiement_5' => 'date|nullable',
            'montant_paiement_5' => 'numeric|nullable',
            'date_paiement_6' => 'date|nullable',
            'montant_paiement_6' => 'numeric|nullable',
            'montant_total_paye' => 'numeric|nullable',
            'restant_a_payer' => 'numeric|nullable',
            'etat' => 'in:actif,inactif',
        ]);

        $validated['modification_username'] = Auth::user()->name ?? Auth::user()->login;

        $item->update($validated);

        return redirect()->route('finances.achats-depenses.index')
            ->with('message', trans('common.record_updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $item = AchatDepense::findOrFail($id);
        $item->delete();

        return back()->with('message', trans('common.record_deleted'));
    }

    /**
     * Toggle the status of the specified resource.
     */
    public function statut($id)
    {
        $item = AchatDepense::findOrFail($id);
        $item->etat = $item->etat === 'actif' ? 'inactif' : 'actif';
        $item->modification_username = Auth::user()->name ?? Auth::user()->login;
        $item->save();

        return redirect()->route('finances.achats-depenses.index')
            ->with('message', trans('common.status_updated'));
    }
}
