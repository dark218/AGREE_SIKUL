<?php

namespace Modules\Finances\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Finances\Entities\TypeFrais;

class TypeFraisController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:types-frais-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:types-frais-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:types-frais-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:types-frais-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = TypeFrais::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('libelle', 'like', "%$search%")
                      ->orWhere('code', 'like', "%$search%");
                });
            }

            if ($request->filled('obligatoire')) {
                $query->where('obligatoire', (bool) $request->input('obligatoire'));
            }

            $types = $query->paginate(10)->withQueryString();

            return Inertia::render('Finances::TypesFrais/Index', [
                'types' => $types,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "TypeFraisController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Finances::TypesFrais/Create');
        } catch (\Throwable $th) {
            log_error("Finances", "TypeFraisController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code'          => 'required|string|max:100|unique:types_frais',
                'libelle'       => 'required|string|max:255',
                'description'   => 'nullable|string',
                'montant_cents' => 'nullable|integer|min:0',
                'obligatoire'   => 'nullable|boolean',
            ]);
            $validated['obligatoire'] = (bool) ($validated['obligatoire'] ?? true);

            TypeFrais::create($validated);

            return redirect()->route('finances.types-frais.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "TypeFraisController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(TypeFrais $typeFrai)
    {
        try {
            $typeFrai->load('frais');

            return Inertia::render('Finances::TypesFrais/Show', [
                'type' => $typeFrai,
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "TypeFraisController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(TypeFrais $typeFrai)
    {
        try {
            return Inertia::render('Finances::TypesFrais/Edit', [
                'type' => $typeFrai,
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "TypeFraisController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, TypeFrais $typeFrai)
    {
        try {
            $validated = $request->validate([
                'code'          => 'required|string|max:100|unique:types_frais,code,' . $typeFrai->id,
                'libelle'       => 'required|string|max:255',
                'description'   => 'nullable|string',
                'montant_cents' => 'nullable|integer|min:0',
                'obligatoire'   => 'nullable|boolean',
            ]);
            $validated['obligatoire'] = (bool) ($validated['obligatoire'] ?? $typeFrai->obligatoire);

            $typeFrai->update($validated);

            return redirect()->route('finances.types-frais.show', $typeFrai)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "TypeFraisController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(TypeFrais $typeFrai)
    {
        try {
            $typeFrai->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "TypeFraisController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(TypeFrais $typeFrai)
    {
        try {
            if ($typeFrai->trashed()) {
                $typeFrai->restore();
            } else {
                $typeFrai->delete();
            }

            return redirect()->route('finances.types-frais.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Finances", "TypeFraisController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
