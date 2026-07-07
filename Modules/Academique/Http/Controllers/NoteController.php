<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academique\Entities\Note;
use Modules\Academique\Entities\Apprenant;
use Modules\Academique\Entities\Evaluation;
use Modules\Parametrage\Entities\MatiereUnite;
use Modules\Academique\Entities\Enseignant;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\Section;
use Modules\Parametrage\Entities\CycleEnseignement;
use Modules\Parametrage\Entities\Classe;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Campus;
use Modules\Parametrage\Entities\PeriodeColaire;
use Modules\Parametrage\Entities\NatureExamen;
use Modules\Parametrage\Entities\TypeExamen;
use Modules\Parametrage\Entities\GroupeMatiere;

class NoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:notes-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:notes-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:notes-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:notes-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Note::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('apprenant', function ($q) use ($search) {
                    $q->where('matricule', 'like', "%$search%")
                        ->orWhereHas('user', function ($user) use ($search) {
                            $user->where('nom', 'like', "%$search%")
                                ->orWhere('prenoms', 'like', "%$search%");
                        });
                });
            }

            $notes = $query->with(['evaluation', 'apprenant.user'])->paginate(10)->withQueryString()
                ->through(fn($item) => [
                    'id' => $item->id,
                    'apprenant_display' => $item->apprenant && $item->apprenant->user
                        ? $item->apprenant->user->prenoms . ' ' . $item->apprenant->user->nom . ' (' . $item->apprenant->matricule . ')'
                        : ($item->apprenant ? ($item->apprenant->prenoms ? $item->apprenant->prenoms . ' ' : '') . ($item->apprenant->nom ?: 'Sans nom') . ' (' . $item->apprenant->matricule . ')' : 'N/A'),
                    'evaluation_titre' => $item->evaluation?->titre,
                    'note' => $item->note,
                    'note_max' => $item->evaluation?->note_sur,
                    'remarques' => $item->remarques,
                    'statut' => $item->statut,
                    'created_at' => $item->created_at,
                ]);

            return Inertia::render('Academique::Notes/Index', [
                'title' => __('common.notes'),
                'notes' => $notes,
                'filters' => $request->only(['search']),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "NoteController::index", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function create()
    {
        try {
            \Log::info('🔍 NoteController::create() called');

            $apprenants = Apprenant::with('user')->whereNull('deleted_at')
                ->select('id', 'matricule', 'user_id', 'nom', 'prenoms', 'classe_id', 'ecole_id', 'campus_id', 'section_id', 'cycle_id', 'pays_residence_id')
                ->get()->map(fn($a) => [
                'id' => $a->id,
                'libelle' => $a->user
                    ? $a->user->prenoms . ' ' . $a->user->nom . ' (' . $a->matricule . ')'
                    : ($a->prenoms ? $a->prenoms . ' ' : '') . ($a->nom ?: 'Sans nom') . ' (' . $a->matricule . ')',
                'classe_id' => $a->classe_id,
                'ecole_id' => $a->ecole_id,
                'campus_id' => $a->campus_id,
                'section_id' => $a->section_id,
                'cycle_id' => $a->cycle_id,
                'pays_id' => $a->pays_residence_id,
            ])->toArray();

            $evaluations = Evaluation::whereNull('deleted_at')->select('id', 'titre')->get()->map(fn($e) => [
                'id' => $e->id,
                'libelle' => $e->titre
            ])->toArray();

            $refData = [
                'anneesScolaires' => AnneeScolaire::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'sections' => Section::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'cycles' => CycleEnseignement::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'classes' => Classe::whereNull('deleted_at')
                    ->with(['ecole:id,nom', 'campus:id,nom', 'niveau:id,libelle', 'section:id,libelle', 'cycle:id,libelle', 'anneeScolaire:id,libelle'])
                    ->select('id', 'libelle_affichage', 'libelle', 'nom', 'code', 'ecole_id', 'campus_id', 'niveau_id', 'section_id', 'cycle_id', 'annee_scolaire_id')
                    ->get()->map(fn($c) => [
                        'id' => $c->id,
                        'libelle' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                        'nom' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                        'ecole_id' => $c->ecole_id,
                        'ecole_nom' => $c->ecole?->nom,
                        'campus_id' => $c->campus_id,
                        'campus_nom' => $c->campus?->nom,
                        'niveau_id' => $c->niveau_id,
                        'niveau_libelle' => $c->niveau?->libelle,
                        'section_id' => $c->section_id,
                        'section_libelle' => $c->section?->libelle,
                        'cycle_id' => $c->cycle_id,
                        'cycle_libelle' => $c->cycle?->libelle,
                        'annee_scolaire_id' => $c->annee_scolaire_id,
                        'annee_scolaire_libelle' => $c->anneeScolaire?->libelle,
                    ])->toArray(),
                'ecoles' => Ecole::whereNull('deleted_at')->select('id', 'nom as libelle')->get()->toArray(),
                'campuses' => Campus::whereNull('deleted_at')->select('id', 'nom as libelle')->get()->toArray(),
                'periodes' => PeriodeColaire::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'natureExamens' => NatureExamen::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'typeExamens' => TypeExamen::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'matieres' => MatiereUnite::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'groupes' => GroupeMatiere::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'enseignants' => Enseignant::with('user')->whereNull('deleted_at')->select('id', 'user_id')->get()->map(fn($e) => [
                    'id' => $e->id,
                    'libelle' => $e->user?->prenoms . ' ' . $e->user?->nom
                ])->toArray(),
            ];

            \Log::info('✅ Notes data loaded successfully', [
                'apprenants_count' => count($apprenants),
                'evaluations_count' => count($evaluations),
            ]);

            return Inertia::render('Academique::Notes/Create', [
                'title' => __('actions.create'),
                'apprenants' => $apprenants,
                'evaluations' => $evaluations,
                ...$refData,
            ]);
        } catch (\Throwable $th) {
            \Log::error('❌ NoteController::create() ERROR', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            log_error("Academique", "NoteController::create", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            \Log::info('🔍 [DEBUG] NoteController::store - Request data:', $request->all());

            $validated = $request->validate([
                'apprenant_id' => 'required|exists:apprenants,id',
                'evaluation_id' => 'required|exists:evaluations,id',
                'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
                'section_id' => 'nullable|exists:sections,id',
                'cycle_id' => 'nullable|exists:cycles_enseignement,id',
                'classe_id' => 'nullable|exists:classes,id',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'campus_id' => 'nullable|exists:campuses,id',
                'periode_id' => 'nullable|exists:periodes_colaires,id',
                'nature_examen_id' => 'nullable|exists:natures_examens,id',
                'type_examen_id' => 'nullable|exists:type_examens,id',
                'date_examen' => 'nullable|date',
                'matiere_id' => 'nullable|exists:matieres_unites,id',
                'groupe_id' => 'nullable|exists:groupes_matieres,id',
                'note_originale' => 'required|numeric|min:0',
                'note_sur' => 'required|numeric|min:0.01',
                'enseignant_id' => 'nullable|exists:enseignants,id',
                'statut' => 'required|in:en_attente,validee,rejetee,suspendue',
                'remarques' => 'nullable|string',
            ]);

            \Log::info('✅ [DEBUG] NoteController::store - Validation passed:', $validated);

            // 🔄 Normaliser la note à /20
            $noteOriginale = (float) $validated['note_originale'];
            $noteSur = (float) $validated['note_sur'];
            $noteNormalisee = ($noteOriginale / $noteSur) * 20;

            \Log::info('📊 Note normalization', [
                'original' => $noteOriginale,
                'sur' => $noteSur,
                'normalized' => $noteNormalisee
            ]);

            // Ajouter les valeurs normalisées
            $validated['note'] = round($noteNormalisee, 2);

            $note = Note::create($validated);

            \Log::info('✅ [DEBUG] NoteController::store - Note created:', [
                'id' => $note->id,
                'note_originale' => $note->note_originale,
                'note_sur' => $note->note_sur,
                'note_normalized' => $note->note
            ]);

            return redirect()->route('academique.notes.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            \Log::error('❌ [VALIDATION ERROR] NoteController::store:', $ve->errors());
            return back()->withInput()->withErrors($ve->errors());
        } catch (\Throwable $th) {
            \Log::error('❌ [ERROR] NoteController::store:', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'class' => get_class($th)
            ]);
            log_error("Academique", "NoteController::store", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function show(Note $note)
    {
        try {
            $note->load('evaluation', 'apprenant.user', 'anneeScolaire', 'section', 'cycle', 'classe', 'ecole', 'campus', 'periode', 'natureExamen', 'typeExamen', 'matiere', 'groupe', 'enseignant.user');

            $apprenants = Apprenant::with('user')->whereNull('deleted_at')
                ->select('id', 'matricule', 'user_id', 'nom', 'prenoms', 'classe_id', 'ecole_id', 'campus_id', 'section_id', 'cycle_id', 'pays_residence_id')
                ->get()->map(fn($a) => [
                'id' => $a->id,
                'libelle' => $a->user
                    ? $a->user->prenoms . ' ' . $a->user->nom . ' (' . $a->matricule . ')'
                    : ($a->prenoms ? $a->prenoms . ' ' : '') . ($a->nom ?: 'Sans nom') . ' (' . $a->matricule . ')',
                'classe_id' => $a->classe_id,
                'ecole_id' => $a->ecole_id,
                'campus_id' => $a->campus_id,
                'section_id' => $a->section_id,
                'cycle_id' => $a->cycle_id,
                'pays_id' => $a->pays_residence_id,
            ])->toArray();

            $evaluations = Evaluation::whereNull('deleted_at')->select('id', 'titre')->get()->map(fn($e) => [
                'id' => $e->id,
                'libelle' => $e->titre
            ])->toArray();

            $refData = [
                'anneesScolaires' => AnneeScolaire::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'sections' => Section::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'cycles' => CycleEnseignement::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'classes' => Classe::whereNull('deleted_at')
                    ->with(['ecole:id,nom', 'campus:id,nom', 'niveau:id,libelle', 'section:id,libelle', 'cycle:id,libelle', 'anneeScolaire:id,libelle'])
                    ->select('id', 'libelle_affichage', 'libelle', 'nom', 'code', 'ecole_id', 'campus_id', 'niveau_id', 'section_id', 'cycle_id', 'annee_scolaire_id')
                    ->get()->map(fn($c) => [
                        'id' => $c->id,
                        'libelle' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                        'nom' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                        'ecole_id' => $c->ecole_id,
                        'ecole_nom' => $c->ecole?->nom,
                        'campus_id' => $c->campus_id,
                        'campus_nom' => $c->campus?->nom,
                        'niveau_id' => $c->niveau_id,
                        'niveau_libelle' => $c->niveau?->libelle,
                        'section_id' => $c->section_id,
                        'section_libelle' => $c->section?->libelle,
                        'cycle_id' => $c->cycle_id,
                        'cycle_libelle' => $c->cycle?->libelle,
                        'annee_scolaire_id' => $c->annee_scolaire_id,
                        'annee_scolaire_libelle' => $c->anneeScolaire?->libelle,
                    ])->toArray(),
                'ecoles' => Ecole::whereNull('deleted_at')->select('id', 'nom as libelle')->get()->toArray(),
                'campuses' => Campus::whereNull('deleted_at')->select('id', 'nom as libelle')->get()->toArray(),
                'periodes' => PeriodeColaire::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'natureExamens' => NatureExamen::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'typeExamens' => TypeExamen::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'matieres' => MatiereUnite::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'groupes' => GroupeMatiere::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'enseignants' => Enseignant::with('user')->whereNull('deleted_at')->select('id', 'user_id')->get()->map(fn($e) => [
                    'id' => $e->id,
                    'libelle' => $e->user?->prenoms . ' ' . $e->user?->nom
                ])->toArray(),
            ];

            // Convert note to array for proper Inertia serialization
            $noteArray = [
                'id' => $note->id,
                'apprenant_id' => $note->apprenant_id,
                'evaluation_id' => $note->evaluation_id,
                'annee_scolaire_id' => $note->annee_scolaire_id,
                'section_id' => $note->section_id,
                'cycle_id' => $note->cycle_id,
                'classe_id' => $note->classe_id,
                'ecole_id' => $note->ecole_id,
                'campus_id' => $note->campus_id,
                'periode_id' => $note->periode_id,
                'nature_examen_id' => $note->nature_examen_id,
                'type_examen_id' => $note->type_examen_id,
                'date_examen' => $note->date_examen,
                'matiere_id' => $note->matiere_id,
                'groupe_id' => $note->groupe_id,
                'note_originale' => $note->note_originale,
                'note_sur' => $note->note_sur,
                'note' => $note->note,
                'enseignant_id' => $note->enseignant_id,
                'statut' => $note->statut,
                'remarques' => $note->remarques,
            ];

            \Log::info('🔵 [NoteController::show] Data being sent to Inertia:', [
                'noteId' => $note->id,
                'noteArray' => $noteArray,
                'apparenantCount' => count($apprenants),
                'evaluationsCount' => count($evaluations),
            ]);

            return Inertia::render('Academique::Notes/Show', [
                'title' => __('actions.view'),
                'note' => $noteArray,
                'apprenants' => $apprenants,
                'evaluations' => $evaluations,
                ...$refData,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "NoteController::show", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function edit(Note $note)
    {
        try {
            $apprenants = Apprenant::with('user')->whereNull('deleted_at')
                ->select('id', 'matricule', 'user_id', 'nom', 'prenoms', 'classe_id', 'ecole_id', 'campus_id', 'section_id', 'cycle_id', 'pays_residence_id')
                ->get()->map(fn($a) => [
                'id' => $a->id,
                'libelle' => $a->user
                    ? $a->user->prenoms . ' ' . $a->user->nom . ' (' . $a->matricule . ')'
                    : ($a->prenoms ? $a->prenoms . ' ' : '') . ($a->nom ?: 'Sans nom') . ' (' . $a->matricule . ')',
                'classe_id' => $a->classe_id,
                'ecole_id' => $a->ecole_id,
                'campus_id' => $a->campus_id,
                'section_id' => $a->section_id,
                'cycle_id' => $a->cycle_id,
                'pays_id' => $a->pays_residence_id,
            ])->toArray();

            $evaluations = Evaluation::whereNull('deleted_at')->select('id', 'titre')->get()->map(fn($e) => [
                'id' => $e->id,
                'libelle' => $e->titre
            ])->toArray();

            $refData = [
                'anneesScolaires' => AnneeScolaire::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'sections' => Section::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'cycles' => CycleEnseignement::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'classes' => Classe::whereNull('deleted_at')
                    ->with(['ecole:id,nom', 'campus:id,nom', 'niveau:id,libelle', 'section:id,libelle', 'cycle:id,libelle', 'anneeScolaire:id,libelle'])
                    ->select('id', 'libelle_affichage', 'libelle', 'nom', 'code', 'ecole_id', 'campus_id', 'niveau_id', 'section_id', 'cycle_id', 'annee_scolaire_id')
                    ->get()->map(fn($c) => [
                        'id' => $c->id,
                        'libelle' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                        'nom' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                        'ecole_id' => $c->ecole_id,
                        'ecole_nom' => $c->ecole?->nom,
                        'campus_id' => $c->campus_id,
                        'campus_nom' => $c->campus?->nom,
                        'niveau_id' => $c->niveau_id,
                        'niveau_libelle' => $c->niveau?->libelle,
                        'section_id' => $c->section_id,
                        'section_libelle' => $c->section?->libelle,
                        'cycle_id' => $c->cycle_id,
                        'cycle_libelle' => $c->cycle?->libelle,
                        'annee_scolaire_id' => $c->annee_scolaire_id,
                        'annee_scolaire_libelle' => $c->anneeScolaire?->libelle,
                    ])->toArray(),
                'ecoles' => Ecole::whereNull('deleted_at')->select('id', 'nom as libelle')->get()->toArray(),
                'campuses' => Campus::whereNull('deleted_at')->select('id', 'nom as libelle')->get()->toArray(),
                'periodes' => PeriodeColaire::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'natureExamens' => NatureExamen::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'typeExamens' => TypeExamen::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'matieres' => MatiereUnite::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'groupes' => GroupeMatiere::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
                'enseignants' => Enseignant::with('user')->whereNull('deleted_at')->select('id', 'user_id')->get()->map(fn($e) => [
                    'id' => $e->id,
                    'libelle' => $e->user?->prenoms . ' ' . $e->user?->nom
                ])->toArray(),
            ];

            // Convert note to array for proper Inertia serialization
            $noteArray = [
                'id' => $note->id,
                'apprenant_id' => $note->apprenant_id,
                'evaluation_id' => $note->evaluation_id,
                'annee_scolaire_id' => $note->annee_scolaire_id,
                'section_id' => $note->section_id,
                'cycle_id' => $note->cycle_id,
                'classe_id' => $note->classe_id,
                'ecole_id' => $note->ecole_id,
                'campus_id' => $note->campus_id,
                'periode_id' => $note->periode_id,
                'nature_examen_id' => $note->nature_examen_id,
                'type_examen_id' => $note->type_examen_id,
                'date_examen' => $note->date_examen,
                'matiere_id' => $note->matiere_id,
                'groupe_id' => $note->groupe_id,
                'note_originale' => $note->note_originale,
                'note_sur' => $note->note_sur,
                'note' => $note->note,
                'enseignant_id' => $note->enseignant_id,
                'statut' => $note->statut,
                'remarques' => $note->remarques,
            ];

            return Inertia::render('Academique::Notes/Edit', [
                'title' => __('actions.edit'),
                'note' => $noteArray,
                'apprenants' => $apprenants,
                'evaluations' => $evaluations,
                ...$refData,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "NoteController::edit", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function update(Request $request, Note $note)
    {
        try {
            $validated = $request->validate([
                'apprenant_id' => 'required|exists:apprenants,id',
                'evaluation_id' => 'required|exists:evaluations,id',
                'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
                'section_id' => 'nullable|exists:sections,id',
                'cycle_id' => 'nullable|exists:cycles_enseignement,id',
                'classe_id' => 'nullable|exists:classes,id',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'campus_id' => 'nullable|exists:campuses,id',
                'periode_id' => 'nullable|exists:periodes_colaires,id',
                'nature_examen_id' => 'nullable|exists:natures_examens,id',
                'type_examen_id' => 'nullable|exists:type_examens,id',
                'date_examen' => 'nullable|date',
                'matiere_id' => 'nullable|exists:matieres_unites,id',
                'groupe_id' => 'nullable|exists:groupes_matieres,id',
                'note_originale' => 'required|numeric|min:0',
                'note_sur' => 'required|numeric|min:0.01',
                'enseignant_id' => 'nullable|exists:enseignants,id',
                'statut' => 'required|in:en_attente,validee,rejetee,suspendue',
                'remarques' => 'nullable|string',
            ]);

            // 🔄 Normaliser la note à /20
            $noteOriginale = (float) $validated['note_originale'];
            $noteSur = (float) $validated['note_sur'];
            $noteNormalisee = ($noteOriginale / $noteSur) * 20;

            \Log::info('📊 Note normalization (update)', [
                'original' => $noteOriginale,
                'sur' => $noteSur,
                'normalized' => $noteNormalisee
            ]);

            // Ajouter les valeurs normalisées
            $validated['note'] = round($noteNormalisee, 2);

            $note->update($validated);

            return redirect()->route('academique.notes.show', $note)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "NoteController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function destroy(Note $note)
    {
        try {
            $note->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "NoteController::destroy", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function statut(Note $note)
    {
        try {
            if ($note->trashed()) {
                $note->restore();
            } else {
                $note->delete();
            }

            return redirect()->route('academique.notes.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Academique", "NoteController::statut", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }
}
