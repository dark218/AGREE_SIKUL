<?php

namespace Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Administration\Entities\Feature;
use Modules\Administration\Entities\Module;
use Modules\Administration\Permissions;
use Spatie\Permission\Models\Permission;
use Modules\Administration\Entities\Role;

class RoleController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('permission.check:roles-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:roles-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:roles-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:roles-statut', ['only' => ['statut']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        try {
            $name = $request->input('name');

            $roles = Role::where(function ($q) use ($name) {
                if ($name != null) {
                    $q->where('name', 'LIKE', '%' . $name . '%');
                }
            })
                ->orderBy('id', 'DESC')
                ->paginate(10)
                ->withQueryString();

            return Inertia::render('Administration::Roles/Index', [
                'roles' => $roles,
                'filters' => [
                    'name' => $name,
                ],
            ]);
        } catch (\Throwable $th) {
            log_error("Role", "index", $th->getMessage());
            return redirect()->route('home')->with('error', __('Erreur'));
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        try {
            $modules = Module::with(['feature.permissions'])->get();

            return Inertia::render('Administration::Roles/Create', [
                'modules' => $this->formatModulesForVue($modules),
            ]);
        } catch (\Throwable $th) {
            log_error("Role", "create", $th->getMessage());
            return redirect()->route('administration.roles.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'array',
        ]);

        try {
            $role = Role::create(['name' => $request->input('name')]);

            if ($request->has('permissions') && count($request->permissions) > 0) {
                $permissions = Permission::whereIn('id', $request->permissions)->get();
                $role->syncPermissions($permissions);
            }

            return redirect()->route('administration.roles.index')->with('success', __('enregistrementsucces'));
        } catch (\Throwable $e) {
            log_error("Role", "store", $e->getMessage());
            return redirect()->route('administration.roles.create')->with('error', __('Erreur'));
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id): Response
    {
        try {
            $role = Role::with('permissions')->findOrFail($id);
            $modules = Module::with(['feature.permissions'])->get();

            $rolePermissions = $role->permissions->pluck('id')->toArray();

            return Inertia::render('Administration::Roles/Show', [
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $rolePermissions,
                ],
                'modules' => $this->formatModulesForVue($modules),
                'rolePermissions' => $rolePermissions,
            ]);
        } catch (\Throwable $th) {
            log_error("Role", "show", $th->getMessage());
            return redirect()->route('administration.roles.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id): Response
    {
        try {
            $role = Role::with('permissions')->findOrFail($id);
            $modules = Module::with(['feature.permissions'])->get();

            $rolePermissions = $role->permissions->pluck('id')->toArray();

            return Inertia::render('Administration::Roles/Edit', [
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $rolePermissions,
                ],
                'modules' => $this->formatModulesForVue($modules),
                'rolePermissions' => $rolePermissions,
            ]);
        } catch (\Throwable $th) {
            log_error("Role", "edit", $th->getMessage());
            return redirect()->route('administration.roles.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'permissions' => 'array',
        ]);

        try {
            $role = Role::findOrFail($id);
            $role->name = $request->input('name');
            $role->save();

            if ($request->has('permissions')) {
                $permissions = Permission::whereIn('id', $request->permissions)->get();
                $role->syncPermissions($permissions);
            } else {
                $role->syncPermissions([]);
            }

            return redirect()->route('administration.roles.index')->with('success', __('modifsucces'));
        } catch (\Throwable $e) {
            log_error("Role", "update", $e->getMessage());
            return redirect()->route('administration.roles.edit', $id)->with('error', __('erreurmaj'));
        }
    }

    /**
     * Toggle status (soft delete/restore) the specified resource.
     */
    public function statut($id)
    {
        try {
            $role = Role::withTrashed()->findOrFail($id);

            if ($role->trashed()) {
                $role->restore();
                $message = __('restaurationsucces');
            } else {
                $role->delete();
                $message = __('suppressionsucces');
            }

            return redirect()->route('administration.roles.index')->with('success', $message);
        } catch (\Throwable $e) {
            log_error("Role", "statut", $e->getMessage());
            return redirect()->route('administration.roles.index')->with('error', __('erreurmaj'));
        }
    }

    /**
     * Delete the specified resource.
     */
    public function destroy($id)
    {
        try {
            $role = Role::findOrFail($id);
            $role->delete();

            $message = __('suppressionsucces');

            return redirect()->route('administration.roles.index')->with('success',$message);
        } catch (\Throwable $e) {
            log_error("Role", "destroy", $e->getMessage());
            return redirect()->route('administration.roles.index')->with('error', __('erreurmaj'));
        }
    }

    /**
     * Format modules data for Vue component
     */
    private function formatModulesForVue($modules): array
    {
        return $modules->map(function ($module) {
            return [
                'id' => $module->id,
                'libelle' => $module->libelle,
                'features' => $module->feature->map(function ($feature) {
                    return [
                        'id' => $feature->id,
                        'libelle' => $feature->libelle,
                        'name' => $feature->name ?? $feature->libelle,
                        'permissions' => $feature->permissions->map(function ($permission) {
                            return [
                                'id' => $permission->id,
                                'name' => $permission->name,
                                'libelle' => $permission->libelle ?? $permission->name,
                            ];
                        })->toArray(),
                    ];
                })->toArray(),
            ];
        })->toArray();
    }
}
