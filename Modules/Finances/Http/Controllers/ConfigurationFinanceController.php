<?php

namespace Modules\Finances\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Finances\Entities\ConfigurationFinance;

class ConfigurationFinanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:configurations-finances-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:configurations-finances-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:configurations-finances-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:configurations-finances-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = ConfigurationFinance::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('libelle', 'like', "%$search%")
                    ->orWhere('code', 'like', "%$search%");
            }

            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $configurations = $query->paginate(10)->withQueryString();

            return Inertia::render('Finances::ConfigurationsFinances/Index', [
                'configurations' => $configurations,
                'filters' => $request->only(['search', 'type', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "ConfigurationFinanceController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            $types = [
                'texte' => 'Texte',
                'monetaire' => 'Montant',
                'pourcentage' => 'Pourcentage',
                'booleen' => 'Booléen',
                'nombre' => 'Nombre',
            ];

            return Inertia::render('Finances::ConfigurationsFinances/Create', [
                'types' => $types,
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "ConfigurationFinanceController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:configurations_finances',
                'libelle' => 'required|string|max:255',
                'valeur' => 'required|string',
                'type' => 'required|in:texte,monetaire,pourcentage,booleen,nombre',
                'description' => 'nullable|string',
                'statut' => 'required|in:actif,inactif',
            ]);

            ConfigurationFinance::create($validated);

            return redirect()->route('finances.configurations-finances.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "ConfigurationFinanceController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(ConfigurationFinance $configuration)
    {
        try {
            $types = [
                'texte' => 'Texte',
                'monetaire' => 'Montant',
                'pourcentage' => 'Pourcentage',
                'booleen' => 'Booléen',
                'nombre' => 'Nombre',
            ];

            return Inertia::render('Finances::ConfigurationsFinances/Show', [
                'configuration' => $configuration,
                'types' => $types,
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "ConfigurationFinanceController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(ConfigurationFinance $configuration)
    {
        try {
            $types = [
                'texte' => 'Texte',
                'monetaire' => 'Montant',
                'pourcentage' => 'Pourcentage',
                'booleen' => 'Booléen',
                'nombre' => 'Nombre',
            ];

            return Inertia::render('Finances::ConfigurationsFinances/Edit', [
                'configuration' => $configuration,
                'types' => $types,
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "ConfigurationFinanceController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, ConfigurationFinance $configuration)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:configurations_finances,code,' . $configuration->id,
                'libelle' => 'required|string|max:255',
                'valeur' => 'required|string',
                'type' => 'required|in:texte,monetaire,pourcentage,booleen,nombre',
                'description' => 'nullable|string',
                'statut' => 'required|in:actif,inactif',
            ]);

            $configuration->update($validated);

            return redirect()->route('finances.configurations-finances.show', $configuration)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "ConfigurationFinanceController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(ConfigurationFinance $configuration)
    {
        try {
            $configuration->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "ConfigurationFinanceController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(ConfigurationFinance $configuration)
    {
        try {
            if ($configuration->trashed()) {
                $configuration->restore();
            } else {
                $configuration->delete();
            }

            return redirect()->route('finances.configurations-finances.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Finances", "ConfigurationFinanceController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
