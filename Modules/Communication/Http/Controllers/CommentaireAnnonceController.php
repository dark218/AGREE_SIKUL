<?php

namespace Modules\Communication\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Communication\Entities\CommentaireAnnonce;

class CommentaireAnnonceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:commentaires-annonces-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:commentaires-annonces-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:commentaires-annonces-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:commentaires-annonces-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = CommentaireAnnonce::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('contenu', 'like', "%$search%");
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $commentaires = $query->with(['annonce', 'auteur'])->paginate(10)->withQueryString();

            return Inertia::render('Communication::CommentairesAnnonces/Index', [
                'commentaires' => $commentaires,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Communication", "CommentaireAnnonceController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Communication::CommentairesAnnonces/Create');
        } catch (\Throwable $th) {
            log_error("Communication", "CommentaireAnnonceController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'annonce_id' => 'required|exists:annonces,id',
                'auteur_id' => 'required|exists:users,id',
                'contenu' => 'required|string',
                'date_commentaire' => 'required|date',
                'statut' => 'required|in:en_attente,approuve,rejete',
            ]);

            CommentaireAnnonce::create($validated);

            return redirect()->route('commentaires-annonces.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Communication", "CommentaireAnnonceController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(CommentaireAnnonce $commentaireAnnonce)
    {
        try {
            $commentaireAnnonce->load('annonce', 'auteur');

            return Inertia::render('Communication::CommentairesAnnonces/Show', [
                'commentaire' => $commentaireAnnonce,
            ]);
        } catch (\Throwable $th) {
            log_error("Communication", "CommentaireAnnonceController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(CommentaireAnnonce $commentaireAnnonce)
    {
        try {
            return Inertia::render('Communication::CommentairesAnnonces/Edit', [
                'commentaire' => $commentaireAnnonce->load('annonce', 'auteur'),
            ]);
        } catch (\Throwable $th) {
            log_error("Communication", "CommentaireAnnonceController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, CommentaireAnnonce $commentaireAnnonce)
    {
        try {
            $validated = $request->validate([
                'annonce_id' => 'required|exists:annonces,id',
                'auteur_id' => 'required|exists:users,id',
                'contenu' => 'required|string',
                'date_commentaire' => 'required|date',
                'statut' => 'required|in:en_attente,approuve,rejete',
            ]);

            $commentaireAnnonce->update($validated);

            return redirect()->route('commentaires-annonces.show', $commentaireAnnonce)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Communication", "CommentaireAnnonceController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(CommentaireAnnonce $commentaireAnnonce)
    {
        try {
            $commentaireAnnonce->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Communication", "CommentaireAnnonceController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(CommentaireAnnonce $commentaireAnnonce)
    {
        try {
            if ($commentaireAnnonce->trashed()) {
                $commentaireAnnonce->restore();
            } else {
                $commentaireAnnonce->delete();
            }

            return redirect()->route('commentaires-annonces.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Communication", "CommentaireAnnonceController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
