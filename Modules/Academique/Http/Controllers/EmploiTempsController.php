<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academique\Entities\EmploiTemps;
use Modules\Parametrage\Entities\Classe;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\Section;
use Modules\Parametrage\Entities\CycleEnseignement;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Campus;
use Modules\Parametrage\Entities\MatiereUnite;
use Modules\Academique\Entities\Enseignant;

class EmploiTempsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:emploi-temps-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:emploi-temps-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:emploi-temps-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:emploi-temps-delete', ['only' => ['destroy', 'activate']]);
    }

    /**
     * Check if a new course time overlaps with existing courses
     */
    private function hasTimeOverlap($jour, $heureDebut, $heureFin, $classeId, $weekStartDate, $excludeIds = [])
    {
        return EmploiTemps::where('classe_id', $classeId)
            ->where('jour', $jour)
            ->where('week_start_date', $weekStartDate)
            ->whereNull('deleted_at')
            ->whereNotIn('id', $excludeIds)
            ->get()
            ->filter(function($existing) use ($heureDebut, $heureFin) {
                $existStart = substr($existing->date_debut, 11, 5); // Extract HH:mm
                $existEnd = substr($existing->date_fin, 11, 5);
                // Overlap check: startA < endB AND startB < endA
                return $heureDebut < $existEnd && $existStart < $heureFin;
            })
            ->isNotEmpty();
    }

    public function index(Request $request)
    {
        try {
            $query = EmploiTemps::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('classe', function ($q) use ($search) {
                    $q->where('nom', 'like', "%$search%");
                })->orWhereHas('anneeScolaire', function ($q) use ($search) {
                    $q->where('libelle', 'like', "%$search%");
                });
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            if ($request->filled('week_number')) {
                $weekParts = explode('-W', $request->input('week_number'));
                if (count($weekParts) === 2) {
                    $year = (int)$weekParts[0];
                    $week = (int)$weekParts[1];
                    $query->where('week_number', $week)->where('year', $year);
                }
            }

            // Get all matching emplois du temps
            $allEmplois = $query->with(['classe', 'anneeScolaire'])->get();

            // Group by week (week_start_date + classe_id + week_name) to show one row per week
            $grouped = $allEmplois->groupBy(function ($item) {
                return $item->week_start_date . '|' . $item->classe_id . '|' . $item->week_name;
            })->map(function ($group) {
                $first = $group->first();
                return (object)[
                    'id' => $first->id,
                    'classe_id' => $first->classe_id,
                    'classe' => $first->classe ? ['id' => $first->classe->id, 'nom' => $first->classe->nom] : null,
                    'annee_scolaire_id' => $first->annee_scolaire_id,
                    'anneeScolaire' => $first->anneeScolaire ? ['id' => $first->anneeScolaire->id, 'libelle' => $first->anneeScolaire->libelle] : null,
                    'week_name' => $first->week_name,
                    'week_start_date' => $first->week_start_date,
                    'week_end_date' => $first->week_end_date,
                    'week_number' => $first->week_number,
                    'year' => $first->year,
                    'statut' => $first->statut,
                    'deleted_at' => $first->deleted_at,
                    'total_courses' => $group->count(),
                    'courses' => $group->toArray(),
                ];
            })->values();

            // Create proper Laravel pagination
            $page = \Illuminate\Pagination\Paginator::resolveCurrentPage();
            $perPage = 25;
            $items = $grouped->slice(($page - 1) * $perPage, $perPage)->values();
            $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $grouped->count(),
                $perPage,
                $page,
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
            );

            return Inertia::render('Academique::EmploisTemps/Index', [
                'title' => __('common.emplois_du_temps'),
                'emploisTemps' => $paginated,
                'filters' => $request->only(['search', 'statut', 'week_number']),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "EmploiTempsController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Academique::EmploisTemps/Create', [
                'title' => __('actions.create'),
                'classes' => Classe::select('id', 'nom')->get()->toArray(),
                'anneesScolaires' => AnneeScolaire::select('id', 'libelle')->get()->toArray(),
                'sections' => Section::select('id', 'libelle')->get()->toArray(),
                'cycles' => CycleEnseignement::select('id', 'libelle')->get()->toArray(),
                'ecoles' => Ecole::select('id', 'nom')->get()->toArray(),
                'campuses' => Campus::select('id', 'nom')->get()->toArray(),
                'matieres' => MatiereUnite::select('id', 'libelle')->get()->toArray(),
                'enseignants' => Enseignant::select('id', 'nom', 'prenoms')->get()->map(fn($e) => [
                    'id' => $e->id,
                    'libelle' => trim(($e->prenoms ?? '') . ' ' . $e->nom)
                ])->toArray(),
                'enseignantMatieres' => \DB::table('enseignant_matieres')
                    ->select('enseignant_id', 'matiere_id')
                    ->get()
                    ->toArray(),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "EmploiTempsController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'classe_id' => 'required|exists:classes,id',
                'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
                'week_name' => 'required|string|max:100',
                'week_start_date' => 'required|date_format:Y-m-d',
                'week_end_date' => 'nullable|date_format:Y-m-d',
                'week_number' => 'nullable|integer|min:1|max:52',
                'year' => 'nullable|integer|min:2000|max:2100',
                'section_id' => 'nullable|exists:sections,id',
                'cycle_id' => 'nullable|exists:cycles_enseignement,id',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'campus_id' => 'nullable|exists:campuses,id',
                'jour' => 'nullable|string|max:100',
                'matiere_id' => 'nullable|exists:matieres_unites,id',
                'enseignant_id' => 'nullable|exists:enseignants,id',
                'duree' => 'nullable|numeric|min:0',
                'date_debut' => 'required|date_format:Y-m-d\TH:i',
                'date_fin' => 'required|date_format:Y-m-d\TH:i',
                'est_valide' => 'nullable|boolean',
                'statut' => 'required|in:brouillon,valide,publie,archive',
            ]);

            EmploiTemps::create($validated);

            return redirect()->route('academique.emplois_du_temps.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "EmploiTempsController::store", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function show(EmploiTemps $emploi_temps)
    {
        try {
            // Load all courses for this week (same week_start_date + classe_id)
            $weekCourses = EmploiTemps::where('week_start_date', $emploi_temps->week_start_date)
                ->where('classe_id', $emploi_temps->classe_id)
                ->with(['matiere', 'enseignant'])
                ->get()
                ->groupBy('jour');

            // Transform week courses to array of day => courses
            $coursesGroupedByDay = [];
            foreach (['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'] as $jour) {
                $coursesGroupedByDay[$jour] = collect($weekCourses->get($jour, []))
                    ->map(function ($course) {
                        return [
                            'id' => $course->id,
                            'matiere_id' => $course->matiere_id,
                            'matiere' => $course->matiere ? ['id' => $course->matiere->id, 'libelle' => $course->matiere->libelle] : null,
                            'enseignant_id' => $course->enseignant_id,
                            'enseignant' => $course->enseignant ? ['id' => $course->enseignant->id, 'prenoms' => $course->enseignant->user->prenoms ?? '', 'nom' => $course->enseignant->user->nom ?? ''] : null,
                            'date_debut' => $course->date_debut,
                            'date_fin' => $course->date_fin,
                            'duree' => $course->duree,
                        ];
                    })->toArray();
            }

            $emploi_temps->load('classe', 'anneeScolaire', 'section', 'cycle', 'ecole', 'campus', 'matiere', 'enseignant');

            // Transform data to flatten Inertia serialization issues
            $emploiTempsData = [
                'id' => $emploi_temps->id,
                'classe_id' => $emploi_temps->classe_id,
                'annee_scolaire_id' => $emploi_temps->annee_scolaire_id,
                'section_id' => $emploi_temps->section_id,
                'cycle_id' => $emploi_temps->cycle_id,
                'ecole_id' => $emploi_temps->ecole_id,
                'campus_id' => $emploi_temps->campus_id,
                'week_name' => $emploi_temps->week_name,
                'week_start_date' => $emploi_temps->week_start_date,
                'week_end_date' => $emploi_temps->week_end_date,
                'week_number' => $emploi_temps->week_number,
                'year' => $emploi_temps->year,
                'jour' => $emploi_temps->jour,
                'matiere_id' => $emploi_temps->matiere_id,
                'enseignant_id' => $emploi_temps->enseignant_id,
                'duree' => $emploi_temps->duree,
                'date_debut' => $emploi_temps->date_debut,
                'date_fin' => $emploi_temps->date_fin,
                'est_valide' => $emploi_temps->est_valide,
                'statut' => $emploi_temps->statut,
                'coursesGroupedByDay' => $coursesGroupedByDay,
                'classe' => $emploi_temps->classe ? ['id' => $emploi_temps->classe->id, 'nom' => $emploi_temps->classe->nom] : null,
                'anneeScolaire' => $emploi_temps->anneeScolaire ? ['id' => $emploi_temps->anneeScolaire->id, 'libelle' => $emploi_temps->anneeScolaire->libelle] : null,
                'section' => $emploi_temps->section ? ['id' => $emploi_temps->section->id, 'libelle' => $emploi_temps->section->libelle] : null,
                'cycle' => $emploi_temps->cycle ? ['id' => $emploi_temps->cycle->id, 'libelle' => $emploi_temps->cycle->libelle] : null,
                'ecole' => $emploi_temps->ecole ? ['id' => $emploi_temps->ecole->id, 'nom' => $emploi_temps->ecole->nom] : null,
                'campus' => $emploi_temps->campus ? ['id' => $emploi_temps->campus->id, 'nom' => $emploi_temps->campus->nom] : null,
                'matiere' => $emploi_temps->matiere ? ['id' => $emploi_temps->matiere->id, 'libelle' => $emploi_temps->matiere->libelle] : null,
                'enseignant' => $emploi_temps->enseignant ? ['id' => $emploi_temps->enseignant->id, 'user_id' => $emploi_temps->enseignant->user_id] : null,
            ];

            return Inertia::render('Academique::EmploisTemps/Show', [
                'title' => __('actions.view'),
                'emploiTemps' => $emploiTempsData,
                'classes' => Classe::select('id', 'nom')->get()->toArray(),
                'anneesScolaires' => AnneeScolaire::select('id', 'libelle')->get()->toArray(),
                'sections' => Section::select('id', 'libelle')->get()->toArray(),
                'cycles' => CycleEnseignement::select('id', 'libelle')->get()->toArray(),
                'ecoles' => Ecole::select('id', 'nom')->get()->toArray(),
                'campuses' => Campus::select('id', 'nom')->get()->toArray(),
                'matieres' => MatiereUnite::select('id', 'libelle')->get()->toArray(),
                'enseignants' => Enseignant::with('user')->select('id', 'user_id')->get()->map(fn($e) => [
                    'id' => $e->id,
                    'libelle' => $e->user->prenoms . ' ' . $e->user->nom
                ])->toArray(),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "EmploiTempsController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(EmploiTemps $emploi_temps)
    {
        try {
            $emploi_temps->load('classe', 'anneeScolaire', 'section', 'cycle', 'ecole', 'campus', 'matiere', 'enseignant');

            // Transform data to flatten Inertia serialization issues
            $emploiTempsData = [
                'id' => $emploi_temps->id,
                'classe_id' => $emploi_temps->classe_id,
                'annee_scolaire_id' => $emploi_temps->annee_scolaire_id,
                'section_id' => $emploi_temps->section_id,
                'cycle_id' => $emploi_temps->cycle_id,
                'ecole_id' => $emploi_temps->ecole_id,
                'campus_id' => $emploi_temps->campus_id,
                'week_name' => $emploi_temps->week_name,
                'week_start_date' => $emploi_temps->week_start_date,
                'week_end_date' => $emploi_temps->week_end_date,
                'week_number' => $emploi_temps->week_number,
                'year' => $emploi_temps->year,
                'jour' => $emploi_temps->jour,
                'matiere_id' => $emploi_temps->matiere_id,
                'enseignant_id' => $emploi_temps->enseignant_id,
                'duree' => $emploi_temps->duree,
                'date_debut' => $emploi_temps->date_debut,
                'date_fin' => $emploi_temps->date_fin,
                'est_valide' => $emploi_temps->est_valide,
                'statut' => $emploi_temps->statut,
                'classe' => $emploi_temps->classe ? ['id' => $emploi_temps->classe->id, 'nom' => $emploi_temps->classe->nom] : null,
                'anneeScolaire' => $emploi_temps->anneeScolaire ? ['id' => $emploi_temps->anneeScolaire->id, 'libelle' => $emploi_temps->anneeScolaire->libelle] : null,
                'section' => $emploi_temps->section ? ['id' => $emploi_temps->section->id, 'libelle' => $emploi_temps->section->libelle] : null,
                'cycle' => $emploi_temps->cycle ? ['id' => $emploi_temps->cycle->id, 'libelle' => $emploi_temps->cycle->libelle] : null,
                'ecole' => $emploi_temps->ecole ? ['id' => $emploi_temps->ecole->id, 'nom' => $emploi_temps->ecole->nom] : null,
                'campus' => $emploi_temps->campus ? ['id' => $emploi_temps->campus->id, 'nom' => $emploi_temps->campus->nom] : null,
                'matiere' => $emploi_temps->matiere ? ['id' => $emploi_temps->matiere->id, 'libelle' => $emploi_temps->matiere->libelle] : null,
                'enseignant' => $emploi_temps->enseignant ? ['id' => $emploi_temps->enseignant->id, 'user_id' => $emploi_temps->enseignant->user_id] : null,
            ];

            return Inertia::render('Academique::EmploisTemps/Edit', [
                'title' => __('actions.edit'),
                'emploiTemps' => $emploiTempsData,
                'classes' => Classe::select('id', 'nom')->get()->toArray(),
                'anneesScolaires' => AnneeScolaire::select('id', 'libelle')->get()->toArray(),
                'sections' => Section::select('id', 'libelle')->get()->toArray(),
                'cycles' => CycleEnseignement::select('id', 'libelle')->get()->toArray(),
                'ecoles' => Ecole::select('id', 'nom')->get()->toArray(),
                'campuses' => Campus::select('id', 'nom')->get()->toArray(),
                'matieres' => MatiereUnite::select('id', 'libelle')->get()->toArray(),
                'enseignants' => Enseignant::with('user')->select('id', 'user_id')->get()->map(fn($e) => [
                    'id' => $e->id,
                    'libelle' => $e->user->prenoms . ' ' . $e->user->nom
                ])->toArray(),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "EmploiTempsController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, EmploiTemps $emploi_temps)
    {
        try {
            $validated = $request->validate([
                'classe_id' => 'required|exists:classes,id',
                'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
                'week_name' => 'required|string|max:100',
                'week_start_date' => 'required|date_format:Y-m-d',
                'week_end_date' => 'nullable|date_format:Y-m-d',
                'week_number' => 'nullable|integer|min:1|max:52',
                'year' => 'nullable|integer|min:2000|max:2100',
                'section_id' => 'nullable|exists:sections,id',
                'cycle_id' => 'nullable|exists:cycles_enseignement,id',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'campus_id' => 'nullable|exists:campuses,id',
                'jour' => 'nullable|string|max:100',
                'matiere_id' => 'nullable|exists:matieres_unites,id',
                'enseignant_id' => 'nullable|exists:enseignants,id',
                'duree' => 'nullable|numeric|min:0',
                'date_debut' => 'required|date_format:Y-m-d\TH:i',
                'date_fin' => 'required|date_format:Y-m-d\TH:i',
                'est_valide' => 'nullable|boolean',
                'statut' => 'required|in:brouillon,valide,publie,archive',
            ]);

            $emploi_temps->update($validated);

            return redirect()->route('academique.emplois_du_temps.show', $emploi_temps)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "EmploiTempsController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function destroy(EmploiTemps $emploi_temps)
    {
        try {
            $emploi_temps->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "EmploiTempsController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function storeWeek(Request $request)
    {
        try {
            // Validate common fields
            $validated = $request->validate([
                'week_name' => 'required|string|max:100',
                'week_start_date' => 'required|date_format:Y-m-d',
                'week_end_date' => 'nullable|date_format:Y-m-d',
                'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
                'classe_id' => 'required|exists:classes,id',
                'section_id' => 'nullable|exists:sections,id',
                'cycle_id' => 'nullable|exists:cycles_enseignement,id',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'campus_id' => 'nullable|exists:campuses,id',
                'statut' => 'required|in:brouillon,valide,publie,archive',
                'cours' => 'required|array',
                'cours.*' => 'array',
                'cours.*.*.matiere_id' => 'nullable|exists:matieres_unites,id',
                'cours.*.*.enseignant_id' => 'nullable|exists:enseignants,id',
                'cours.*.*.heure_debut' => 'nullable|date_format:H:i',
                'cours.*.*.heure_fin' => 'nullable|date_format:H:i',
                'cours.*.*.duree' => 'nullable|numeric|min:0',
            ]);

            // Day offsets for week calculation
            $jourOffsets = ['lundi' => 0, 'mardi' => 1, 'mercredi' => 2, 'jeudi' => 3, 'vendredi' => 4, 'samedi' => 5];
            $startDate = \Carbon\Carbon::parse($validated['week_start_date']);
            $now = \Carbon\Carbon::now();
            $username = auth()->user()->name ?? null;

            // Check for time overlaps
            foreach ($validated['cours'] as $jour => $coursDuJour) {
                foreach ($coursDuJour as $cours) {
                    if (empty($cours['matiere_id']) || empty($cours['heure_debut']) || empty($cours['heure_fin'])) {
                        continue;
                    }
                    if ($this->hasTimeOverlap($jour, $cours['heure_debut'], $cours['heure_fin'], $validated['classe_id'], $validated['week_start_date'])) {
                        return back()->withErrors(['cours' => "❌ Chevauchement détecté le {$jour}: {$cours['heure_debut']}-{$cours['heure_fin']} chevauche un cours existant."]);
                    }
                }
            }

            // Build records array for bulk insert
            $records = [];
            foreach ($validated['cours'] as $jour => $coursDuJour) {
                $offset = $jourOffsets[$jour] ?? 0;
                $dateJour = $startDate->copy()->addDays($offset)->format('Y-m-d');

                foreach ($coursDuJour as $cours) {
                    // Skip empty slots
                    if (empty($cours['heure_debut']) && empty($cours['matiere_id'])) {
                        continue;
                    }

                    $records[] = [
                        'week_name'         => $validated['week_name'],
                        'week_start_date'   => $validated['week_start_date'],
                        'week_end_date'     => $validated['week_end_date'] ?? null,
                        'annee_scolaire_id' => $validated['annee_scolaire_id'],
                        'classe_id'         => $validated['classe_id'],
                        'section_id'        => $validated['section_id'] ?? null,
                        'cycle_id'          => $validated['cycle_id'] ?? null,
                        'ecole_id'          => $validated['ecole_id'] ?? null,
                        'campus_id'         => $validated['campus_id'] ?? null,
                        'jour'              => $jour,
                        'matiere_id'        => $cours['matiere_id'] ?? null,
                        'enseignant_id'     => $cours['enseignant_id'] ?? null,
                        'date_debut'        => $dateJour . 'T' . ($cours['heure_debut'] ?? '00:00'),
                        'date_fin'          => $dateJour . 'T' . ($cours['heure_fin'] ?? '00:00'),
                        'duree'             => $cours['duree'] ?? null,
                        'statut'            => $validated['statut'],
                        'creation_username' => $username,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];
                }
            }

            // Bulk insert all courses at once
            if (!empty($records)) {
                EmploiTemps::insert($records);
            }

            return redirect()->route('academique.emplois_du_temps.index')
                ->with('success', count($records) . " cours créés avec succès pour la semaine '{$validated['week_name']}'");

        } catch (\Throwable $th) {
            log_error("Academique", "EmploiTempsController::storeWeek", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function editWeek(EmploiTemps $emploi_temps)
    {
        try {
            // Load all courses for this week
            $weekCourses = EmploiTemps::where('week_start_date', $emploi_temps->week_start_date)
                ->where('classe_id', $emploi_temps->classe_id)
                ->get()
                ->groupBy('jour');

            // Build courses array for form
            $coursesForForm = [];
            foreach (['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'] as $jour) {
                $coursesForForm[$jour] = collect($weekCourses->get($jour, []))
                    ->map(function ($course) {
                        return [
                            'id' => $course->id,
                            'matiere_id' => $course->matiere_id,
                            'enseignant_id' => $course->enseignant_id,
                            'heure_debut' => substr($course->date_debut, 11, 5),
                            'heure_fin' => substr($course->date_fin, 11, 5),
                            'duree' => $course->duree,
                        ];
                    })->toArray();
            }

            return Inertia::render('Academique::EmploisTemps/EditWeek', [
                'title' => __('actions.edit'),
                'emploi_temps_id' => $emploi_temps->id,
                'week_name' => $emploi_temps->week_name,
                'week_start_date' => $emploi_temps->week_start_date,
                'week_end_date' => $emploi_temps->week_end_date,
                'annee_scolaire_id' => $emploi_temps->annee_scolaire_id,
                'classe_id' => $emploi_temps->classe_id,
                'section_id' => $emploi_temps->section_id,
                'cycle_id' => $emploi_temps->cycle_id,
                'ecole_id' => $emploi_temps->ecole_id,
                'campus_id' => $emploi_temps->campus_id,
                'statut' => $emploi_temps->statut,
                'coursesForForm' => $coursesForForm,
                'classes' => Classe::select('id', 'nom')->get()->toArray(),
                'anneesScolaires' => AnneeScolaire::select('id', 'libelle')->get()->toArray(),
                'sections' => Section::select('id', 'libelle')->get()->toArray(),
                'cycles' => CycleEnseignement::select('id', 'libelle')->get()->toArray(),
                'ecoles' => Ecole::select('id', 'nom')->get()->toArray(),
                'campuses' => Campus::select('id', 'nom')->get()->toArray(),
                'matieres' => MatiereUnite::select('id', 'libelle')->get()->toArray(),
                'enseignants' => Enseignant::with('user')->select('id', 'user_id')->get()->map(fn($e) => [
                    'id' => $e->id,
                    'libelle' => $e->user->prenoms . ' ' . $e->user->nom
                ])->toArray(),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "EmploiTempsController::editWeek", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function updateWeek(Request $request, EmploiTemps $emploi_temps)
    {
        try {
            // Validate common fields
            $validated = $request->validate([
                'week_name' => 'required|string|max:100',
                'week_start_date' => 'required|date_format:Y-m-d',
                'week_end_date' => 'nullable|date_format:Y-m-d',
                'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
                'classe_id' => 'required|exists:classes,id',
                'section_id' => 'nullable|exists:sections,id',
                'cycle_id' => 'nullable|exists:cycles_enseignement,id',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'campus_id' => 'nullable|exists:campuses,id',
                'statut' => 'required|in:brouillon,valide,publie,archive',
                'cours' => 'required|array',
                'cours.*' => 'array',
                'cours.*.*.matiere_id' => 'nullable|exists:matieres_unites,id',
                'cours.*.*.enseignant_id' => 'nullable|exists:enseignants,id',
                'cours.*.*.heure_debut' => 'nullable|date_format:H:i',
                'cours.*.*.heure_fin' => 'nullable|date_format:H:i',
                'cours.*.*.duree' => 'nullable|numeric|min:0',
            ]);

            // Check for time overlaps in submitted data
            foreach ($validated['cours'] as $jour => $coursDuJour) {
                foreach ($coursDuJour as $cours) {
                    if (empty($cours['matiere_id']) || empty($cours['heure_debut']) || empty($cours['heure_fin'])) {
                        continue;
                    }
                    // §BUG-FIX : la closure référençait $coursDuJour sans le
                    //   capturer → PHP 8+ émet "Undefined variable $coursDuJour"
                    //   à chaque validation → log_error + rollback silencieux.
                    //   On capture explicitement les 2 variables du scope parent.
                    $conflicting = array_filter($coursDuJour, function ($c) use ($cours, $coursDuJour) {
                        if (empty($c['matiere_id']) || empty($c['heure_debut']) || empty($c['heure_fin'])) {
                            return false;
                        }
                        // Overlap check: startA < endB AND startB < endA
                        return $c['heure_debut'] < $cours['heure_fin']
                            && $cours['heure_debut'] < $c['heure_fin']
                            && (array_search($c, $coursDuJour) !== array_search($cours, $coursDuJour));
                    });
                    if (!empty($conflicting)) {
                        return back()->withErrors(['cours' => "❌ Chevauchement détecté le {$jour}: {$cours['heure_debut']}-{$cours['heure_fin']} chevauche un autre cours."]);
                    }
                }
            }

            // Use transaction for atomicity
            \DB::beginTransaction();

            // Delete all existing courses for this week
            EmploiTemps::where('week_start_date', $emploi_temps->week_start_date)
                ->where('classe_id', $emploi_temps->classe_id)
                ->delete();

            // Day offsets for week calculation
            $jourOffsets = ['lundi' => 0, 'mardi' => 1, 'mercredi' => 2, 'jeudi' => 3, 'vendredi' => 4, 'samedi' => 5];
            $startDate = \Carbon\Carbon::parse($validated['week_start_date']);
            $now = \Carbon\Carbon::now();
            $username = auth()->user()->name ?? null;

            // Build records array for bulk insert
            $records = [];
            foreach ($validated['cours'] as $jour => $coursDuJour) {
                $offset = $jourOffsets[$jour] ?? 0;
                $dateJour = $startDate->copy()->addDays($offset)->format('Y-m-d');

                foreach ($coursDuJour as $cours) {
                    // Skip empty slots
                    if (empty($cours['heure_debut']) && empty($cours['matiere_id'])) {
                        continue;
                    }

                    $records[] = [
                        'week_name'         => $validated['week_name'],
                        'week_start_date'   => $validated['week_start_date'],
                        'week_end_date'     => $validated['week_end_date'] ?? null,
                        'annee_scolaire_id' => $validated['annee_scolaire_id'],
                        'classe_id'         => $validated['classe_id'],
                        'section_id'        => $validated['section_id'] ?? null,
                        'cycle_id'          => $validated['cycle_id'] ?? null,
                        'ecole_id'          => $validated['ecole_id'] ?? null,
                        'campus_id'         => $validated['campus_id'] ?? null,
                        'jour'              => $jour,
                        'matiere_id'        => $cours['matiere_id'] ?? null,
                        'enseignant_id'     => $cours['enseignant_id'] ?? null,
                        'date_debut'        => $dateJour . 'T' . ($cours['heure_debut'] ?? '00:00'),
                        'date_fin'          => $dateJour . 'T' . ($cours['heure_fin'] ?? '00:00'),
                        'duree'             => $cours['duree'] ?? null,
                        'statut'            => $validated['statut'],
                        'modification_username' => $username,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];
                }
            }

            // Bulk insert all courses at once
            if (!empty($records)) {
                EmploiTemps::insert($records);
            }

            \DB::commit();

            return redirect()->route('academique.emplois_du_temps.index')
                ->with('success', count($records) . " cours mis à jour avec succès pour la semaine '{$validated['week_name']}'");

        } catch (\Throwable $th) {
            \DB::rollBack();
            log_error("Academique", "EmploiTempsController::updateWeek", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function activate(EmploiTemps $emploi_temps)
    {
        try {
            if ($emploi_temps->trashed()) {
                $emploi_temps->restore();
            } else {
                $emploi_temps->delete();
            }

            return redirect()->route('academique.emplois_du_temps.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Academique", "EmploiTempsController::activate", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    /**
     * Export emploi du temps to PDF
     */
    public function exportPdf(EmploiTemps $emploi_temps)
    {
        try {
            $weekCourses = EmploiTemps::where('week_start_date', $emploi_temps->week_start_date)
                ->where('classe_id', $emploi_temps->classe_id)
                ->whereNull('deleted_at')
                ->with(['matiere', 'enseignant', 'classe', 'anneeScolaire'])
                ->orderBy('date_debut')
                ->get()
                ->groupBy('jour');

            $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.emploi_temps', [
                'emploi_temps' => $emploi_temps,
                'weekCourses'  => $weekCourses,
                'jours'        => $jours,
            ]);

            $filename = 'emploi-du-temps-' . str($emploi_temps->week_name)->slug() . '.pdf';
            return $pdf->download($filename);

        } catch (\Throwable $th) {
            log_error("Academique", "EmploiTempsController::exportPdf", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
