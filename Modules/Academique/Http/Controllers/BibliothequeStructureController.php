<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academique\Entities\BibliothequeStructure;
use Modules\Parametrage\Entities\Campus;

class BibliothequeStructureController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:bibliotheque-structures-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:bibliotheque-structures-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:bibliotheque-structures-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:bibliotheque-structures-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = BibliothequeStructure::with('campus');

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('libelle', 'like', "%$search%")
                        ->orWhere('code', 'like', "%$search%")
                        ->orWhere('localisation', 'like', "%$search%")
                        ->orWhere('responsable', 'like', "%$search%");
                });
            }

            if ($request->filled('campus_id')) {
                $query->where('campus_id', $request->input('campus_id'));
            }

            if ($request->filled('statut_disponibilite')) {
                $query->where('statut_disponibilite', $request->input('statut_disponibilite'));
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->input('etat'));
            }

            $structures = $query->paginate(10)->withQueryString();

            return Inertia::render('Academique::BibliothequeStructures/Index', array_merge($this->lookups(), [
                'structures' => $structures,
                'filters' => $request->only(['search', 'campus_id', 'statut_disponibilite', 'etat']),
            ]));
        } catch (\Throwable $th) {
            log_error('Academique', 'BibliothequeStructureController::index', $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Academique::BibliothequeStructures/Create', $this->lookups());
        } catch (\Throwable $th) {
            log_error('Academique', 'BibliothequeStructureController::create', $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->rules());
            $validated['creation_username'] = auth()->user()->name ?? 'system';
            BibliothequeStructure::create($validated);

            return redirect()->route('academique.bibliotheque-structures.index')
                ->with('success', __('messages.created_successfully'));
        } catch (\Throwable $th) {
            log_error('Academique', 'BibliothequeStructureController::store', $th->getMessage());
            return back()->withErrors(['error' => $th->getMessage()])->withInput();
        }
    }

    public function show(BibliothequeStructure $bibliothequeStructure)
    {
        try {
            $bibliothequeStructure->load('campus');

            return Inertia::render('Academique::BibliothequeStructures/Show', array_merge($this->lookups(), [
                'structure' => $bibliothequeStructure,
            ]));
        } catch (\Throwable $th) {
            log_error('Academique', 'BibliothequeStructureController::show', $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(BibliothequeStructure $bibliothequeStructure)
    {
        try {
            $bibliothequeStructure->load('campus');

            return Inertia::render('Academique::BibliothequeStructures/Edit', array_merge($this->lookups(), [
                'structure' => $bibliothequeStructure,
            ]));
        } catch (\Throwable $th) {
            log_error('Academique', 'BibliothequeStructureController::edit', $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, BibliothequeStructure $bibliothequeStructure)
    {
        try {
            $validated = $request->validate($this->rules());
            $validated['modification_username'] = auth()->user()->name ?? 'system';
            $bibliothequeStructure->update($validated);

            return redirect()->route('academique.bibliotheque-structures.index')
                ->with('success', __('messages.updated_successfully'));
        } catch (\Throwable $th) {
            log_error('Academique', 'BibliothequeStructureController::update', $th->getMessage());
            return back()->withErrors(['error' => $th->getMessage()])->withInput();
        }
    }

    public function destroy(BibliothequeStructure $bibliothequeStructure)
    {
        try {
            $bibliothequeStructure->delete();
            return back()->with('success', __('messages.deleted_successfully'));
        } catch (\Throwable $th) {
            log_error('Academique', 'BibliothequeStructureController::destroy', $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(BibliothequeStructure $bibliothequeStructure)
    {
        try {
            $bibliothequeStructure->etat = $bibliothequeStructure->etat === 'actif' ? 'inactif' : 'actif';
            $bibliothequeStructure->save();
            return back()->with('success', __('messages.status_changed'));
        } catch (\Throwable $th) {
            log_error('Academique', 'BibliothequeStructureController::statut', $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    private function lookups(): array
    {
        return [
            'campuses' => Campus::where('statut', 'actif')->orderBy('nom')->get(['id', 'nom']),
        ];
    }

    private function rules(): array
    {
        return [
            'code' => 'nullable|string|max:100',
            'libelle' => 'required|string|max:255',
            'localisation' => 'nullable|string|max:255',
            'campus_id' => 'nullable|exists:campuses,id',
            'responsable' => 'nullable|string|max:255',
            'statut_disponibilite' => 'required|in:disponible,indisponible,maintenance',
            'etat' => 'required|in:actif,inactif',
        ];
    }
}
