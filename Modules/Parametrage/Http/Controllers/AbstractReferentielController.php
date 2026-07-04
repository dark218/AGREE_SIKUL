<?php

namespace Modules\Parametrage\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Contrôleur abstrait factorisé pour les 8 référentiels Paramétrage
 * simples (code, libelle, ordre, etat) : TypeContrat, StatutEmploye,
 * SituationMatrimoniale, LienParente, Civilite, StatutApprenant,
 * TypeInscription, GroupeSanguin, Langue.
 *
 * Les enfants doivent définir :
 *   • static::MODEL      = classe Eloquent
 *   • static::VIEW_DIR   = 'Parametrage::TypesContrats' (chemin Inertia)
 *   • static::ROUTE_NAME = 'parametrage.types_contrats' (prefix)
 *   • static::TITLE      = titre affiché
 */
abstract class AbstractReferentielController extends Controller
{
    protected const MODEL = null;
    protected const VIEW_DIR = null;
    protected const ROUTE_NAME = null;
    protected const TITLE = 'Référentiel';

    public function index(Request $request)
    {
        try {
            $query = static::MODEL::query();

            if ($request->filled('search')) {
                $s = $request->input('search');
                $query->where(fn($q) => $q
                    ->where('libelle', 'like', "%$s%")
                    ->orWhere('code', 'like', "%$s%"));
            }
            if ($request->filled('etat')) {
                $query->where('etat', $request->input('etat'));
            }

            $items = $query->orderBy('ordre')->paginate(10)->withQueryString();

            return Inertia::render(static::VIEW_DIR . '/Index', [
                'title' => static::TITLE,
                'items' => $items,
                'filters' => $request->only(['search', 'etat']),
            ]);
        } catch (\Throwable $th) {
            log_error('Parametrage', static::class . '::index', $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function create()
    {
        return Inertia::render(static::VIEW_DIR . '/Create', ['title' => 'Créer — ' . static::TITLE]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code'    => 'required|string|max:50|unique:' . (new (static::MODEL))->getTable() . ',code',
                'libelle' => 'required|string|max:150',
                'ordre'   => 'nullable|integer|min:0',
                'etat'    => 'required|in:actif,inactif',
            ]);
            $validated['code'] = strtoupper($validated['code']);
            static::MODEL::create($validated);
            return redirect()->route(static::ROUTE_NAME . '.index')
                ->with('success', static::TITLE . ' créé avec succès');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error('Parametrage', static::class . '::store', $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function show($item)
    {
        $model = static::MODEL::findOrFail($item);
        return Inertia::render(static::VIEW_DIR . '/Show', [
            'title' => 'Détails — ' . static::TITLE,
            'item' => $model,
        ]);
    }

    public function edit($item)
    {
        $model = static::MODEL::findOrFail($item);
        return Inertia::render(static::VIEW_DIR . '/Edit', [
            'title' => 'Modifier — ' . static::TITLE,
            'item' => $model,
        ]);
    }

    public function update(Request $request, $item)
    {
        try {
            $model = static::MODEL::findOrFail($item);
            $validated = $request->validate([
                'code'    => 'required|string|max:50|unique:' . $model->getTable() . ',code,' . $model->id,
                'libelle' => 'required|string|max:150',
                'ordre'   => 'nullable|integer|min:0',
                'etat'    => 'required|in:actif,inactif',
            ]);
            $validated['code'] = strtoupper($validated['code']);
            $model->update($validated);
            return redirect()->route(static::ROUTE_NAME . '.index')
                ->with('success', static::TITLE . ' modifié avec succès');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error('Parametrage', static::class . '::update', $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function destroy($item)
    {
        try {
            $model = static::MODEL::findOrFail($item);
            $model->delete();
            return redirect()->route(static::ROUTE_NAME . '.index')
                ->with('success', static::TITLE . ' supprimé');
        } catch (\Throwable $th) {
            log_error('Parametrage', static::class . '::destroy', $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function statut($item)
    {
        try {
            $model = static::MODEL::findOrFail($item);
            $model->update(['etat' => $model->etat === 'actif' ? 'inactif' : 'actif']);
            return back()->with('success', 'Statut modifié');
        } catch (\Throwable $th) {
            log_error('Parametrage', static::class . '::statut', $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }
}
