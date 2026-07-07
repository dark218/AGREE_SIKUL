<?php

namespace Modules\RessourcesLogistique\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Parametrage\Entities\Ecole;
use Modules\RessourcesLogistique\Entities\Bibliotheque;

class BibliothequeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:bibliotheques-list',   ['only' => ['index', 'show']]);
        $this->middleware('permission.check:bibliotheques-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:bibliotheques-edit',   ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:bibliotheques-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Bibliotheque::query();

            if ($request->filled('search')) {
                $query->where('nom', 'like', '%' . $request->input('search') . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->input('etat'));
            }

            $bibliotheques = $query
                ->with(['responsable', 'ecole'])
                ->paginate(10)
                ->withQueryString()
                ->through(fn ($b) => [
                    'id'          => $b->id,
                    'nom'         => $b->nom,
                    'adresse'     => $b->adresse,
                    'capacite'    => $b->capacite,
                    'etat'        => $b->etat,
                    'ecole'       => $b->ecole ? ['id' => $b->ecole->id, 'nom' => $b->ecole->nom] : null,
                    'responsable' => $b->responsable ? ['id' => $b->responsable->id, 'nom' => $b->responsable->nom, 'prenoms' => $b->responsable->prenoms] : null,
                ]);

            return Inertia::render('RessourcesLogistique::Bibliotheques/Index', [
                'bibliotheques' => $bibliotheques,
                'filters'       => $request->only(['search', 'etat']),
            ]);
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "BibliothequeController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('RessourcesLogistique::Bibliotheques/Create', [
                'ecoles'       => Ecole::orderBy('nom')->get(['id', 'nom']),
                'responsables' => User::orderBy('nom')->get(['id', 'nom', 'prenoms']),
            ]);
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "BibliothequeController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'ecole_id'       => 'nullable|exists:ecoles,id',
                'nom'            => 'required|string|max:125',
                'adresse'        => 'nullable|string|max:255',
                'capacite'       => 'nullable|integer|min:0',
                'responsable_id' => 'nullable|exists:users,id',
                'etat'           => 'required|in:actif,inactif',
            ]);

            Bibliotheque::create($validated);

            return redirect()->route('bibliotheques.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "BibliothequeController::store", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()])->withInput();
        }
    }

    public function show(Bibliotheque $bibliotheque)
    {
        try {
            $bibliotheque->load(['responsable', 'ecole']);

            return Inertia::render('RessourcesLogistique::Bibliotheques/Show', [
                'bibliotheque' => $bibliotheque,
            ]);
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "BibliothequeController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(Bibliotheque $bibliotheque)
    {
        try {
            return Inertia::render('RessourcesLogistique::Bibliotheques/Edit', [
                'bibliotheque' => $bibliotheque->load(['responsable', 'ecole']),
                'ecoles'       => Ecole::orderBy('nom')->get(['id', 'nom']),
                'responsables' => User::orderBy('nom')->get(['id', 'nom', 'prenoms']),
            ]);
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "BibliothequeController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, Bibliotheque $bibliotheque)
    {
        try {
            $validated = $request->validate([
                'ecole_id'       => 'nullable|exists:ecoles,id',
                'nom'            => 'required|string|max:125',
                'adresse'        => 'nullable|string|max:255',
                'capacite'       => 'nullable|integer|min:0',
                'responsable_id' => 'nullable|exists:users,id',
                'etat'           => 'required|in:actif,inactif',
            ]);

            $bibliotheque->update($validated);

            return redirect()->route('bibliotheques.show', $bibliotheque)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "BibliothequeController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()])->withInput();
        }
    }

    public function destroy(Bibliotheque $bibliotheque)
    {
        try {
            $bibliotheque->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Bibliotheque", "BibliothequeController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(Bibliotheque $bibliotheque)
    {
        try {
            if ($bibliotheque->trashed()) {
                $bibliotheque->restore();
            } else {
                $bibliotheque->delete();
            }

            return redirect()->route('bibliotheques.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Bibliotheque", "BibliothequeController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
