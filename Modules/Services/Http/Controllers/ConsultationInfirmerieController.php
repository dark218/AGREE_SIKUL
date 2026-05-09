<?php

namespace Modules\Services\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Services\Entities\ConsultationInfirmerie;

class ConsultationInfirmerieController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:consultations-infirmerie-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:consultations-infirmerie-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:consultations-infirmerie-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:consultations-infirmerie-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = ConsultationInfirmerie::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('apprenant', function ($q) use ($search) {
                    $q->whereHas('user', function ($user) use ($search) {
                        $user->where('nom', 'like', "%$search%")
                            ->orWhere('prenoms', 'like', "%$search%");
                    });
                })->orWhere('motif', 'like', "%$search%");
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $consultations = $query->with(['apprenant', 'infirmier'])->paginate(10)->withQueryString();

            return Inertia::render('Services::ConsultationsInfirmeries/Index', [
                'consultations' => $consultations,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "ConsultationInfirmerieController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Services::ConsultationsInfirmeries/Create');
        } catch (\Throwable $th) {
            log_error("Services", "ConsultationInfirmerieController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'apprenant_id' => 'required|exists:apprenants,id',
                'infirmier_id' => 'required|exists:users,id',
                'date_consultation' => 'required|date',
                'heure_consultation' => 'required|date_format:H:i',
                'motif' => 'required|string|max:255',
                'diagnostic' => 'nullable|string',
                'traitement' => 'nullable|string',
                'observations' => 'nullable|string',
                'statut' => 'required|in:en_attente,consultee,terminee',
            ]);

            ConsultationInfirmerie::create($validated);

            return redirect()->route('consultations-infirmerie.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Services", "ConsultationInfirmerieController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(ConsultationInfirmerie $consultationInfirmerie)
    {
        try {
            $consultationInfirmerie->load('apprenant', 'infirmier');

            return Inertia::render('Services::ConsultationsInfirmeries/Show', [
                'consultation' => $consultationInfirmerie,
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "ConsultationInfirmerieController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(ConsultationInfirmerie $consultationInfirmerie)
    {
        try {
            return Inertia::render('Services::ConsultationsInfirmeries/Edit', [
                'consultation' => $consultationInfirmerie->load('apprenant', 'infirmier'),
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "ConsultationInfirmerieController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, ConsultationInfirmerie $consultationInfirmerie)
    {
        try {
            $validated = $request->validate([
                'apprenant_id' => 'required|exists:apprenants,id',
                'infirmier_id' => 'required|exists:users,id',
                'date_consultation' => 'required|date',
                'heure_consultation' => 'required|date_format:H:i',
                'motif' => 'required|string|max:255',
                'diagnostic' => 'nullable|string',
                'traitement' => 'nullable|string',
                'observations' => 'nullable|string',
                'statut' => 'required|in:en_attente,consultee,terminee',
            ]);

            $consultationInfirmerie->update($validated);

            return redirect()->route('consultations-infirmerie.show', $consultationInfirmerie)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Services", "ConsultationInfirmerieController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(ConsultationInfirmerie $consultationInfirmerie)
    {
        try {
            $consultationInfirmerie->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Services", "ConsultationInfirmerieController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(ConsultationInfirmerie $consultationInfirmerie)
    {
        try {
            if ($consultationInfirmerie->trashed()) {
                $consultationInfirmerie->restore();
            } else {
                $consultationInfirmerie->delete();
            }

            return redirect()->route('consultations-infirmerie.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Services", "ConsultationInfirmerieController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
