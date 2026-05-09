<?php

namespace Modules\RessourcesLogistique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\RessourcesLogistique\Entities\Ouvrage;

class OuvrageController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:ouvrages-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:ouvrages-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:ouvrages-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:ouvrages-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Ouvrage::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('titre', 'like', "%$search%")
                    ->orWhere('auteur', 'like', "%$search%")
                    ->orWhere('isbn', 'like', "%$search%");
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $ouvrages = $query->with('bibliotheque')->paginate(10)->withQueryString()
                ->through(fn ($ouvrage) => [
                    'id' => $ouvrage->id,
                    'titre' => $ouvrage->titre,
                    'auteur' => $ouvrage->auteur,
                    'isbn' => $ouvrage->isbn,
                    'bibliotheque' => $ouvrage->bibliotheque?->titre_manuel,
                    'statut' => $ouvrage->statut,
                ]);

            return Inertia::render('RessourcesLogistique::Ouvrages/Index', [
                'ouvrages' => $ouvrages,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "OuvrageController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            $bibliotheques = \Modules\RessourcesLogistique\Entities\Bibliotheque::where('etat', 'actif')
                ->select('id', 'titre_manuel as libelle')->get()->toArray();

            return Inertia::render('RessourcesLogistique::Ouvrages/Create', [
                'bibliotheques' => $bibliotheques,
            ]);
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "OuvrageController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'bibliotheque_id' => 'required|exists:bibliotheques,id',
                'titre' => 'required|string|max:255',
                'auteur' => 'required|string|max:255',
                'isbn' => 'nullable|string|max:20',
                'editeur' => 'nullable|string|max:255',
                'annee_publication' => 'nullable|integer|min:1900|max:' . date('Y'),
                'categorie' => 'nullable|string|max:100',
                'description' => 'nullable|string',
                'nombre_exemplaires' => 'required|integer|min:1',
                'statut' => 'required|in:actif,inactif,archive',
            ]);

            Ouvrage::create($validated);

            return redirect()->route('ouvrages.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Bibliotheque", "OuvrageController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(Ouvrage $ouvrage)
    {
        try {
            $ouvrage->load('bibliotheque', 'exemplaires');
            $bibliotheques = \Modules\RessourcesLogistique\Entities\Bibliotheque::where('etat', 'actif')
                ->select('id', 'titre_manuel as libelle')->get()->toArray();

            return Inertia::render('RessourcesLogistique::Ouvrages/Show', [
                'ouvrage' => $ouvrage,
                'bibliotheques' => $bibliotheques,
            ]);
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "OuvrageController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(Ouvrage $ouvrage)
    {
        try {
            $bibliotheques = \Modules\RessourcesLogistique\Entities\Bibliotheque::where('etat', 'actif')
                ->select('id', 'titre_manuel as libelle')->get()->toArray();

            return Inertia::render('RessourcesLogistique::Ouvrages/Edit', [
                'ouvrage' => $ouvrage->load('bibliotheque'),
                'bibliotheques' => $bibliotheques,
            ]);
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "OuvrageController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, Ouvrage $ouvrage)
    {
        try {
            $validated = $request->validate([
                'bibliotheque_id' => 'required|exists:bibliotheques,id',
                'titre' => 'required|string|max:255',
                'auteur' => 'required|string|max:255',
                'isbn' => 'nullable|string|max:20',
                'editeur' => 'nullable|string|max:255',
                'annee_publication' => 'nullable|integer|min:1900|max:' . date('Y'),
                'categorie' => 'nullable|string|max:100',
                'description' => 'nullable|string',
                'nombre_exemplaires' => 'required|integer|min:1',
                'statut' => 'required|in:actif,inactif,archive',
            ]);

            $ouvrage->update($validated);

            return redirect()->route('ouvrages.show', $ouvrage)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Bibliotheque", "OuvrageController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(Ouvrage $ouvrage)
    {
        try {
            $ouvrage->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Bibliotheque", "OuvrageController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(Ouvrage $ouvrage)
    {
        try {
            if ($ouvrage->trashed()) {
                $ouvrage->restore();
            } else {
                $ouvrage->delete();
            }

            return redirect()->route('ouvrages.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Bibliotheque", "OuvrageController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
