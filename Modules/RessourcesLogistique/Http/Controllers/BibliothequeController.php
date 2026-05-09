<?php

namespace Modules\RessourcesLogistique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\RessourcesLogistique\Entities\Bibliotheque;

class BibliothequeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:bibliotheques-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:bibliotheques-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:bibliotheques-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:bibliotheques-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Bibliotheque::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('nom', 'like', "%$search%")
                    ->orWhere('code', 'like', "%$search%");
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $bibliotheques = $query->paginate(10)->withQueryString()
                ->through(fn ($bibliotheque) => [
                    'id' => $bibliotheque->id,
                    'code' => $bibliotheque->code,
                    'nom' => $bibliotheque->nom,
                    'localisation' => $bibliotheque->localisation,
                    'statut' => $bibliotheque->statut,
                ]);

            return Inertia::render('RessourcesLogistique::Bibliotheques/Index', [
                'bibliotheques' => $bibliotheques,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "BibliothequeController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('RessourcesLogistique::Bibliotheques/Create');
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "BibliothequeController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:bibliotheques',
                'nom' => 'required|string|max:255',
                'localisation' => 'nullable|string|max:255',
                'responsable_id' => 'nullable|exists:users,id',
                'horaire_ouverture' => 'nullable|string|max:50',
                'horaire_fermeture' => 'nullable|string|max:50',
                'statut' => 'required|in:actif,inactif',
            ]);

            Bibliotheque::create($validated);

            return redirect()->route('bibliotheques.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Bibliotheque", "BibliothequeController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(Bibliotheque $bibliotheque)
    {
        try {
            $bibliotheque->load('responsable', 'ouvrages', 'emprunts');

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
                'bibliotheque' => $bibliotheque->load('responsable'),
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
                'code' => 'required|string|max:100|unique:bibliotheques,code,' . $bibliotheque->id,
                'nom' => 'required|string|max:255',
                'localisation' => 'nullable|string|max:255',
                'responsable_id' => 'nullable|exists:users,id',
                'horaire_ouverture' => 'nullable|string|max:50',
                'horaire_fermeture' => 'nullable|string|max:50',
                'statut' => 'required|in:actif,inactif',
            ]);

            $bibliotheque->update($validated);

            return redirect()->route('bibliotheques.show', $bibliotheque)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Bibliotheque", "BibliothequeController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
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
