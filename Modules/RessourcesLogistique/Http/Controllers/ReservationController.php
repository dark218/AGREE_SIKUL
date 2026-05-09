<?php

namespace Modules\RessourcesLogistique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\RessourcesLogistique\Entities\Reservation;

class ReservationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:reservations-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:reservations-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:reservations-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:reservations-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Reservation::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('apprenant.user', function ($q) use ($search) {
                    $q->where('nom', 'like', "%$search%")
                        ->orWhere('prenoms', 'like', "%$search%");
                });
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $reservations = $query->with(['exemplaire', 'apprenant.user'])->paginate(10)->withQueryString()
                ->through(fn ($reservation) => [
                    'id' => $reservation->id,
                    'date_reservation' => $reservation->date_reservation?->toDateString(),
                    'date_expiration' => $reservation->date_expiration?->toDateString(),
                    'priorite' => $reservation->priorite,
                    'statut' => $reservation->statut,
                    'apprenant' => $reservation->apprenant?->user?->nom . ' ' . $reservation->apprenant?->user?->prenoms . ' (' . $reservation->apprenant?->matricule . ')',
                    'exemplaire' => $reservation->exemplaire?->code_exemplaire,
                ]);

            return Inertia::render('RessourcesLogistique::Reservations/Index', [
                'reservations' => $reservations,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "ReservationController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            $exemplaires = \Modules\RessourcesLogistique\Entities\Exemplaire::all(['id', 'code_exemplaire'])->toArray();
            $apprenants = \Modules\Academique\Entities\Apprenant::with('user')->get(['id', 'user_id', 'matricule'])->map(fn ($a) => [
                'id' => $a->id,
                'name' => ($a->user ? $a->user->nom . ' ' . $a->user->prenoms : 'N/A') . ' (' . $a->matricule . ')',
            ])->toArray();

            return Inertia::render('RessourcesLogistique::Reservations/Create', [
                'exemplaires' => $exemplaires,
                'apprenants' => $apprenants,
            ]);
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "ReservationController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            \Log::info('🔵 ReservationController::store - START', [
                'all_data' => $request->all(),
                'method' => $request->method(),
            ]);

            $validated = $request->validate([
                'exemplaire_id' => 'required|exists:exemplaires,id',
                'apprenant_id' => 'required|exists:apprenants,id',
                'date_reservation' => 'required|date',
                'date_expiration' => 'required|date|after:date_reservation',
                'date_notification' => 'nullable|date',
                'priorite' => 'required|in:basse,normale,haute',
                'statut' => 'required|in:en_attente,confirmee,annulee,retiree',
            ]);

            \Log::info('✅ Validation PASSED', ['validated_data' => $validated]);

            $reservation = Reservation::create($validated);

            \Log::info('✅ Reservation CREATED', ['id' => $reservation->id, 'data' => $reservation->toArray()]);

            return redirect()->route('reservations.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            \Log::error('❌ Validation ERROR', [
                'errors' => $ve->errors(),
                'messages' => $ve->messages(),
            ]);
            return back()->withErrors($ve->errors());
        } catch (\Throwable $th) {
            \Log::error('❌ ReservationController::store ERROR', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);
            log_error("Bibliotheque", "ReservationController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(Reservation $reservation)
    {
        try {
            $exemplaires = \Modules\RessourcesLogistique\Entities\Exemplaire::all(['id', 'code_exemplaire'])->toArray();
            $apprenants = \Modules\Academique\Entities\Apprenant::with('user')->get(['id', 'user_id', 'matricule'])->map(fn ($a) => [
                'id' => $a->id,
                'name' => ($a->user ? $a->user->nom . ' ' . $a->user->prenoms : 'N/A') . ' (' . $a->matricule . ')',
            ])->toArray();

            return Inertia::render('RessourcesLogistique::Reservations/Show', [
                'reservation' => $reservation->load('exemplaire', 'apprenant.user'),
                'exemplaires' => $exemplaires,
                'apprenants' => $apprenants,
            ]);
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "ReservationController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(Reservation $reservation)
    {
        try {
            $exemplaires = \Modules\RessourcesLogistique\Entities\Exemplaire::all(['id', 'code_exemplaire'])->toArray();
            $apprenants = \Modules\Academique\Entities\Apprenant::with('user')->get(['id', 'user_id', 'matricule'])->map(fn ($a) => [
                'id' => $a->id,
                'name' => ($a->user ? $a->user->nom . ' ' . $a->user->prenoms : 'N/A') . ' (' . $a->matricule . ')',
            ])->toArray();

            return Inertia::render('RessourcesLogistique::Reservations/Edit', [
                'reservation' => $reservation->load('exemplaire', 'apprenant.user'),
                'exemplaires' => $exemplaires,
                'apprenants' => $apprenants,
            ]);
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "ReservationController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, Reservation $reservation)
    {
        try {
            $validated = $request->validate([
                'exemplaire_id' => 'required|exists:exemplaires,id',
                'apprenant_id' => 'required|exists:apprenants,id',
                'date_reservation' => 'required|date',
                'date_expiration' => 'required|date|after:date_reservation',
                'date_notification' => 'nullable|date',
                'priorite' => 'required|in:basse,normale,haute',
                'statut' => 'required|in:en_attente,confirmee,annulee,retiree',
            ]);

            $reservation->update($validated);

            return redirect()->route('reservations.show', $reservation)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Bibliotheque", "ReservationController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(Reservation $reservation)
    {
        try {
            $reservation->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Bibliotheque", "ReservationController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(Reservation $reservation)
    {
        try {
            if ($reservation->trashed()) {
                $reservation->restore();
            } else {
                $reservation->delete();
            }

            return redirect()->route('reservations.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Bibliotheque", "ReservationController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
