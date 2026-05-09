<?php

namespace Modules\Business\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Business\Entities\CompteBancaireMarchand;
use Modules\Business\Entities\Marchand;
use Modules\Parametrage\Entities\Banque;

class CompteBancaireMarchandController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:compte-bancaire-marchand-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:compte-bancaire-marchand-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:compte-bancaire-marchand-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:compte-bancaire-marchand-statut', ['only' => ['statut','toggleActive']]);
    }

    /**
     * Display a listing of comptes bancaires marchand.
     */
    public function index(Request $request): Response
    {
        try {
            $user = auth()->user();
            $marchand = $user->marchand;
            $paysCurrent = $user->pays;


            $filters = [
                'nom_compte' => $request->input('nom_compte'),
                'numero_compte' => $request->input('numero_compte'),
                'marchand_id' => $request->input('marchand_id'),
                'pays_id' => $request->input('pays_id'),
                'banque_id' => $request->input('banque_id'),
            ];

            $query = CompteBancaireMarchand::with(['marchand', 'banque'])->orderBy('created_at', 'DESC');

            if ($marchand) {
                $query->where('marchand_id', $marchand->id);
            }

            if (!empty($filters['nom_compte'])) {
                $query->where('nom_compte', 'LIKE', '%' . $filters['nom_compte'] . '%');
            }

            if (!empty($filters['numero_compte'])) {
                $query->where('numero_compte', 'LIKE', '%' . $filters['numero_compte'] . '%');
            }

            if (!empty($filters['marchand_id'])) {
                $query->where('marchand_id', $filters['marchand_id']);
            }

            if (!empty($filters['banque_id'])) {
                $query->where('banque_id', $filters['banque_id']);
            }
            if (!empty($filters['pays_id'])) {
                $query->whereHas('banque', function ($q) use ($filters) {
                    $q->where('pays_id', $filters['pays_id']);
                });
            } elseif (!is_null($paysCurrent)) {
                $query->whereHas('banque', function ($q) use ($paysCurrent) {
                    $q->where('pays_id', $paysCurrent->id);
                });
            }

            $comptes = $query->paginate(10)->withQueryString();
            $marchands = Marchand::select('marchands.id', 'raison_sociale')
                ->join('users', 'marchands.proprietaire_id', '=', 'users.id')
                ->where('users.statut', config('appconstants.user_statut.actif'))
                ->when($paysCurrent, fn($q) => $q->where('users.pays_id', $paysCurrent->id))
                ->orderBy('raison_sociale')
                ->get();

            if ($paysCurrent){
                $banques = Banque::where('is_active', true)
                ->whereHas('pays', function ($query) use ($paysCurrent) {
                    $query->where('id', $paysCurrent->id);
                })->get();
            }else{
                $banques = Banque::where('is_active', true)->get();
            }

            return Inertia::render('Business::CompteBancaireMarchand/Index', [
                'comptes' => $comptes,
                'filters' => $filters,
                'marchands' => $marchands,
                'banques' => $banques,
                'is_marchand' => $marchand !== null,
                'marchand_current' => $marchand,
            ]);
        } catch (\Throwable $th) {
            log_error("CompteBancaireMarchand", "index", $th->getMessage());
            return redirect()->route('home')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Show the form for creating a new compte bancaire marchand.
     */
    public function create(): Response
    {
        try {
            $user = auth()->user();
            $isMarchand = $user->marchand !== null;
            $marchandCurrent = $user->marchand;
            $paysCurrent = $user->pays;

            $marchands = $isMarchand ?
                collect([$marchandCurrent]) :
                Marchand::select('marchands.id', 'raison_sociale')
                    ->join('users', 'marchands.proprietaire_id', '=', 'users.id')
                    ->where('users.statut', config('appconstants.user_statut.actif'))
                    ->when($paysCurrent, fn($q) => $q->where('users.pays_id', $paysCurrent->id))
                    ->orderBy('raison_sociale')
                    ->get();

            if ($paysCurrent){
                $banques = Banque::where('is_active', true)
                    ->whereHas('pays', function ($query) use ($paysCurrent) {
                        $query->where('id', $paysCurrent->id);
                    })->get();
            }else{
                $banques = Banque::where('is_active', true)->get();
            }

            return Inertia::render('Business::CompteBancaireMarchand/Create', [
                'marchands' => $marchands,
                'banques' => $banques,
                'is_marchand' => $isMarchand,
                'marchand_current' => $marchandCurrent,
            ]);
        } catch (\Throwable $th) {
            log_error("CompteBancaireMarchand", "create", $th->getMessage());
            return redirect()->route('compte-bancaire-marchand.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Store a newly created compte bancaire marchand.
     */
    public function store(Request $request)
    {
        $request->validate([
            'marchand_id' => 'required|exists:marchands,id',
            'banque_id' => 'required|exists:banques,id',
            'nom_compte' => 'required|string|max:255',
            'numero_compte' => 'required|string|max:255',
            'iban' => 'nullable|string|max:255',
            'bic_swift' => 'nullable|string|max:50',
            'is_principal' => 'boolean',
            'is_active' => 'boolean',
            'meta_json' => 'nullable|array',
        ]);

        try {
            CompteBancaireMarchand::create($request->only([
                'marchand_id', 'banque_id', 'nom_compte', 'numero_compte',
                'iban', 'bic_swift', 'is_principal', 'is_active', 'meta_json'
            ]));

            return redirect()
                ->route('compte-bancaire-marchand.index')
                ->with('success', __('enregistrementsucces'));
        } catch (\Throwable $e) {
            log_error("CompteBancaireMarchand", "store", $e->getMessage());
            return redirect()
                ->route('compte-bancaire-marchand.create')
                ->with('error', __('Erreur'));
        }
    }

    /**
     * Display the specified compte bancaire marchand.
     */
    public function show($id): Response
    {
        try {
            $compte = CompteBancaireMarchand::with(['marchand.proprietaire', 'banque.pays'])->findOrFail($id);

            return Inertia::render('Business::CompteBancaireMarchand/Show', [
                'compte' => $compte,
            ]);
        } catch (\Throwable $th) {
            log_error("CompteBancaireMarchand", "show", $th->getMessage());
            return redirect()->route('compte-bancaire-marchand.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Show the form for editing the specified compte bancaire marchand.
     */
    public function edit($id): Response
    {
        try {
            $user = auth()->user();
            $isMarchand = $user->marchand !== null;
            $marchandCurrent = $user->marchand;
            $paysCurrent = $user->pays;

            $compte = CompteBancaireMarchand::findOrFail($id);

            $marchands = $isMarchand ?
                collect([$marchandCurrent]) :
                Marchand::select('marchands.id', 'raison_sociale')
                    ->join('users', 'marchands.proprietaire_id', '=', 'users.id')
                    ->where('users.statut', config('appconstants.user_statut.actif'))
                    ->when($paysCurrent, fn($q) => $q->where('users.pays_id', $paysCurrent->id))
                    ->orderBy('raison_sociale')
                    ->get();

            if ($paysCurrent){
                $banques = Banque::where('is_active', true)
                    ->whereHas('pays', function ($query) use ($paysCurrent) {
                        $query->where('id', $paysCurrent->id);
                    })->get();
            }else{
                $banques = Banque::where('is_active', true)->get();
            }

            return Inertia::render('Business::CompteBancaireMarchand/Edit', [
                'compte' => $compte,
                'marchands' => $marchands,
                'banques' => $banques,
                'is_marchand' => $isMarchand,
                'marchand_current' => $marchandCurrent,
            ]);
        } catch (\Throwable $th) {
            log_error("CompteBancaireMarchand", "edit", $th->getMessage());
            return redirect()->route('compte-bancaire-marchand.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Update the specified compte bancaire marchand.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'marchand_id' => 'required|exists:marchands,id',
            'banque_id' => 'required|exists:banques,id',
            'nom_compte' => 'required|string|max:255',
            'numero_compte' => 'required|string|max:255',
            'iban' => 'nullable|string|max:255',
            'bic_swift' => 'nullable|string|max:50',
            'is_principal' => 'boolean',
            'is_active' => 'boolean',
            'meta_json' => 'nullable|array',
        ]);

        try {
            $compte = CompteBancaireMarchand::findOrFail($id);
            $compte->update($request->only([
                'marchand_id', 'banque_id', 'nom_compte', 'numero_compte',
                'iban', 'bic_swift', 'is_principal', 'is_active', 'meta_json'
            ]));

            return redirect()
                ->route('compte-bancaire-marchand.index')
                ->with('success', __('modifsucces'));
        } catch (\Throwable $e) {
            log_error("CompteBancaireMarchand", "update", $e->getMessage());
            return redirect()
                ->route('compte-bancaire-marchand.edit', $id)
                ->with('error', __('erreurmaj'));
        }
    }

    /**
     * Toggle compte bancaire marchand status (soft delete/restore).
     */
    public function statut($id)
    {
        try {
            $compte = CompteBancaireMarchand::withTrashed()->findOrFail($id);

            if ($compte->trashed()) {
                $compte->restore();
                $message = __('restaurationsucces');
            } else {
                $compte->delete();
                $message = __('suppressionsucces');
            }

            return redirect()->route('compte-bancaire-marchand.index')->with('success', $message);
        } catch (\Throwable $e) {
            log_error("CompteBancaireMarchand", "statut", $e->getMessage());
            return redirect()->route('compte-bancaire-marchand.index')->with('error', __('Erreur'));
        }
    }

    /**
     * Toggle compte bancaire marchand active status.
     */
    public function toggleActive($id)
    {
        try {
            $compte = CompteBancaireMarchand::findOrFail($id);
            $compte->toggleActive();

            $message = $compte->is_active ? __('Compte activé avec succès') : __('Compte désactivé avec succès');

            return redirect()->route('compte-bancaire-marchand.index')->with('success', $message);
        } catch (\Throwable $e) {
            log_error("CompteBancaireMarchand", "toggleActive", $e->getMessage());
            return redirect()->route('compte-bancaire-marchand.index')->with('error', __('Erreur'));
        }
    }
}
