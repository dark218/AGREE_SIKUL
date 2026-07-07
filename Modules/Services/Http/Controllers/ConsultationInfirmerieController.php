<?php

namespace Modules\Services\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Services\Entities\ConsultationInfirmerie;

class ConsultationInfirmerieController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:consultations-infirmerie-list',   ['only' => ['index', 'show']]);
        $this->middleware('permission.check:consultations-infirmerie-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:consultations-infirmerie-edit',   ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:consultations-infirmerie-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = ConsultationInfirmerie::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('motif', 'like', "%$search%")
                      ->orWhereHas('apprenant', function ($qa) use ($search) {
                          $qa->where('nom', 'like', "%$search%")
                             ->orWhere('prenoms', 'like', "%$search%");
                      });
                });
            }

            $consultations = $query
                ->with(['apprenant', 'infirmier'])
                ->orderByDesc('date_consultation')
                ->paginate(10)
                ->withQueryString();

            return Inertia::render('Services::ConsultationsInfirmeries/Index', [
                'consultations' => $consultations,
                'filters'       => $request->only(['search']),
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "ConsultationInfirmerieController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Services::ConsultationsInfirmeries/Create', [
                'apprenants'  => User::role('apprenant')->orderBy('nom')->get(['id', 'nom', 'prenoms']),
                'infirmiers'  => User::role('infirmier')->orderBy('nom')->get(['id', 'nom', 'prenoms']),
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "ConsultationInfirmerieController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'apprenant_id'      => 'required|exists:users,id',
                'infirmier_id'      => 'nullable|exists:users,id',
                'date_consultation' => 'required|date',
                'motif'             => 'required|string|max:255',
                'diagnostic'        => 'nullable|string',
                'traitement'        => 'nullable|string',
            ]);

            ConsultationInfirmerie::create($validated);

            return redirect()->route('consultations-infirmerie.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("Services", "ConsultationInfirmerieController::store", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()])->withInput();
        }
    }

    public function show(ConsultationInfirmerie $consultation)
    {
        try {
            $consultation->load(['apprenant', 'infirmier']);

            return Inertia::render('Services::ConsultationsInfirmeries/Show', [
                'consultation' => $consultation,
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "ConsultationInfirmerieController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(ConsultationInfirmerie $consultation)
    {
        try {
            return Inertia::render('Services::ConsultationsInfirmeries/Edit', [
                'consultation' => $consultation->load(['apprenant', 'infirmier']),
                'apprenants'   => User::role('apprenant')->orderBy('nom')->get(['id', 'nom', 'prenoms']),
                'infirmiers'   => User::role('infirmier')->orderBy('nom')->get(['id', 'nom', 'prenoms']),
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "ConsultationInfirmerieController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, ConsultationInfirmerie $consultation)
    {
        try {
            $validated = $request->validate([
                'apprenant_id'      => 'required|exists:users,id',
                'infirmier_id'      => 'nullable|exists:users,id',
                'date_consultation' => 'required|date',
                'motif'             => 'required|string|max:255',
                'diagnostic'        => 'nullable|string',
                'traitement'        => 'nullable|string',
            ]);

            $consultation->update($validated);

            return redirect()->route('consultations-infirmerie.show', $consultation)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("Services", "ConsultationInfirmerieController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()])->withInput();
        }
    }

    public function destroy(ConsultationInfirmerie $consultation)
    {
        try {
            $consultation->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Services", "ConsultationInfirmerieController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(ConsultationInfirmerie $consultation)
    {
        try {
            if ($consultation->trashed()) {
                $consultation->restore();
            } else {
                $consultation->delete();
            }

            return redirect()->route('consultations-infirmerie.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Services", "ConsultationInfirmerieController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
