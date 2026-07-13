<?php

namespace Modules\Finances\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Finances\Entities\GroupeCompte;
use Modules\Finances\Entities\PlanCompte;

class PlanCompteController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:plan-comptes-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:plan-comptes-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:plan-comptes-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:plan-comptes-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        try {
            $query = PlanCompte::query()->with('compteParent:id,numero_compte,libelle_compte');

            if ($request->filled('search')) {
                $s = $request->input('search');
                $query->where(function ($q) use ($s) {
                    $q->where('numero_compte', 'like', "%$s%")
                      ->orWhere('libelle_compte', 'like', "%$s%");
                });
            }
            if ($request->filled('etat')) {
                $query->where('etat', $request->input('etat'));
            }

            $comptes = $query->orderBy('numero_compte')->paginate(25)->withQueryString();

            $comptes->setCollection($comptes->getCollection()->map(fn ($c) => [
                'id' => $c->id,
                'numero_compte' => $c->numero_compte,
                'libelle_compte' => $c->libelle_compte,
                'niveau' => strlen((string) $c->numero_compte),
                'parent' => $c->compteParent ? ($c->compteParent->numero_compte . ' — ' . $c->compteParent->libelle_compte) : '—',
                'etat' => $c->etat,
            ]));

            return Inertia::render('Finances::PlanComptes/Index', [
                'title' => 'Plan comptable OHADA',
                'comptes' => $comptes,
                'filters' => $request->only(['search', 'etat']),
            ]);
        } catch (\Throwable $th) {
            log_error('Finances', 'PlanCompteController::index', $th->getMessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    private function options(): array
    {
        // §BUG-FIX : la colonne réelle sur `groupes_comptes` est `libelle_groupes`
        // (voir migration 2026_03_19_create_finances_modules_table.php). Le
        // controller demandait `libelle` inexistant → SQLSTATE[42S22] → 500 sur
        // /finances/plan-comptes/create et /edit.
        return [
            'groupes' => GroupeCompte::orderBy('libelle_groupes')->get(['id', 'libelle_groupes'])
                ->map(fn ($g) => ['id' => $g->id, 'libelle' => $g->libelle_groupes])->toArray(),
            'comptesParents' => PlanCompte::orderBy('numero_compte')->get(['id', 'numero_compte', 'libelle_compte'])
                ->map(fn ($c) => ['id' => $c->id, 'libelle' => $c->numero_compte . ' — ' . $c->libelle_compte])->toArray(),
        ];
    }

    public function create()
    {
        return Inertia::render('Finances::PlanComptes/Create', array_merge($this->options(), ['title' => 'Nouveau compte']));
    }

    private function rules($id = null): array
    {
        return [
            'numero_compte' => 'required|string|max:50|unique:plan_comptes,numero_compte' . ($id ? ",$id" : ''),
            'libelle_compte' => 'required|string|max:255',
            'libelle_court' => 'nullable|string|max:100',
            'groupe_comptes_id' => 'nullable|exists:groupe_comptes,id',
            'compte_parent_id' => 'nullable|exists:plan_comptes,id',
            'etat' => 'nullable|in:actif,inactif',
        ];
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->rules());
            $validated['etat'] = $validated['etat'] ?? 'actif';
            PlanCompte::create($validated);
            return redirect()->route('finances.plan-comptes.index')->with('success', 'Compte créé avec succès');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            log_error('Finances', 'PlanCompteController::store', $th->getMessage());
            return back()->withInput()->with('error', 'Erreur lors de la création');
        }
    }

    public function edit(PlanCompte $planCompte)
    {
        return Inertia::render('Finances::PlanComptes/Edit', array_merge($this->options(), [
            'title' => 'Modifier le compte',
            'compte' => $planCompte,
        ]));
    }

    public function update(Request $request, PlanCompte $planCompte)
    {
        try {
            $validated = $request->validate($this->rules($planCompte->id));
            $validated['etat'] = $validated['etat'] ?? $planCompte->etat;
            $planCompte->update($validated);
            return redirect()->route('finances.plan-comptes.index')->with('success', 'Compte modifié avec succès');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            log_error('Finances', 'PlanCompteController::update', $th->getMessage());
            return back()->withInput()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(PlanCompte $planCompte)
    {
        try {
            $planCompte->delete();
            return back()->with('success', 'Compte supprimé avec succès');
        } catch (\Throwable $th) {
            log_error('Finances', 'PlanCompteController::destroy', $th->getMessage());
            return back()->with('error', 'Erreur lors de la suppression');
        }
    }
}
