<?php

namespace Modules\Services\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Services\Entities\PassageCantine;

class PassageCantineController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:passages-cantine-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:passages-cantine-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:passages-cantine-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:passages-cantine-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            \Log::info('🚀 PassageCantineController::index() - START');
            \Log::info('URL: ' . request()->fullUrl());

            $query = PassageCantine::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('apprenant', function ($q) use ($search) {
                    $q->whereHas('user', function ($user) use ($search) {
                        $user->where('nom', 'like', "%$search%")
                            ->orWhere('prenoms', 'like', "%$search%");
                    });
                });
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $passages = $query->with(['apprenant', 'menu'])->paginate(10)->withQueryString();

            return Inertia::render('Services::PassagesCantines/Index', [
                'passages' => $passages,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "PassageCantineController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Services::PassagesCantines/Create');
        } catch (\Throwable $th) {
            log_error("Services", "PassageCantineController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'apprenant_id' => 'required|exists:apprenants,id',
                'menu_id' => 'required|exists:menus,id',
                'date_passage' => 'required|date',
                'heure_passage' => 'required|date_format:H:i',
                'montant_cents' => 'required|integer|min:0',
                'observations' => 'nullable|string',
                'statut' => 'required|in:confirmé,annulé,remboursé',
            ]);

            PassageCantine::create($validated);

            return redirect()->route('passages-cantine.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Services", "PassageCantineController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(PassageCantine $passageCantine)
    {
        try {
            // MANUAL FIX: If model is empty, load it from route parameter
            if (!$passageCantine->exists) {
                $id = request()->route('passageCantine');
                $passageCantine = PassageCantine::find($id);
            }

            $passageCantine->load('apprenant', 'menu');

            return Inertia::render('Services::PassagesCantines/Show', [
                'passage' => $passageCantine,
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "PassageCantineController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(PassageCantine $passageCantine)
    {
        try {
            // MANUAL FIX: If model is empty, load it from route parameter
            if (!$passageCantine->exists) {
                $id = request()->route('passageCantine');
                $passageCantine = PassageCantine::find($id);
            }

            return Inertia::render('Services::PassagesCantines/Edit', [
                'passage' => $passageCantine->load('apprenant', 'menu'),
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "PassageCantineController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, PassageCantine $passageCantine)
    {
        try {
            // MANUAL FIX: If model is empty, load it from route parameter
            if (!$passageCantine->exists) {
                $id = request()->route('passageCantine');
                $passageCantine = PassageCantine::find($id);
            }

            $validated = $request->validate([
                'apprenant_id' => 'required|exists:apprenants,id',
                'menu_id' => 'required|exists:menus,id',
                'date_passage' => 'required|date',
                'heure_passage' => 'required|date_format:H:i',
                'montant_cents' => 'required|integer|min:0',
                'observations' => 'nullable|string',
                'statut' => 'required|in:confirmé,annulé,remboursé',
            ]);

            $passageCantine->update($validated);

            return redirect()->route('passages-cantine.show', $passageCantine)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Services", "PassageCantineController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(PassageCantine $passageCantine)
    {
        try {
            // MANUAL FIX: If model is empty, load it from route parameter
            if (!$passageCantine->exists) {
                $id = request()->route('passageCantine');
                $passageCantine = PassageCantine::find($id);
            }

            $passageCantine->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Services", "PassageCantineController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(PassageCantine $passageCantine)
    {
        try {
            // MANUAL FIX: If model is empty, load it from route parameter
            if (!$passageCantine->exists) {
                $id = request()->route('passageCantine');
                $passageCantine = PassageCantine::find($id);
            }

            if ($passageCantine->trashed()) {
                $passageCantine->restore();
            } else {
                $passageCantine->delete();
            }

            return redirect()->route('passages-cantine.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Services", "PassageCantineController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
