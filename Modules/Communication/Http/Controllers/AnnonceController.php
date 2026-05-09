<?php

namespace Modules\Communication\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Communication\Entities\Annonce;

class AnnonceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:annonces-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:annonces-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:annonces-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:annonces-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Annonce::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('titre', 'like', "%$search%")
                    ->orWhere('contenu', 'like', "%$search%");
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $annonces = $query->with(['auteur'])->paginate(10)->withQueryString();

            // Force serialization of etat accessor for Inertia
            $annonces->through(fn ($annonce) => [
                'id' => $annonce->id,
                'auteur_id' => $annonce->auteur_id,
                'titre' => $annonce->titre,
                'contenu' => $annonce->contenu,
                'date_publication' => $annonce->date_publication,
                'date_expiration' => $annonce->date_expiration,
                'statut' => $annonce->statut,
                'etat' => $annonce->etat, // Include etat accessor
                'cibles' => $annonce->cibles,
                'auteur' => $annonce->auteur,
                'created_at' => $annonce->created_at,
                'updated_at' => $annonce->updated_at,
            ]);

            return Inertia::render('Communication::Annonces/Index', [
                'annonces' => $annonces,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Communication", "AnnonceController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Communication::Annonces/Create');
        } catch (\Throwable $th) {
            log_error("Communication", "AnnonceController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            \Log::info('=== AnnonceController::store START ===');
            \Log::info('Request data:', $request->all());

            $validated = $request->validate([
                'titre' => 'required|string|max:255',
                'contenu' => 'required|string',
                'date_publication' => 'nullable|date',
                'date_fin_publication' => 'nullable|date',
                'statut' => 'required|in:active,inactive,archive',
            ]);

            \Log::info('✓ Validation passed', $validated);

            // Add auteur_id and default values (matching database schema)
            $validated['auteur_id'] = auth()->user()->id;
            $validated['creation_username'] = auth()->user()->name;

            // Map date_fin_publication to date_expiration
            if (isset($validated['date_fin_publication'])) {
                $validated['date_expiration'] = $validated['date_fin_publication'];
                unset($validated['date_fin_publication']);
            }

            \Log::info('✓ User ID added:', ['auteur_id' => $validated['auteur_id']]);

            if (!isset($validated['date_publication']) || empty($validated['date_publication'])) {
                $validated['date_publication'] = now();
            }

            \Log::info('✓ Final data to create:', $validated);

            $annonce = Annonce::create($validated);

            \Log::info('✓ Annonce created successfully', ['id' => $annonce->id]);

            return redirect()->route('annonces.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            \Log::error('❌ AnnonceController::store ERROR:', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString()
            ]);
            log_error("Communication", "AnnonceController::store", $th->getMessage());
            return back()->with('error', 'Erreur: ' . $th->getMessage());
        }
    }

    public function show(Annonce $annonce)
    {
        try {
            $annonce->load('auteur', 'commentaires');

            return Inertia::render('Communication::Annonces/Show', [
                'item' => $annonce,
            ]);
        } catch (\Throwable $th) {
            log_error("Communication", "AnnonceController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(Annonce $annonce)
    {
        try {
            return Inertia::render('Communication::Annonces/Edit', [
                'item' => $annonce->load('auteur'),
            ]);
        } catch (\Throwable $th) {
            log_error("Communication", "AnnonceController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, Annonce $annonce)
    {
        try {
            \Log::info('=== AnnonceController::update START ===');
            \Log::info('Request data:', $request->all());

            $validated = $request->validate([
                'titre' => 'required|string|max:255',
                'contenu' => 'required|string',
                'date_publication' => 'nullable|date',
                'date_fin_publication' => 'nullable|date',
                'statut' => 'required|in:active,inactive,archive',
            ]);

            \Log::info('✓ Validation passed', $validated);

            // Map date_fin_publication to date_expiration
            if (isset($validated['date_fin_publication'])) {
                $validated['date_expiration'] = $validated['date_fin_publication'];
                unset($validated['date_fin_publication']);
            }

            $validated['modification_username'] = auth()->user()->name;

            \Log::info('✓ Final data to update:', $validated);

            $annonce->update($validated);

            \Log::info('✓ Annonce updated successfully', ['id' => $annonce->id]);

            return redirect()->route('annonces.index')
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            \Log::error('❌ AnnonceController::update ERROR:', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString()
            ]);
            log_error("Communication", "AnnonceController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(Annonce $annonce)
    {
        try {
            $annonce->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Communication", "AnnonceController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(Annonce $annonce)
    {
        try {
            if ($annonce->trashed()) {
                $annonce->restore();
            } else {
                $annonce->delete();
            }

            return redirect()->route('annonces.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Communication", "AnnonceController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
