<?php

namespace Modules\Parametrage\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Parametrage\Entities\Genre;

class GenreController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:genres-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:genres-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:genres-update', ['only' => ['edit', 'update', 'statut']]);
        $this->middleware('permission.check:genres-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Genre::query();

            if ($request->filled('search')) {
                $s = $request->input('search');
                $query->where(function ($q) use ($s) {
                    $q->where('libelle', 'like', "%$s%")
                      ->orWhere('code', 'like', "%$s%");
                });
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->input('etat'));
            }

            $genres = $query->orderBy('ordre')->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::Genres/Index', [
                'title' => 'Genres',
                'genres' => $genres,
                'filters' => $request->only(['search', 'etat']),
            ]);
        } catch (\Throwable $th) {
            log_error('Parametrage', 'GenreController::index', $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function create()
    {
        return Inertia::render('Parametrage::Genres/Create', ['title' => 'Créer un genre']);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:20|unique:genres,code',
                'libelle' => 'required|string|max:100',
                'symbole' => 'nullable|string|max:5',
                'couleur' => 'nullable|string|max:20',
                'ordre' => 'nullable|integer|min:0',
                'etat' => 'required|in:actif,inactif',
            ]);
            $validated['code'] = strtoupper($validated['code']);
            Genre::create($validated);
            return redirect()->route('parametrage.genres.index')
                ->with('success', 'Genre créé avec succès');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error('Parametrage', 'GenreController::store', $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function show(Genre $genre)
    {
        return Inertia::render('Parametrage::Genres/Show', ['title' => 'Détails du genre', 'genre' => $genre]);
    }

    public function edit(Genre $genre)
    {
        return Inertia::render('Parametrage::Genres/Edit', ['title' => 'Modifier le genre', 'genre' => $genre]);
    }

    public function update(Request $request, Genre $genre)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:20|unique:genres,code,' . $genre->id,
                'libelle' => 'required|string|max:100',
                'symbole' => 'nullable|string|max:5',
                'couleur' => 'nullable|string|max:20',
                'ordre' => 'nullable|integer|min:0',
                'etat' => 'required|in:actif,inactif',
            ]);
            $validated['code'] = strtoupper($validated['code']);
            $genre->update($validated);
            return redirect()->route('parametrage.genres.index')
                ->with('success', 'Genre modifié avec succès');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error('Parametrage', 'GenreController::update', $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function destroy(Genre $genre)
    {
        try {
            // Sécurité : empêche la suppression si des apprenants/enseignants l'utilisent
            $usage = $genre->apprenants()->count() + $genre->enseignants()->count();
            if ($usage > 0) {
                return back()->withErrors([
                    '_error' => "Impossible de supprimer ce genre : {$usage} personne(s) l'utilisent.",
                ]);
            }
            $genre->delete();
            return redirect()->route('parametrage.genres.index')
                ->with('success', 'Genre supprimé avec succès');
        } catch (\Throwable $th) {
            log_error('Parametrage', 'GenreController::destroy', $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function statut(Genre $genre)
    {
        try {
            $genre->update(['etat' => $genre->etat === 'actif' ? 'inactif' : 'actif']);
            return back()->with('success', 'Statut modifié');
        } catch (\Throwable $th) {
            log_error('Parametrage', 'GenreController::statut', $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }
}
