<?php

namespace Modules\Parametrage\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Parametrage\Entities\FournisseurPaiement;
use Modules\Parametrage\Entities\PaysDevise;

class FournisseurPaiementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:fournisseurpaiement-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:fournisseurpaiement-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:fournisseurpaiement-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:fournisseurpaiement-statut', ['only' => ['statut']]);
    }

    public function index(Request $request): Response
    {
        try {
            $paysCurrent = auth()->user()->pays_id;
            $filters = [
                'nom' => $request->input('nom'),
                'code' => $request->input('code'),
                'type' => $request->input('type'),
            ];

            $types = [
                ['value' => FournisseurPaiement::TYPE_MOBILE_MONEY, 'label' => 'Mobile Money'],
                ['value' => FournisseurPaiement::TYPE_BANQUE, 'label' => 'Banque'],
                ['value' => FournisseurPaiement::TYPE_MICROFINANCE, 'label' => 'Microfinance'],
                ['value' => FournisseurPaiement::TYPE_AGGREGATEUR, 'label' => 'Agrégateur'],
                ['value' => FournisseurPaiement::TYPE_CARTE, 'label' => 'Carte'],
            ];

            $paysdevises = PaysDevise::with(['pays', 'devise'])->get();

            $query = FournisseurPaiement::query()
                ->with(['paysDevise.pays', 'paysDevise.devise'])
                ->orderBy('created_at', 'DESC');

            if (!empty($filters['nom'])) {
                $query->where('nom', 'LIKE', '%' . $filters['nom'] . '%');
            }

            if (!empty($filters['code'])) {
                $query->where('code', 'LIKE', '%' . $filters['code'] . '%');
            }

            if (!empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            $fournisseurs = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::FournisseursPaiement/Index', [
                'fournisseurs' => $fournisseurs,
                'filters' => $filters,
                'types' => $types,
                'paysdevises' => $paysdevises,
                'paysCurrent' => $paysCurrent,
            ]);
        } catch (\Throwable $th) {
            \Log::error("error");
            return redirect()->route('home')->with('error', __('erreurmaj'));
        }
    }

    public function create(): Response
    {
        try {
            $paysCurrent = auth()->user()->pays_id;
            $types = [
                ['value' => FournisseurPaiement::TYPE_MOBILE_MONEY, 'label' => 'Mobile Money'],
                ['value' => FournisseurPaiement::TYPE_BANQUE, 'label' => 'Banque'],
                ['value' => FournisseurPaiement::TYPE_MICROFINANCE, 'label' => 'Microfinance'],
                ['value' => FournisseurPaiement::TYPE_AGGREGATEUR, 'label' => 'Agrégateur'],
                ['value' => FournisseurPaiement::TYPE_CARTE, 'label' => 'Carte'],
            ];
            $paysdevises = PaysDevise::with(['pays', 'devise'])->get();

            return Inertia::render('Parametrage::FournisseursPaiement/Create', [
                'types' => $types,
                'paysdevises' => $paysdevises,
                'paysCurrent' => $paysCurrent,
            ]);
        } catch (\Throwable $th) {
            \Log::error("error");
            return redirect()->route('parametrage.fournisseurs_paiement.index')->with('error', __('erreurmaj'));
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'pays_devise_id' => 'required|exists:pays_devises,id',
            'libelle' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:fournisseurs_paiement,code',
            'type' => [
                'required',
                'in:' . implode(',', [
                    FournisseurPaiement::TYPE_MOBILE_MONEY,
                    FournisseurPaiement::TYPE_BANQUE,
                    FournisseurPaiement::TYPE_MICROFINANCE,
                    FournisseurPaiement::TYPE_AGGREGATEUR,
                    FournisseurPaiement::TYPE_CARTE,
                ]),
            ],
        ]);

        try {
            FournisseurPaiement::create([
                'pays_devise_id' => $request->pays_devise_id,
                'nom' => $request->nom,
                'code' => $request->code,
                'type' => $request->type,
            ]);

            return redirect()
                ->route('parametrage.fournisseurs_paiement.index')
                ->with('success', __('enregistrementsucces'));
        } catch (\Throwable $e) {
            \Log::error("error");
            return redirect()
                ->route('parametrage.fournisseurs_paiement.create')
                ->with('error', __('Erreur'));
        }
    }

    public function show($id): Response
    {
        try {
            $paysCurrent = auth()->user()->pays_id;
            $fournisseur = FournisseurPaiement::with(['paysDevise.pays', 'paysDevise.devise'])->findOrFail($id);
            $types = [
                ['value' => FournisseurPaiement::TYPE_MOBILE_MONEY, 'label' => 'Mobile Money'],
                ['value' => FournisseurPaiement::TYPE_BANQUE, 'label' => 'Banque'],
                ['value' => FournisseurPaiement::TYPE_MICROFINANCE, 'label' => 'Microfinance'],
                ['value' => FournisseurPaiement::TYPE_AGGREGATEUR, 'label' => 'Agrégateur'],
                ['value' => FournisseurPaiement::TYPE_CARTE, 'label' => 'Carte'],
            ];
            $paysdevises = PaysDevise::with(['pays', 'devise'])->get();

            return Inertia::render('Parametrage::FournisseursPaiement/Show', [
                'fournisseur' => $fournisseur,
                'types' => $types,
                'paysdevises' => $paysdevises,
                'paysCurrent' => $paysCurrent,
            ]);
        } catch (\Throwable $th) {
            \Log::error("error");
            return redirect()->route('parametrage.fournisseurs_paiement.index')->with('error', __('error_loading_form'));
        }
    }

    public function edit($id): Response
    {
        try {
            $paysCurrent = auth()->user()->pays_id;
            $fournisseur = FournisseurPaiement::with(['paysDevise.pays', 'paysDevise.devise'])->findOrFail($id);
            $types = [
                ['value' => FournisseurPaiement::TYPE_MOBILE_MONEY, 'label' => 'Mobile Money'],
                ['value' => FournisseurPaiement::TYPE_BANQUE, 'label' => 'Banque'],
                ['value' => FournisseurPaiement::TYPE_MICROFINANCE, 'label' => 'Microfinance'],
                ['value' => FournisseurPaiement::TYPE_AGGREGATEUR, 'label' => 'Agrégateur'],
                ['value' => FournisseurPaiement::TYPE_CARTE, 'label' => 'Carte'],
            ];
            $paysdevises = PaysDevise::with(['pays', 'devise'])->get();

            return Inertia::render('Parametrage::FournisseursPaiement/Edit', [
                'fournisseur' => $fournisseur,
                'types' => $types,
                'paysdevises' => $paysdevises,
                'paysCurrent' => $paysCurrent,
            ]);
        } catch (\Throwable $th) {
            \Log::error("error");
            return redirect()->route('parametrage.fournisseurs_paiement.index')->with('error', __('error_loading_form'));
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'pays_devise_id' => 'required|exists:pays_devises,id',
            'libelle' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:fournisseurs_paiement,code,' . $id,
            'type' => [
                'required',
                'in:' . implode(',', [
                    FournisseurPaiement::TYPE_MOBILE_MONEY,
                    FournisseurPaiement::TYPE_BANQUE,
                    FournisseurPaiement::TYPE_MICROFINANCE,
                    FournisseurPaiement::TYPE_AGGREGATEUR,
                    FournisseurPaiement::TYPE_CARTE,
                ]),
            ],
        ]);

        try {
            $fournisseur = FournisseurPaiement::findOrFail($id);
            $fournisseur->update([
                'pays_devise_id' => $request->pays_devise_id,
                'nom' => $request->nom,
                'code' => $request->code,
                'type' => $request->type,
            ]);

            return redirect()
                ->route('parametrage.fournisseurs_paiement.index')
                ->with('success', __('modifsucces'));
        } catch (\Throwable $e) {
            \Log::error("error");
            return redirect()
                ->route('parametrage.fournisseurs_paiement.edit', $id)
                ->with('error', __('erreurmaj'));
        }
    }

    public function activate($id)
    {
        try {
            $fournisseur = FournisseurPaiement::findOrFail($id);

            // Toggle between actif and inactif
            $newStatus = $fournisseur->etat === 'actif' ? 'inactif' : 'actif';
            $fournisseur->etat = $newStatus;
            $fournisseur->updated_by = auth()->id();
            $fournisseur->save();

            $message = $newStatus === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.fournisseurs_paiement.index')->with('success', $message . ' avec succès');
        } catch (\Throwable $e) {
            \Log::error("error");
            return redirect()->route('parametrage.fournisseurs_paiement.index')->with('error', __('Erreur'));
        }
    }
}
