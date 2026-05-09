<?php

namespace Modules\Administration\Http\Controllers;

use Modules\Administration\Entities\Feature;
use Modules\Administration\Entities\Permissions;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\App;

use Inertia\Response;
use App\Http\Controllers\Controller;

class PermissionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:permission-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:permission-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:permission-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:permission-statut', ['only' => ['statut']]);
    }

    /**
     * Display a listing of permissions.
     */
    public function index()
    {
        try {
            $permissions = Permissions::with('feature')
                ->orderBy('name', 'asc')
                ->paginate(10)
                ->withQueryString();

            // Transformer pour Vue
            $permissions->getCollection()->transform(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'libelle' => $permission->libelle,
                    'guard_name' => $permission->guard_name,
                    'feature' => $permission->feature ? $permission->feature->libelle : null,
                    'feature_id' => $permission->feature_id,
                ];
            });

            return Inertia::render('Administration::Permissions/Index', [
                'permissions' => $permissions,
            ]);
        } catch (\Throwable $th) {
            log_error("Permissions", "index", $th->getMessage());
            return redirect()->route('home')->with('error', trans('Erreur'));
        }
    }

    /**
     * Show the form for creating a new permission.
     */
    public function create(): Response
    {
        try {
            return Inertia::render('Administration::Permissions/Create', [
                'features' => Feature::select('id', 'libelle')->get(),
            ]);
        } catch (\Throwable $th) {
            log_error("Permission", "create", $th->getMessage());
            return redirect()->route('administration.permissions.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Store a newly created permission.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'feature_id' => 'required|exists:feature,id',
            'libelle' => 'nullable|string|max:255',
        ]);

        try {
            $permission = Permissions::create([
                'name' => $request->name,
                'feature_id' => $request->feature_id,
                'libelle' => $request->name,
                'guard_name' => 'web',
            ]);

            return redirect()->route('administration.permissions.index')
                ->with('success', __('enregistrementsucces'));
        } catch (\Throwable $e) {
            log_error("Permissions", "store", $e->getMessage());
            return redirect()->route('administration.permissions.create')
                ->with('error', __('Erreur') . ': ' . $e->getMessage());
        }
    }

    /**
     * Display the specified permission.
     */
    public function show($id): Response
    {
        try {
            $permission = Permissions::with('feature')->findOrFail($id);

            return Inertia::render('Administration::Permissions/Show', [
                'permission' => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'libelle' => $permission->libelle,
                    'guard_name' => $permission->guard_name,
                    'feature' => $permission->feature ? $permission->feature->libelle : null,
                    'feature_id' => $permission->feature_id,
                ],
                'features' => Feature::select('id', 'libelle')->get(),
            ]);
        } catch (\Throwable $th) {
            log_error("Permissions", "show", $th->getMessage());
            return redirect()->route('administration.permissions.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Show the form for editing the specified permission.
     */
    public function edit($id): Response
    {
        try {
            $permission = Permissions::with('feature')->findOrFail($id);

            return Inertia::render('Administration::Permissions/Edit', [
                'permission' => $permission,
                'features' => Feature::select('id', 'libelle')->get(),
            ]);
        } catch (\Throwable $th) {
            log_error("Permissions", "edit", $th->getMessage());
            return redirect()->route('administration.permissions.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Update the specified permission.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $id,
            'feature_id' => 'required|exists:feature,id',
            'libelle' => 'nullable|string|max:255',
        ]);

        try {
            $permission = Permissions::findOrFail($id);
            $permission->update($request->all());

            return redirect()->route('administration.permissions.index')
                ->with('success', __('modifsucces'));
        } catch (\Throwable $e) {
            log_error("Permissions", "update", $e->getMessage());
            return redirect()->route('administration.permissions.edit', $id)
                ->with('error', __('erreurmaj'));
        }
    }

    /**
     * Toggle permission status (soft delete/restore).
     */
    public function statut($id)
    {
        try {
            $permission = Permissions::withTrashed()->findOrFail($id);

            if ($permission->trashed()) {
                $permission->restore();
                $message = __('restaurationsucces');
            } else {
                $permission->delete();
                $message = __('suppressionsucces');
            }

            return redirect()->route('administration.permissions.index')->with('success', $message);
        } catch (\Throwable $e) {
            log_error("Permissions", "statut", $e->getMessage());
            return redirect()->route('administration.permissions.index')->with('error', __('Erreur'));
        }
    }
}
