<?php

namespace Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Administration\Entities\Module;

class ModuleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:module-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:module-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:module-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:module-statut', ['only' => ['statut']]);
    }

    /**
     * Display a listing of modules.
     */
    public function index()
    {
        try {
            $modules = Module::orderBy('libelle', 'asc')
                ->paginate(10)
                ->withQueryString();

            return Inertia::render('Administration::Modules/Index', [
                'modules' => $modules,
            ]);
        } catch (\Throwable $th) {
            log_error("Module", "index", $th->getMessage());
            return redirect()->route('home')->with('error', trans('Erreur'));

        }
    }

    /**
     * Show the form for creating a new module.
     */
    public function create(): Response
    {
        return Inertia::render('Administration::Modules/Create');

    }

    /**
     * Store a newly created module.
     */
    public function store(Request $request)
    {
        $request->validate([
            'libelle' => 'required|string|max:255',
            'libelle_en' => 'nullable|string|max:255',
            'icone' => 'required|string|max:255',
            'menu_url' => 'required|string|max:255',
            'ordre' => 'nullable|integer',
        ]);

        try {
            Module::create($request->all());

            return redirect()
                ->route('administration.modules.index')
                ->with('success', __('enregistrementsucces'));
        } catch (\Throwable $e) {
            log_error("Module", "store", $e->getMessage());
            return redirect()
                ->route('administration.modules.create')
                ->with('error', __('Erreur'));
        }
    }

    /**
     * Display the specified module.
     */
    public function show($id): Response
    {
        try {
            $module = Module::findOrFail($id);

            return Inertia::render('Administration::Modules/Show', [
                'module' => $module,
            ]);
        } catch (\Throwable $th) {
            log_error("Module", "show", $th->getMessage());
            return redirect()->route('administration.modules.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Show the form for editing the specified module.
     */
    public function edit($id): Response
    {
        try {
            $module = Module::findOrFail($id);

            return Inertia::render('Administration::Modules/Edit', [
                'module' => $module,
            ]);
        } catch (\Throwable $th) {
            log_error("Module", "edit", $th->getMessage());
            return redirect()->route('administration.modules.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Update the specified module.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'libelle' => 'required|string|max:255',
            'libelle_en' => 'nullable|string|max:255',
            'icone' => 'required|string|max:255',
            'menu_url' => 'required|string|max:255',
            'ordre' => 'nullable|integer',
        ]);

        try {
            $module = Module::findOrFail($id);
            $module->fill($request->all());
            $module->save();

            return redirect()
                ->route('administration.modules.index')
                ->with('success', __('modifsucces'));
        } catch (\Throwable $e) {
            log_error("Module", "update", $e->getMessage());
            return redirect()
                ->route('administration.modules.edit', $id)
                ->with('error', __('erreurmaj'));
        }
    }

    /**
     * Toggle module status (soft delete/restore).
     */
    public function statut($id)
    {
        try {
            $module = Module::withTrashed()->findOrFail($id);

            if ($module->trashed()) {
                $module->restore();
                $message = __('restaurationsucces');
            } else {
                $module->delete();
                $message = __('suppressionsucces');
            }

            return redirect()->route('administration.modules.index')->with('success', $message);
        } catch (\Throwable $e) {
            log_error("Module", "statut", $e->getMessage());
            return redirect()->route('administration.modules.index')->with('error', __('Erreur'));
        }
    }
}
