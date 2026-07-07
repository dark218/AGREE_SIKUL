<?php

namespace Modules\Services\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Services\Entities\InscriptionCantine;
use Modules\Services\Entities\PassageCantine;

class PassageCantineController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:passages-cantine-list',   ['only' => ['index', 'show']]);
        $this->middleware('permission.check:passages-cantine-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:passages-cantine-edit',   ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:passages-cantine-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = PassageCantine::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('inscriptionCantine.apprenant', function ($q) use ($search) {
                    $q->where('nom', 'like', "%$search%")
                      ->orWhere('prenoms', 'like', "%$search%");
                });
            }

            if ($request->filled('date')) {
                $query->whereDate('date_passage', $request->input('date'));
            }

            $passages = $query
                ->with(['inscriptionCantine.apprenant', 'inscriptionCantine.serviceCantine'])
                ->orderByDesc('date_passage')
                ->paginate(10)
                ->withQueryString();

            return Inertia::render('Services::PassagesCantines/Index', [
                'passages' => $passages,
                'filters'  => $request->only(['search', 'date']),
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "PassageCantineController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Services::PassagesCantines/Create', [
                'inscriptions' => InscriptionCantine::with(['apprenant', 'serviceCantine'])
                    ->orderByDesc('created_at')
                    ->get(['id', 'apprenant_id', 'service_cantine_id']),
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "PassageCantineController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'inscription_cantine_id' => 'required|exists:inscriptions_cantines,id',
                'date_passage'           => 'required|date',
                'heure_passage'          => 'nullable|date_format:H:i',
            ]);

            PassageCantine::create($validated);

            return redirect()->route('passages-cantine.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("Services", "PassageCantineController::store", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()])->withInput();
        }
    }

    public function show(PassageCantine $passageCantine)
    {
        try {
            $passageCantine->load(['inscriptionCantine.apprenant', 'inscriptionCantine.serviceCantine']);

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
            return Inertia::render('Services::PassagesCantines/Edit', [
                'passage'      => $passageCantine->load(['inscriptionCantine.apprenant', 'inscriptionCantine.serviceCantine']),
                'inscriptions' => InscriptionCantine::with(['apprenant', 'serviceCantine'])
                    ->orderByDesc('created_at')
                    ->get(['id', 'apprenant_id', 'service_cantine_id']),
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "PassageCantineController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, PassageCantine $passageCantine)
    {
        try {
            $validated = $request->validate([
                'inscription_cantine_id' => 'required|exists:inscriptions_cantines,id',
                'date_passage'           => 'required|date',
                'heure_passage'          => 'nullable|date_format:H:i',
            ]);

            $passageCantine->update($validated);

            return redirect()->route('passages-cantine.show', $passageCantine)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("Services", "PassageCantineController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()])->withInput();
        }
    }

    public function destroy(PassageCantine $passageCantine)
    {
        try {
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
