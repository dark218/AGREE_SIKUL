<?php

namespace Modules\SuiviAnalyse\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\SuiviAnalyse\Entities\ModeleRapport;

class ModeleRapportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:modeles-rapports-list',   ['only' => ['index', 'show']]);
        $this->middleware('permission.check:modeles-rapports-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:modeles-rapports-edit',   ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:modeles-rapports-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = ModeleRapport::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('titre', 'like', "%$search%")
                      ->orWhere('code', 'like', "%$search%");
                });
            }

            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }

            $modeles = $query->paginate(10)->withQueryString();

            return Inertia::render('SuiviAnalyse::ModelesRapports/Index', [
                'modeles' => $modeles,
                'filters' => $request->only(['search', 'type']),
            ]);
        } catch (\Throwable $th) {
            log_error("SuiviAnalyse", "ModeleRapportController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('SuiviAnalyse::ModelesRapports/Create');
        } catch (\Throwable $th) {
            log_error("SuiviAnalyse", "ModeleRapportController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code'        => 'required|string|max:100|unique:modeles_rapports,code',
                'titre'       => 'required|string|max:255',
                'description' => 'nullable|string',
                'type'        => 'required|in:pdf,excel,csv,html',
                'parametres'  => 'nullable|array',
            ]);

            ModeleRapport::create($validated);

            return redirect()->route('suivi-analyse.modeles-rapports.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("SuiviAnalyse", "ModeleRapportController::store", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()])->withInput();
        }
    }

    public function show(ModeleRapport $modeleRapport)
    {
        try {
            $modeleRapport->load('rapports');

            return Inertia::render('SuiviAnalyse::ModelesRapports/Show', [
                'modele' => $modeleRapport,
            ]);
        } catch (\Throwable $th) {
            log_error("SuiviAnalyse", "ModeleRapportController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(ModeleRapport $modeleRapport)
    {
        try {
            return Inertia::render('SuiviAnalyse::ModelesRapports/Edit', [
                'modele' => $modeleRapport,
            ]);
        } catch (\Throwable $th) {
            log_error("SuiviAnalyse", "ModeleRapportController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, ModeleRapport $modeleRapport)
    {
        try {
            $validated = $request->validate([
                'code'        => 'required|string|max:100|unique:modeles_rapports,code,' . $modeleRapport->id,
                'titre'       => 'required|string|max:255',
                'description' => 'nullable|string',
                'type'        => 'required|in:pdf,excel,csv,html',
                'parametres'  => 'nullable|array',
            ]);

            $modeleRapport->update($validated);

            return redirect()->route('suivi-analyse.modeles-rapports.show', $modeleRapport)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("SuiviAnalyse", "ModeleRapportController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()])->withInput();
        }
    }

    public function destroy(ModeleRapport $modeleRapport)
    {
        try {
            $modeleRapport->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("SuiviAnalyse", "ModeleRapportController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(ModeleRapport $modeleRapport)
    {
        try {
            if ($modeleRapport->trashed()) {
                $modeleRapport->restore();
            } else {
                $modeleRapport->delete();
            }

            return redirect()->route('suivi-analyse.modeles-rapports.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("SuiviAnalyse", "ModeleRapportController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
