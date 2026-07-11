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
use Modules\Academique\Entities\EmploiTempsCreneau;

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
            $query = EmploiTemps::query()->with(['classe', 'anneeScolaire', 'periode'])
                ->withCount('creneaux');

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('libelle', 'like', "%$search%")
                      ->orWhereHas('classe', fn ($c) => $c->where('nom', 'like', "%$search%"));
                });
            }
            if ($request->filled('etat')) {
                $query->where('etat', $request->input('etat'));
            }

            $emploisTemps = $query->orderByDesc('id')->paginate(10)->withQueryString()
                ->through(fn ($e) => [
                    'id'            => $e->id,
                    'libelle'       => $e->libelle,
                    'classe'        => $e->classe ? $e->classe->libelle_affichage ?? ($e->classe->libelle ?? $e->classe->nom) : '-',
                    'annee'         => $e->anneeScolaire?->libelle,
                    'periode'       => $e->periode?->libelle,
                    'date_debut'    => $e->date_debut ? \Carbon\Carbon::parse($e->date_debut)->format('d/m/Y') : '-',
                    'date_fin'      => $e->date_fin ? \Carbon\Carbon::parse($e->date_fin)->format('d/m/Y') : '-',
                    'nb_creneaux'   => $e->creneaux_count,
                    'etat'          => $e->etat ?? 'actif',
                ]);

            return Inertia::render('Academique::EmploisTemps/Index', [
                'title' => __('common.emplois_du_temps'),
                'emploisTemps' => $emploisTemps,
                'filters' => $request->only(['search', 'etat']),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "EmploiTempsController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    /**
     * Options communes des formulaires (cadre + créneaux).
     * Les classes portent leurs FK (école/campus/section/cycle/niveau/année)
     * pour l'auto-remplissage en cascade côté front — zéro re-saisie.
     */
    private function formOptions(): array
    {
        return [
            'classes' => Classe::whereNull('deleted_at')->orderBy('nom')
                ->get(['id', 'nom', 'libelle', 'libelle_affichage', 'ecole_id', 'campus_id', 'section_id', 'cycle_id', 'niveau_id', 'annee_scolaire_id'])
                ->map(fn ($c) => [
                    'id'                => $c->id,
                    'libelle'           => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                    'ecole_id'          => $c->ecole_id,
                    'campus_id'         => $c->campus_id,
                    'section_id'        => $c->section_id,
                    'cycle_id'          => $c->cycle_id,
                    'niveau_id'         => $c->niveau_id,
                    'annee_scolaire_id' => $c->annee_scolaire_id,
                ]),
            'ecoles'      => Ecole::whereNull('deleted_at')->orderBy('nom')->get(['id', 'nom'])->map(fn ($e) => ['id' => $e->id, 'libelle' => $e->nom]),
            'campuses'    => Campus::whereNull('deleted_at')->orderBy('nom')->get(['id', 'nom', 'institution_id'])->map(fn ($c) => ['id' => $c->id, 'libelle' => $c->nom, 'institution_id' => $c->institution_id]),
            'institutions' => \Modules\Parametrage\Entities\Institution::orderBy('nom')->get(['id', 'nom'])->map(fn ($i) => ['id' => $i->id, 'libelle' => $i->nom]),
            'sections'    => Section::whereNull('deleted_at')->orderBy('libelle')->get(['id', 'libelle']),
            'cycles'      => CycleEnseignement::whereNull('deleted_at')->orderBy('libelle')->get(['id', 'libelle']),
            'niveaux'     => \Modules\Parametrage\Entities\NiveauEtude::whereNull('deleted_at')->orderBy('libelle')->get(['id', 'libelle']),
            'anneesScolaires' => AnneeScolaire::whereNull('deleted_at')->orderBy('libelle', 'desc')->get(['id', 'libelle']),
            'periodes'    => \Modules\Parametrage\Entities\PeriodeColaire::whereNull('deleted_at')->orderBy('libelle')->get(['id', 'libelle']),
            'matieres'    => MatiereUnite::whereNull('deleted_at')->orderBy('libelle')->get(['id', 'libelle']),
            'enseignants' => Enseignant::whereNull('deleted_at')->get(['id', 'nom', 'prenoms'])
                ->map(fn ($e) => ['id' => $e->id, 'libelle' => trim(($e->prenoms ?? '') . ' ' . ($e->nom ?? ''))]),
        ];
    }

    /** Contexte académique dérivé de la classe (source de vérité unique). */
    private function classeContext($classeId): array
    {
        $c = Classe::find($classeId);
        if (!$c) return [];
        return [
            'ecole_id'          => $c->ecole_id,
            'campus_id'         => $c->campus_id,
            'section_id'        => $c->section_id,
            'cycle_id'          => $c->cycle_id,
            'niveau_id'         => $c->niveau_id,
            'annee_scolaire_id' => $c->annee_scolaire_id,
        ];
    }

    /**
     * Détection de conflits d'horaires (expertise planning) :
     *  - dans le même emploi du temps : deux créneaux du même jour ne peuvent pas
     *    se chevaucher (la classe ne peut pas être à 2 endroits) ;
     *  - inter-emplois : un enseignant ne peut pas être sur deux créneaux qui se
     *    chevauchent le même jour (double réservation).
     * Retourne la liste des messages d'erreur (vide = pas de conflit).
     */
    private function detectConflicts(array $creneaux, $excludeEmploiId = null): array
    {
        $errors = [];
        $overlap = fn ($a1, $a2, $b1, $b2) => $a1 && $a2 && $b1 && $b2 && $a1 < $b2 && $b1 < $a2;

        // 1) Chevauchements internes (même classe = ce cadre)
        foreach ($creneaux as $i => $c1) {
            for ($j = $i + 1; $j < count($creneaux); $j++) {
                $c2 = $creneaux[$j];
                if (($c1['jour'] ?? null) && ($c1['jour'] ?? null) === ($c2['jour'] ?? null)
                    && $overlap($c1['heure_debut'] ?? null, $c1['heure_fin'] ?? null, $c2['heure_debut'] ?? null, $c2['heure_fin'] ?? null)) {
                    $errors[] = "Chevauchement le {$c1['jour']} entre deux créneaux (" . ($c1['heure_debut'] ?? '?') . " et " . ($c2['heure_debut'] ?? '?') . ").";
                }
            }
        }

        // 2) Enseignant déjà occupé sur un autre emploi du temps
        foreach ($creneaux as $c) {
            $ens = $c['enseignant_id'] ?? null;
            $jour = $c['jour'] ?? null;
            $hd = $c['heure_debut'] ?? null;
            $hf = $c['heure_fin'] ?? null;
            if (!$ens || !$jour || !$hd || !$hf) continue;

            $query = EmploiTempsCreneau::query()
                ->where('enseignant_id', $ens)
                ->where('jour', $jour)
                ->whereNull('deleted_at')
                ->where('heure_debut', '<', $hf)
                ->where('heure_fin', '>', $hd);
            if ($excludeEmploiId) {
                $query->where('emploi_temps_id', '!=', $excludeEmploiId);
            }
            if ($query->exists()) {
                $errors[] = "L'enseignant sélectionné est déjà occupé le {$jour} sur ce créneau ({$hd}–{$hf}).";
            }
        }

        return array_values(array_unique($errors));
    }

    private function cadreRules(): array
    {
        return [
            'classe_id'   => 'required|exists:classes,id',
            'periode_id'  => 'nullable|exists:periodes_colaires,id',
            'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
            'libelle'     => 'nullable|string|max:255',
            'date_debut'  => 'nullable|date',
            'date_fin'    => 'nullable|date|after_or_equal:date_debut',
            'etat'        => 'nullable|in:actif,inactif',
            'creneaux'                 => 'nullable|array',
            'creneaux.*.jour'          => 'nullable|string|max:20',
            'creneaux.*.heure_debut'   => 'nullable|string|max:8',
            'creneaux.*.heure_fin'     => 'nullable|string|max:8',
            'creneaux.*.matiere_id'    => 'nullable|exists:matieres_unites,id',
            'creneaux.*.enseignant_id' => 'nullable|exists:enseignants,id',
            'creneaux.*.salle'         => 'nullable|string|max:125',
        ];
    }

    /** Durée (en jours) entre deux dates de validité du cadre. */
    private function computeDuree($debut, $fin)
    {
        if (!$debut || !$fin) return null;
        try {
            return \Carbon\Carbon::parse($debut)->diffInDays(\Carbon\Carbon::parse($fin));
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function persistCadre(array $validated, array $creneaux, ?EmploiTemps $emploi = null): EmploiTemps
    {
        return \DB::transaction(function () use ($validated, $creneaux, $emploi) {
            $context = $this->classeContext($validated['classe_id']);
            $payload = array_merge($validated, $context, [
                'annee_scolaire_id' => $validated['annee_scolaire_id'] ?? ($context['annee_scolaire_id'] ?? null),
                'duree'             => $this->computeDuree($validated['date_debut'] ?? null, $validated['date_fin'] ?? null),
                'etat'              => $validated['etat'] ?? 'actif',
            ]);
            unset($payload['creneaux']);

            if ($emploi) {
                $emploi->update($payload);
            } else {
                $emploi = EmploiTemps::create($payload);
            }

            // Sync créneaux : on remplace intégralement (simple et sûr).
            $emploi->creneaux()->forceDelete();
            foreach (array_values($creneaux) as $i => $c) {
                if (empty($c['jour']) && empty($c['matiere_id']) && empty($c['enseignant_id'])) {
                    continue; // ligne vide ignorée
                }
                $emploi->creneaux()->create([
                    'jour'          => $c['jour'] ?? null,
                    'heure_debut'   => $c['heure_debut'] ?? null,
                    'heure_fin'     => $c['heure_fin'] ?? null,
                    'matiere_id'    => $c['matiere_id'] ?? null,
                    'enseignant_id' => $c['enseignant_id'] ?? null,
                    'salle'         => $c['salle'] ?? null,
                    'ordre'         => $i,
                ]);
            }

            return $emploi;
        });
    }

    public function create()
    {
        try {
            return Inertia::render('Academique::EmploisTemps/Create', array_merge(
                $this->formOptions(),
                ['title' => __('actions.create')]
            ));
        } catch (\Throwable $th) {
            log_error("Academique", "EmploiTempsController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->cadreRules());
            $creneaux = $request->input('creneaux', []);

            $conflicts = $this->detectConflicts($creneaux, null);
            if (!empty($conflicts)) {
                return back()->withErrors(['creneaux' => implode(' ', $conflicts)])->withInput();
            }

            $this->persistCadre($validated, $creneaux);

            return redirect()->route('academique.emplois_du_temps.index')
                ->with('success', __('messages.created_successfully'));
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("Academique", "EmploiTempsController::store", $th->getMessage());
            return back()->with('error', 'Erreur : ' . $th->getMessage())->withInput();
        }
    }

    public function show(EmploiTemps $emploi_temps)
    {
        try {
            $emploi_temps->load('creneaux');
            return Inertia::render('Academique::EmploisTemps/Show', array_merge(
                $this->formOptions(),
                [
                    'title'       => __('actions.view'),
                    'emploiTemps' => $this->cadrePayload($emploi_temps),
                ]
            ));
        } catch (\Throwable $th) {
            log_error("Academique", "EmploiTempsController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(EmploiTemps $emploi_temps)
    {
        try {
            $emploi_temps->load('creneaux');
            return Inertia::render('Academique::EmploisTemps/Edit', array_merge(
                $this->formOptions(),
                [
                    'title'       => __('actions.edit'),
                    'emploiTemps' => $this->cadrePayload($emploi_temps),
                ]
            ));
        } catch (\Throwable $th) {
            log_error("Academique", "EmploiTempsController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, EmploiTemps $emploi_temps)
    {
        try {
            $validated = $request->validate($this->cadreRules());
            $creneaux = $request->input('creneaux', []);

            $conflicts = $this->detectConflicts($creneaux, $emploi_temps->id);
            if (!empty($conflicts)) {
                return back()->withErrors(['creneaux' => implode(' ', $conflicts)])->withInput();
            }

            $this->persistCadre($validated, $creneaux, $emploi_temps);

            return redirect()->route('academique.emplois_du_temps.index')
                ->with('success', __('messages.updated_successfully'));
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("Academique", "EmploiTempsController::update", $th->getMessage());
            return back()->with('error', 'Erreur : ' . $th->getMessage())->withInput();
        }
    }

    /** Données du cadre + ses créneaux pour les formulaires Edit/Show. */
    private function cadrePayload(EmploiTemps $e): array
    {
        $fmtDate = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('Y-m-d') : null;
        return [
            'id'                => $e->id,
            'classe_id'         => $e->classe_id,
            'niveau_id'         => $e->niveau_id,
            'section_id'        => $e->section_id,
            'cycle_id'          => $e->cycle_id,
            'ecole_id'          => $e->ecole_id,
            'campus_id'         => $e->campus_id,
            'annee_scolaire_id' => $e->annee_scolaire_id,
            'periode_id'        => $e->periode_id,
            'libelle'           => $e->libelle,
            'date_debut'        => $fmtDate($e->date_debut),
            'date_fin'          => $fmtDate($e->date_fin),
            'duree'             => $e->duree,
            'etat'              => $e->etat ?? 'actif',
            'creneaux'          => $e->creneaux->map(fn ($c) => [
                'jour'          => $c->jour,
                'heure_debut'   => $c->heure_debut ? substr($c->heure_debut, 0, 5) : null,
                'heure_fin'     => $c->heure_fin ? substr($c->heure_fin, 0, 5) : null,
                'matiere_id'    => $c->matiere_id,
                'enseignant_id' => $c->enseignant_id,
                'salle'         => $c->salle,
            ])->values(),
        ];
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
