<?php

namespace Modules\RessourcesLogistique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\RessourcesLogistique\Entities\Emprunt;

class EmpruntController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:emprunts-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:emprunts-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:emprunts-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:emprunts-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Emprunt::query();

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

            $emprunts = $query->with(['exemplaire', 'apprenant.user'])->paginate(10)->withQueryString()
                ->through(fn ($emprunt) => [
                    'id' => $emprunt->id,
                    'date_emprunt' => $emprunt->date_emprunt?->toDateString(),
                    'date_retour_prevue' => $emprunt->date_retour_prevue?->toDateString(),
                    'statut' => $emprunt->statut,
                    'apprenant' => $emprunt->apprenant?->user?->nom . ' ' . $emprunt->apprenant?->user?->prenoms,
                    'exemplaire' => $emprunt->exemplaire?->code_exemplaire,
                ]);

            return Inertia::render('RessourcesLogistique::Emprunts/Index', [
                'emprunts' => $emprunts,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "EmpruntController::index", $th->getMessage());
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

            return Inertia::render('RessourcesLogistique::Emprunts/Create', [
                'exemplaires' => $exemplaires,
                'apprenants' => $apprenants,
            ]);
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "EmpruntController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'exemplaire_id' => 'required|exists:exemplaires,id',
                'apprenant_id' => 'required|exists:apprenants,id',
                'date_emprunt' => 'required|date',
                'date_retour_prevue' => 'required|date|after:date_emprunt',
                'statut' => 'required|in:en_cours,en_retard,retourne,perdu',
            ]);

            Emprunt::create($validated);

            return redirect()->route('emprunts.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Bibliotheque", "EmpruntController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(Emprunt $emprunt)
    {
        try {
            $exemplaires = \Modules\RessourcesLogistique\Entities\Exemplaire::all(['id', 'code_exemplaire'])->toArray();
            $apprenants = \Modules\Academique\Entities\Apprenant::with('user')->get(['id', 'user_id', 'matricule'])->map(fn ($a) => [
                'id' => $a->id,
                'name' => ($a->user ? $a->user->nom . ' ' . $a->user->prenoms : 'N/A') . ' (' . $a->matricule . ')',
            ])->toArray();

            return Inertia::render('RessourcesLogistique::Emprunts/Show', [
                'emprunt' => $emprunt->load(['exemplaire', 'apprenant.user']),
                'exemplaires' => $exemplaires,
                'apprenants' => $apprenants,
            ]);
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "EmpruntController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(Emprunt $emprunt)
    {
        try {
            $exemplaires = \Modules\RessourcesLogistique\Entities\Exemplaire::all(['id', 'code_exemplaire'])->toArray();
            $apprenants = \Modules\Academique\Entities\Apprenant::with('user')->get(['id', 'user_id', 'matricule'])->map(fn ($a) => [
                'id' => $a->id,
                'name' => ($a->user ? $a->user->nom . ' ' . $a->user->prenoms : 'N/A') . ' (' . $a->matricule . ')',
            ])->toArray();

            return Inertia::render('RessourcesLogistique::Emprunts/Edit', [
                'emprunt' => $emprunt->load(['exemplaire', 'apprenant.user']),
                'exemplaires' => $exemplaires,
                'apprenants' => $apprenants,
            ]);
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "EmpruntController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, Emprunt $emprunt)
    {
        try {
            $validated = $request->validate([
                'exemplaire_id' => 'required|exists:exemplaires,id',
                'apprenant_id' => 'required|exists:apprenants,id',
                'date_emprunt' => 'required|date',
                'date_retour_prevue' => 'required|date|after:date_emprunt',
                'statut' => 'required|in:en_cours,en_retard,retourne,perdu',
            ]);

            $emprunt->update($validated);

            return redirect()->route('emprunts.show', $emprunt)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Bibliotheque", "EmpruntController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(Emprunt $emprunt)
    {
        try {
            $emprunt->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Bibliotheque", "EmpruntController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(Emprunt $emprunt)
    {
        try {
            if ($emprunt->trashed()) {
                $emprunt->restore();
            } else {
                $emprunt->delete();
            }

            return redirect()->route('emprunts.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Bibliotheque", "EmpruntController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
