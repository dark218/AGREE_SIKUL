<?php

namespace Modules\Administration\Http\Controllers;

use Modules\Administration\Entities\Feature;
use Modules\Administration\Entities\Module;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Controllers\Controller;

class FeatureController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:feature-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:feature-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:feature-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:feature-statut', ['only' => ['statut']]);
    }

    /**
     * Display a listing of features.
     */
    public function index(Request $request): Response
    {
        try {
            $filters = [
                'libelle' => $request->input('libelle'),
                'module_id' => $request->input('module_id'),
            ];

            $query = Feature::with('module')->orderBy('id', 'DESC');

            if (!empty($filters['libelle'])) {
                $query->where('libelle', 'LIKE', '%' . $filters['libelle'] . '%');
            }

            if (!empty($filters['module_id'])) {
                $query->where('module_id', $filters['module_id']);
            }

            $features = $query->paginate(10)->withQueryString();

            // Transformer pour Vue
            $features->getCollection()->transform(function ($feature) {
                return [
                    'id' => $feature->id,
                    'libelle' => $feature->libelle,
                    'libelle_en' => $feature->libelle_en,
                    'icone' => $feature->icone,
                    'menu_url' => $feature->menu_url,
                    'ordre' => $feature->ordre,
                    'module' => $feature->module ? $feature->module->libelle : null,
                    'module_id' => $feature->module_id,
                ];
            });

            return Inertia::render('Administration::Features/Index', [
                'features' => $features,
                'modules' => Module::select('id', 'libelle')->get(),
                'filters' => $filters,
            ]);
        } catch (\Throwable $th) {
            log_error("Feature", "index", $th->getMessage());
            return redirect()->route('home')->with('error', __('erreurmaj'));
        }


    }

    /**
     * Show the form for creating a new feature.
     */
    public function create(): Response
    {

        return Inertia::render('Administration::Features/Create', [
            'modules' => Module::select('id', 'libelle')->get(),
        ]);
    }

    /**
     * Store a newly created feature.
     */
    public function store(Request $request)
    {
        $request->validate([
            'libelle' => 'required|string|max:255',
            'libelle_en' => 'nullable|string|max:255',
            'module_id' => 'required|exists:module,id',
            'icone' => 'required|string|max:255',
            'menu_url' => 'required|string|max:255',
            'ordre' => 'nullable|integer',
        ]);

        try {
            $feature = Feature::create($request->all());

            // Créer les permissions automatiquement
            $permissions = [
                $request->menu_url . '-list',
                $request->menu_url . '-create',
                $request->menu_url . '-edit',
                $request->menu_url . '-statut',
                $request->menu_url . '-pdf',
            ];

            foreach ($permissions as $permission) {
                if (Str::contains($permission, "-list")) {
                    $libelle = "Liste";
                } elseif (Str::contains($permission, "-create")) {
                    $libelle = "Nouveau";
                } elseif (Str::contains($permission, "-edit")) {
                    $libelle = "Modifier";
                } elseif (Str::contains($permission, "-statut")) {
                    $libelle = "Statut";
                } elseif (Str::contains($permission, "-pdf")) {
                    $libelle = "Export";
                } else {
                    $libelle = $permission;
                }

                Permission::create([
                    'name' => $permission,
                    'libelle' => $libelle,
                    'feature_id' => $feature->id,
                    'guard_name' => 'web',
                ]);
            }

            return redirect()->route('administration.features.index')
                ->with('success', __('enregistrementsucces'));
        } catch (\Throwable $e) {
            log_error("Feature", "store", $e->getMessage());
            return redirect()->route('administration.features.create')
                ->with('error', __('Erreur') . ': ' . $e->getMessage());
        }
    }

    /**
     * Display the specified feature.
     */
    public function show($id): Response
    {
        try {
            $feature = Feature::with('module')->findOrFail($id);

            return Inertia::render('Administration::Features/Show', [
                'feature' => [
                    'id' => $feature->id,
                    'libelle' => $feature->libelle,
                    'libelle_en' => $feature->libelle_en,
                    'icone' => $feature->icone,
                    'menu_url' => $feature->menu_url,
                    'ordre' => $feature->ordre,
                    'module' => $feature->module ? $feature->module->libelle : null,
                    'module_id' => $feature->module_id,
                ],
                'modules' => Module::select('id', 'libelle')->get(),
            ]);
        } catch (\Throwable $th) {
            log_error("Feature", "show", $th->getMessage());
            return redirect()->route('administration.features.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Show the form for editing the specified feature.
     */
    public function edit($id): Response
    {
        try {
            $feature = Feature::with('module')->findOrFail($id);

            return Inertia::render('Administration::Features/Edit', [
                'feature' => $feature,
                'modules' => Module::select('id', 'libelle')->get(),
            ]);
        } catch (\Throwable $th) {
            log_error("Feature", "edit", $th->getMessage());
            return redirect()->route('administration.features.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Update the specified feature.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'libelle' => 'required|string|max:255',
            'libelle_en' => 'nullable|string|max:255',
            'module_id' => 'required|exists:module,id',
            'icone' => 'required|string|max:255',
            'menu_url' => 'required|string|max:255',
            'ordre' => 'nullable|integer',
        ]);

        try {
            $feature = Feature::findOrFail($id);
            $feature->fill($request->all());
            $feature->save();

            return redirect()->route('administration.features.index')
                ->with('success', __('modifsucces'));
        } catch (\Throwable $e) {
            log_error("Feature", "update", $e->getMessage());
            return redirect()->route('administration.features.edit', $id)
                ->with('error', __('erreurmaj'));
        }
    }

    /**
     * Toggle feature status (soft delete/restore).
     */
    public function statut($id)
    {
        try {
            $feature = Feature::findOrFail($id);

            if ($feature->deleted_at == null) {
                $feature->deleted_at = Carbon::now();
                $message = __('suppressionsucces');
            } else {
                $feature->deleted_at = null;
                $message = __('restaurationsucces');
            }
            $feature->save();

            return redirect()->route('administration.features.index')->with('success', $message);
        } catch (\Throwable $e) {
            log_error("Feature", "statut", $e->getMessage());
            return redirect()->route('administration.features.index')->with('error', __('Erreur'));
        }
    }
}
