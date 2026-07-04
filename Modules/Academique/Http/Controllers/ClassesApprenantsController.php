<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Parametrage\Entities\Classe;
use Modules\Academique\Entities\Apprenant;

class ClassesApprenantsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:classes-apprenants-list', ['only' => ['index']]);
    }

    public function index(Request $request): Response
    {
        try {
            // Get all classes with their apprenants
            $classes = Classe::with([
                'apprenants' => function ($query) {
                    $query->whereNull('deleted_at')
                          ->orderBy('matricule')
                          ->select([
                              'apprenants.id',
                              'apprenants.classe_id',
                              'apprenants.matricule',
                              'apprenants.prenoms',
                              'apprenants.nom',
                              'apprenants.sexe',
                              'apprenants.date_naissance',
                              'apprenants.statut'
                          ]);
                }
            ])
            ->whereNull('deleted_at')
            ->orderBy('nom')
            ->get();

            // Filter by search if provided
            if ($request->filled('search')) {
                $search = $request->search;
                $classes = $classes->filter(function ($classe) use ($search) {
                    // Search in class name
                    if (stripos($classe->nom, $search) !== false) {
                        return true;
                    }
                    // Search in apprenants
                    return $classe->apprenants->some(function ($apprenant) use ($search) {
                        return stripos($apprenant->matricule, $search) !== false ||
                               stripos($apprenant->prenoms, $search) !== false ||
                               stripos($apprenant->nom, $search) !== false;
                    });
                })->values();
            }

            // Filter by classe_id if provided
            if ($request->filled('classe_id')) {
                $classes = $classes->where('id', $request->classe_id);
            }

            // Serialize for Inertia
            $classesData = $classes->map(function ($classe) {
                return [
                    'id' => $classe->id,
                    'nom' => $classe->nom,
                    'apprenants' => $classe->apprenants ? $classe->apprenants->map(function ($apprenant) {
                        return [
                            'id' => $apprenant->id,
                            'matricule' => $apprenant->matricule,
                            'prenoms' => $apprenant->prenoms,
                            'nom' => $apprenant->nom,
                            'sexe' => $apprenant->sexe,
                            'date_naissance' => $apprenant->date_naissance ? $apprenant->date_naissance->format('Y-m-d') : null,
                            'statut' => $apprenant->statut,
                        ];
                    })->toArray() : [],
                ];
            });

            \Log::info('📋 ClassesApprenants index - Total classes:', ['count' => $classesData->count()]);
            \Log::info('📋 ClassesApprenants final data:', $classesData->toArray());

            return Inertia::render('Academique::ClassesApprenants/Index', [
                'classes' => ['data' => $classesData->toArray()],
                'allClasses' => Classe::select('id', 'nom as libelle')
                    ->whereNull('deleted_at')
                    ->orderBy('nom')
                    ->get(),
                'filters' => $request->only(['search', 'classe_id']),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "ClassesApprenantsController::index", $th->getMessage());
            return Inertia::render('Academique::ClassesApprenants/Index', [
                'classes' => ['data' => []],
                'allClasses' => [],
                'filters' => [],
            ]);
        }
    }

    public function create(): Response
    {
        return Inertia::render('Academique::ClassesApprenants/Create', [
            'apprenants' => Apprenant::with(['classe.niveau', 'section', 'cycle', 'ecole.institution', 'campus', 'anneeScolaire'])
                ->whereNull('deleted_at')
                ->orderBy('nom')
                ->get()
                ->toArray(),
            'classes' => Classe::with(['niveau', 'section', 'cycle', 'ecole', 'anneeScolaire'])
                ->whereNull('deleted_at')
                ->orderBy('nom')
                ->get()
                ->toArray(),
            'anneesScolaires' => \Modules\Parametrage\Entities\AnneeScolaire::select('id', 'libelle')
                ->where('etat', 'actif')
                ->orderBy('libelle', 'desc')
                ->get()
                ->toArray(),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'apprenant_id' => 'required|exists:apprenants,id',
                'classe_id' => 'required|exists:classes,id',
            ]);

            Apprenant::findOrFail($validated['apprenant_id'])
                ->update(['classe_id' => $validated['classe_id']]);

            return redirect()->route('academique.classes_apprenants.index')
                ->with('success', 'Apprenant affecté à la classe avec succès');
        } catch (\Throwable $th) {
            log_error("Academique", "ClassesApprenantsController::store", $th->getMessage());
            return redirect()->back()->with('error', 'Une erreur s\'est produite');
        }
    }

    public function show(Apprenant $apprenant): Response
    {
        try {
            $apprenant = $apprenant->load([
                'classe.niveau',
                'section',
                'cycle',
                'ecole.institution',
                'campus',
                'anneeScolaire'
            ]);

            return Inertia::render('Academique::ClassesApprenants/Show', [
                'apprenant' => $apprenant,
                'apprenants' => Apprenant::with(['classe.niveau', 'section', 'cycle', 'ecole.institution', 'campus', 'anneeScolaire'])
                    ->whereNull('deleted_at')
                    ->orderBy('nom')
                    ->get()
                    ->toArray(),
                'classes' => Classe::with(['niveau', 'section', 'cycle', 'ecole', 'anneeScolaire'])
                    ->whereNull('deleted_at')
                    ->orderBy('nom')
                    ->get()
                    ->toArray(),
                'anneesScolaires' => \Modules\Parametrage\Entities\AnneeScolaire::select('id', 'libelle')
                    ->where('etat', 'actif')
                    ->orderBy('libelle', 'desc')
                    ->get()
                    ->toArray(),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "ClassesApprenantsController::show", $th->getMessage());
            return redirect()->route('academique.classes_apprenants.index')
                ->with('error', 'Une erreur s\'est produite');
        }
    }

    public function edit(Apprenant $apprenant): Response
    {
        try {
            $apprenant = $apprenant->load([
                'classe.niveau',
                'section',
                'cycle',
                'ecole.institution',
                'campus',
                'anneeScolaire'
            ]);

            return Inertia::render('Academique::ClassesApprenants/Edit', [
                'apprenant' => $apprenant,
                'apprenants' => Apprenant::with(['classe.niveau', 'section', 'cycle', 'ecole.institution', 'campus', 'anneeScolaire'])
                    ->whereNull('deleted_at')
                    ->orderBy('nom')
                    ->get()
                    ->toArray(),
                'classes' => Classe::with(['niveau', 'section', 'cycle', 'ecole', 'anneeScolaire'])
                    ->whereNull('deleted_at')
                    ->orderBy('nom')
                    ->get()
                    ->toArray(),
                'anneesScolaires' => \Modules\Parametrage\Entities\AnneeScolaire::select('id', 'libelle')
                    ->where('etat', 'actif')
                    ->orderBy('libelle', 'desc')
                    ->get()
                    ->toArray(),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "ClassesApprenantsController::edit", $th->getMessage());
            return redirect()->route('academique.classes_apprenants.index')
                ->with('error', 'Une erreur s\'est produite');
        }
    }

    public function update(Request $request, Apprenant $apprenant)
    {
        try {
            $validated = $request->validate([
                'apprenant_id' => 'required|exists:apprenants,id',
                'classe_id' => 'required|exists:classes,id',
            ]);

            $apprenant->update(['classe_id' => $validated['classe_id']]);

            return redirect()->route('academique.classes_apprenants.index')
                ->with('success', 'Affectation mise à jour avec succès');
        } catch (\Throwable $th) {
            log_error("Academique", "ClassesApprenantsController::update", $th->getMessage());
            return redirect()->back()->with('error', 'Une erreur s\'est produite');
        }
    }

    public function destroy(Apprenant $apprenant)
    {
        try {
            $apprenant->update(['classe_id' => null]);

            return redirect()->route('academique.classes_apprenants.index')
                ->with('success', 'Apprenant retiré de la classe avec succès');
        } catch (\Throwable $th) {
            log_error("Academique", "ClassesApprenantsController::destroy", $th->getMessage());
            return redirect()->back()->with('error', 'Une erreur s\'est produite');
        }
    }
}
