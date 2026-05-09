<?php

namespace Modules\Parametrage\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Parametrage\Entities\Devises;
use Modules\Parametrage\Entities\Pays;

class DeviseController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:devise-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:devise-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:devise-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:devise-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:devise-statut', ['only' => ['statut']]);
    }

    /**
     * Display a listing of devises.
     */
    public function index(Request $request): Response
    {
        try {
            $query = Devises::query()
                ->with('pays')  // Eager load la relation pays
                ->orderBy('created_at', 'DESC');

            if ($request->filled('code')) {
                $query->where('code', 'LIKE', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'LIKE', '%' . $request->libelle . '%');
            }

            if ($request->filled('code_iso')) {
                $query->where('code_iso', 'LIKE', '%' . $request->code_iso . '%');
            }

            if ($request->filled('symbol')) {
                $query->where('symbol', 'LIKE', '%' . $request->symbol . '%');
            }

            if ($request->filled('exchange_rate')) {
                $query->where('exchange_rate', $request->exchange_rate);
            }

            if ($request->filled('pays_id')) {
                $query->where('pays_id', $request->pays_id);
            }

            $devises = $query->paginate(10)->withQueryString();

            $pays = Pays::all()->map(function($country) {
                return ['id' => $country->id, 'libelle' => $country->libelle];
            });

            return Inertia::render('Parametrage::Devises/Index', [
                'devises' => $devises,
                'filters' => $request->only(['code', 'libelle', 'code_iso', 'symbol', 'exchange_rate', 'pays_id']),
                'pays' => $pays,
            ]);
        } catch (\Throwable $th) {
            \Log::error("error");
            return redirect()->route('home')->with('error', __('erreurmaj'));
        }
    }

    /**
     * Show the form for creating a new devise.
     */
    public function create(): Response
    {
        try {
            $pays = Pays::all()->map(function($country) {
                return [
                    'id' => $country->id,
                    'libelle' => $country->libelle,
                ];
            });

            return Inertia::render('Parametrage::Devises/Create', [
                'pays' => $pays,
            ]);
        } catch (\Throwable $th) {
            \Log::error("error");
            return redirect()->route('parametrage.devises.index')->with('error', __('erreurmaj'));
        }
    }

    /**
     * Store a newly created devise.
     */
    public function store(Request $request)
    {
        // Convertir le code_iso en majuscules AVANT la validation
        $request->merge([
            'code_iso' => strtoupper($request->code_iso),
            'is_default' => $request->boolean('is_default'),
        ]);

        $request->validate([
            'code' => 'required|string|max:255|unique:devises,code',
            'code_iso' => 'required|string|size:3|regex:/^[A-Z]{3}$/|unique:devises,code_iso',
            'libelle' => 'required|string|max:255',
            'symbol' => 'required|string|max:10',
            'decimal_places' => 'nullable|integer|min:0|max:8',
            'exchange_rate' => 'nullable|numeric|min:0',
            'is_default' => 'nullable|boolean',
            'pays_id' => 'nullable|exists:pays,id',
            'etat' => 'nullable|in:actif,inactif',
        ], [
            'code_iso.regex' => 'Le code ISO doit contenir 3 lettres majuscules (ex: USD, EUR, XOF)',
            'code_iso.size' => 'Le code ISO doit contenir exactement 3 caractères',
            'code_iso.unique' => 'Ce code ISO est déjà utilisé',
        ]);

        try {
            // If this is marked as default, uncheck other defaults
            if ($request->is_default) {
                Devises::where('is_default', true)->update(['is_default' => false]);
            }

            Devises::create([
                'code' => $request->code,
                'code_iso' => $request->code_iso,  // Déjà converti en majuscules plus haut
                'libelle' => $request->libelle,
                'symbol' => $request->symbol,
                'decimal_places' => $request->decimal_places ?? 2,
                'exchange_rate' => $request->exchange_rate ?? 1,
                'is_default' => $request->is_default,  // Déjà converti en boolean plus haut
                'pays_id' => $request->pays_id,
                'etat' => $request->etat ?? 'actif',
                'created_by' => auth()->id(),
            ]);

            return redirect()
                ->route('parametrage.devises.index')
                ->with('success', __('enregistrementsucces'));
        } catch (\Throwable $e) {
            \Log::error("error");
            return redirect()
                ->route('parametrage.devises.create')
                ->with('error', __('Erreur'));
        }
    }

    /**
     * Display the specified devise.
     */
    public function show($id): Response
    {
        try {
            $devise = Devises::findOrFail($id);

            $pays = Pays::all()->map(function($country) {
                return [
                    'id' => $country->id,
                    'libelle' => $country->libelle,
                ];
            });

            return Inertia::render('Parametrage::Devises/Show', [
                'devise' => $devise,
                'pays' => $pays,
            ]);
        } catch (\Throwable $th) {
            \Log::error("error");
            return redirect()->route('parametrage.devises.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Show the form for editing the specified devise.
     */
    public function edit($id): Response
    {
        try {
            $devise = Devises::findOrFail($id);

            $pays = Pays::all()->map(function($country) {
                return [
                    'id' => $country->id,
                    'libelle' => $country->libelle,
                ];
            });

            return Inertia::render('Parametrage::Devises/Edit', [
                'devise' => $devise,
                'pays' => $pays,
            ]);
        } catch (\Throwable $th) {
            \Log::error("error");
            return redirect()->route('parametrage.devises.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Update the specified devise.
     */
    public function update(Request $request, $id)
    {
        // Convertir le code_iso en majuscules AVANT la validation
        $request->merge([
            'code_iso' => strtoupper($request->code_iso),
            'is_default' => $request->boolean('is_default'),
        ]);

        $request->validate([
            'code' => 'required|string|max:255|unique:devises,code,' . $id,
            'code_iso' => 'required|string|size:3|regex:/^[A-Z]{3}$/|unique:devises,code_iso,' . $id,
            'libelle' => 'required|string|max:255',
            'symbol' => 'required|string|max:10',
            'decimal_places' => 'nullable|integer|min:0|max:8',
            'exchange_rate' => 'nullable|numeric|min:0',
            'is_default' => 'nullable|boolean',
            'pays_id' => 'nullable|exists:pays,id',
            'etat' => 'nullable|in:actif,inactif',
        ], [
            'code_iso.regex' => 'Le code ISO doit contenir 3 lettres majuscules (ex: USD, EUR, XOF)',
            'code_iso.size' => 'Le code ISO doit contenir exactement 3 caractères',
            'code_iso.unique' => 'Ce code ISO est déjà utilisé',
        ]);

        try {
            $devise = Devises::findOrFail($id);

            // If this is marked as default, uncheck other defaults
            if ($request->is_default && !$devise->is_default) {
                Devises::where('is_default', true)->update(['is_default' => false]);
            }

            $devise->update([
                'code' => $request->code,
                'code_iso' => $request->code_iso,  // Déjà converti en majuscules plus haut
                'libelle' => $request->libelle,
                'symbol' => $request->symbol,
                'decimal_places' => $request->decimal_places ?? $devise->decimal_places ?? 2,
                'exchange_rate' => $request->exchange_rate ?? $devise->exchange_rate ?? 1,
                'is_default' => $request->is_default,  // Déjà converti en boolean plus haut
                'pays_id' => $request->pays_id,
                'etat' => $request->etat ?? $devise->etat,
                'updated_by' => auth()->id(),
            ]);

            return redirect()
                ->route('parametrage.devises.index')
                ->with('success', __('modifsucces'));
        } catch (\Throwable $e) {
            \Log::error("error");
            return redirect()
                ->route('parametrage.devises.edit', $id)
                ->with('error', __('erreurmaj'));
        }
    }

    /**
     * Toggle devise status (change etat between actif/inactif).
     */
    public function activate($id)
    {
        try {
            $devise = Devises::findOrFail($id);

            // Toggle between actif and inactif
            $newStatus = $devise->etat === 'actif' ? 'inactif' : 'actif';
            $devise->etat = $newStatus;
            $devise->updated_by = auth()->id();
            $devise->save();

            $message = $newStatus === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.devises.index')->with('success', $message . ' avec succès');
        } catch (\Throwable $e) {
            \Log::error("error");
            return redirect()->route('parametrage.devises.index')->with('error', __('Erreur'));
        }
    }

    /**
     * Permanently delete a devise.
     */
    public function destroy($id)
    {
        try {
            $devise = Devises::withTrashed()->findOrFail($id);
            $devise->forceDelete();

            return redirect()->route('parametrage.devises.index')->with('success', __('suppressionsucces'));
        } catch (\Throwable $e) {
            \Log::error("error");
            return redirect()->route('parametrage.devises.index')->with('error', __('Erreur'));
        }
    }
}
