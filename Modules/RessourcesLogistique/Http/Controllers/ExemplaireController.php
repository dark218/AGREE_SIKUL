<?php

namespace Modules\RessourcesLogistique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\RessourcesLogistique\Entities\Exemplaire;

class ExemplaireController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:exemplaires-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:exemplaires-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:exemplaires-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:exemplaires-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Exemplaire::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('code_exemplaire', 'like', "%$search%")
                    ->orWhere('numero_serie', 'like', "%$search%");
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $exemplaires = $query->with(['ouvrage'])->paginate(10)->withQueryString()
                ->through(fn ($exemplaire) => [
                    'id' => $exemplaire->id,
                    'code_exemplaire' => $exemplaire->code_exemplaire,
                    'numero_serie' => $exemplaire->numero_serie,
                    'ouvrage' => $exemplaire->ouvrage?->titre,
                    'statut' => $exemplaire->statut,
                ]);

            return Inertia::render('RessourcesLogistique::Exemplaires/Index', [
                'exemplaires' => $exemplaires,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "ExemplaireController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            $ouvrages = \Modules\RessourcesLogistique\Entities\Ouvrage::all(['id', 'titre'])->toArray();

            return Inertia::render('RessourcesLogistique::Exemplaires/Create', [
                'ouvrages' => $ouvrages,
            ]);
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "ExemplaireController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            \Log::info('📝 ExemplaireController::store START - Request data:', $request->all());

            $validated = $request->validate([
                'ouvrage_id' => 'required|exists:ouvrages,id',
                'code_exemplaire' => 'required|string|max:100|unique:exemplaires',
                'numero_serie' => 'nullable|string|max:100',
                'etat' => 'required|in:neuf,bon,use,tres_use,endommagé',
                'localisation' => 'nullable|string|max:255',
                'date_acquisition' => 'required|date',
                'date_derniere_maintenance' => 'nullable|date',
                'statut' => 'required|in:disponible,prete,maintenance,retire',
            ]);

            \Log::info('✅ Validation passed:', $validated);

            $created = Exemplaire::create($validated);
            \Log::info('✅ Exemplaire created:', ['id' => $created->id, 'code' => $created->code_exemplaire]);

            return redirect()->route('exemplaires.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            \Log::warning('❌ Validation error:', $ve->errors());
            throw $ve;
        } catch (\Throwable $th) {
            \Log::error('❌ ExemplaireController::store ERROR:', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);
            log_error("Bibliotheque", "ExemplaireController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(Exemplaire $exemplaire)
    {
        try {
            $exemplaire->load('ouvrage', 'emprunts');
            $ouvrages = \Modules\RessourcesLogistique\Entities\Ouvrage::all(['id', 'titre'])->toArray();

            return Inertia::render('RessourcesLogistique::Exemplaires/Show', [
                'item' => $exemplaire,
                'ouvrages' => $ouvrages,
            ]);
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "ExemplaireController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(Exemplaire $exemplaire)
    {
        try {
            $ouvrages = \Modules\RessourcesLogistique\Entities\Ouvrage::all(['id', 'titre'])->toArray();
            return Inertia::render('RessourcesLogistique::Exemplaires/Edit', [
                'item' => $exemplaire->load('ouvrage'),
                'ouvrages' => $ouvrages,
            ]);
        } catch (\Throwable $th) {
            log_error("Bibliotheque", "ExemplaireController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, Exemplaire $exemplaire)
    {
        try {
            $validated = $request->validate([
                'ouvrage_id' => 'required|exists:ouvrages,id',
                'code_exemplaire' => 'required|string|max:100|unique:exemplaires,code_exemplaire,' . $exemplaire->id,
                'numero_serie' => 'nullable|string|max:100',
                'etat' => 'required|in:neuf,bon,use,tres_use,endommagé',
                'localisation' => 'nullable|string|max:255',
                'date_acquisition' => 'required|date',
                'date_derniere_maintenance' => 'nullable|date',
                'statut' => 'required|in:disponible,prete,maintenance,retire',
            ]);

            $exemplaire->update($validated);

            return redirect()->route('exemplaires.show', $exemplaire)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Bibliotheque", "ExemplaireController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(Exemplaire $exemplaire)
    {
        try {
            $exemplaire->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Bibliotheque", "ExemplaireController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(Exemplaire $exemplaire)
    {
        try {
            if ($exemplaire->trashed()) {
                $exemplaire->restore();
            } else {
                $exemplaire->delete();
            }

            return redirect()->route('exemplaires.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Bibliotheque", "ExemplaireController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
