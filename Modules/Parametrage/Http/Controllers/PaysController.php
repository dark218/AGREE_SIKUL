<?php

namespace Modules\Parametrage\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Parametrage\Entities\Pays;
use Illuminate\Support\Facades\App;

class PaysController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:pays-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:pays-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:pays-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:pays-statut', ['only' => ['statut']]);
    }

    /**
     * Display a listing of pays.
     */
    public function index(Request $request): Response
    {
        try {
            $filters = [
                'code' => $request->input('code'),
                'libelle' => $request->input('libelle'),
            ];

            $query = Pays::query()->orderBy('created_at', 'DESC');

            if (!empty($filters['code'])) {
                $query->where('code', 'LIKE', '%' . $filters['code'] . '%');
            }

            if (!empty($filters['libelle'])) {
                $query->where('libelle', 'LIKE', '%' . $filters['libelle'] . '%');
            }

            $pays = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::Pays/Index', [
                'pays' => $pays,
                'filters' => $filters,
            ]);
        } catch (\Throwable $th) {
            \Log::error("error");
            return redirect()->route('home')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Show the form for creating a new pays.
     */
    public function create(): Response
    {
        try {
            return Inertia::render('Parametrage::Pays/Create');
        } catch (\Throwable $th) {
            \Log::error("error");
            return redirect()->route('parametrage.pays.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Store a newly created pays.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:255|unique:pays,code',
            'code_3_chars' => 'nullable|string|max:3',
            'code_2_chars' => 'nullable|string|max:2',
            'libelle' => 'required|string|max:255',
            'capitale' => 'nullable|string|max:255',
            'nombre' => 'nullable|integer',
            'continent' => 'nullable|string|max:255',
            'etat' => 'nullable|in:actif,inactif',
        ]);

        try {
            Pays::create([
                'code' => $request->code,
                'code_3_chars' => $request->code_3_chars,
                'code_2_chars' => $request->code_2_chars,
                'libelle' => $request->libelle,
                'capitale' => $request->capitale,
                'nombre' => $request->nombre,
                'continent' => $request->continent,
                'etat' => $request->etat ?? 'actif',
                'created_by' => auth()->id(),
            ]);

            return redirect()
                ->route('parametrage.pays.index')
                ->with('success', __('enregistrementsucces'));
        } catch (\Throwable $e) {
            \Log::error("error");
            return redirect()
                ->route('parametrage.pays.create')
                ->with('error', __('Erreur'));
        }
    }

    /**
     * Display the specified pays.
     */
    public function show($id): Response
    {
        try {
            $pays = Pays::findOrFail($id);

            return Inertia::render('Parametrage::Pays/Show', [
                'title' => 'Détails Pays',
                'pays' => $pays,
            ]);
        } catch (\Throwable $th) {
            \Log::error("error");
            return redirect()->route('parametrage.pays.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Show the form for editing the specified pays.
     */
    public function edit($id): Response
    {
        try {
            $pays = Pays::findOrFail($id);

            return Inertia::render('Parametrage::Pays/Edit', [
                'pays' => $pays,
            ]);
        } catch (\Throwable $th) {
            \Log::error("error");
            return redirect()->route('parametrage.pays.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Update the specified pays.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'code' => 'required|string|max:255|unique:pays,code,' . $id,
            'code_3_chars' => 'nullable|string|max:3',
            'code_2_chars' => 'nullable|string|max:2',
            'libelle' => 'required|string|max:255',
            'capitale' => 'nullable|string|max:255',
            'nombre' => 'nullable|integer',
            'continent' => 'nullable|string|max:255',
            'etat' => 'nullable|in:actif,inactif',
        ]);

        try {
            $pays = Pays::findOrFail($id);
            $pays->update([
                'code' => $request->code,
                'code_3_chars' => $request->code_3_chars,
                'code_2_chars' => $request->code_2_chars,
                'libelle' => $request->libelle,
                'capitale' => $request->capitale,
                'nombre' => $request->nombre,
                'continent' => $request->continent,
                'etat' => $request->etat ?? $pays->etat,
                'updated_by' => auth()->id(),
            ]);

            return redirect()
                ->route('parametrage.pays.index')
                ->with('success', __('modifsucces'));
        } catch (\Throwable $e) {
            \Log::error("error");
            return redirect()
                ->route('parametrage.pays.edit', $id)
                ->with('error', __('erreurmaj'));
        }
    }

    /**
     * Toggle pays status (change etat between actif/inactif).
     */
    public function activate($id)
    {
        try {
            $pays = Pays::findOrFail($id);

            // Toggle between actif and inactif
            $newStatus = $pays->etat === 'actif' ? 'inactif' : 'actif';
            $pays->etat = $newStatus;
            $pays->updated_by = auth()->id();
            $pays->save();

            $message = $newStatus === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.pays.index')->with('success', $message . ' avec succès');
        } catch (\Throwable $e) {
            \Log::error("error");
            return redirect()->route('parametrage.pays.index')->with('error', __('Erreur'));
        }
    }
}
