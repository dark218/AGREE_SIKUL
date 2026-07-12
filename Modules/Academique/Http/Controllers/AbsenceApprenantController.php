<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Modules\Academique\Entities\AbsenceApprenant;
use Modules\Academique\Entities\Apprenant;
use Modules\Parametrage\Entities\Classe;
use Modules\Parametrage\Entities\MatiereUnite;

class AbsenceApprenantController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:absences_apprenants-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:absences_apprenants-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:absences_apprenants-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:absences_apprenants-delete', ['only' => ['destroy', 'activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = AbsenceApprenant::query();

            if ($request->filled('apprenant')) {
                $terme = $request->input('apprenant');
                $query->whereHas('apprenant', function ($q) use ($terme) {
                    $q->where('nom', 'like', "%$terme%")
                      ->orWhere('prenoms', 'like', "%$terme%")
                      ->orWhere('matricule', 'like', "%$terme%");
                });
            }
            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }
            if ($request->filled('etat')) {
                $query->where('etat', $request->input('etat'));
            }

            $absences = $query->with(['apprenant', 'classe'])->paginate(10)->withQueryString();

            $absences->setCollection(
                $absences->getCollection()->map(fn ($item) => [
                    'id' => $item->id,
                    'apprenant' => [
                        'nom' => $item->apprenant?->nom ?? '-',
                        'prenoms' => $item->apprenant?->prenoms ?? '-',
                        'matricule' => $item->apprenant?->matricule ?? '-',
                    ],
                    'classe' => ['nom' => $item->classe?->nom ?? '-'],
                    'date_debut' => $item->date_debut ? $item->date_debut->format('Y-m-d H:i') : '-',
                    'date_fin' => $item->date_fin ? $item->date_fin->format('Y-m-d H:i') : '-',
                    'nombre_heures' => $item->nombre_heures,
                    'motif' => $item->motif ?? '-',
                    'statut' => $item->statut,
                    'etat' => $item->etat,
                ])
            );

            return Inertia::render('Academique::AbsencesApprenants/Index', [
                'title' => 'Absences des apprenants',
                'absencesApprenants' => $absences,
                'filters' => $request->only(['apprenant', 'statut', 'etat']),
            ]);
        } catch (\Throwable $th) {
            log_error('Academique', 'AbsenceApprenantController::index', $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    private function formOptions(): array
    {
        return [
            'apprenants' => Apprenant::orderBy('nom')->get(['id', 'nom', 'prenoms', 'matricule', 'classe_id'])
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'nom' => $a->nom ?? '',
                    'prenoms' => $a->prenoms ?? '',
                    'matricule' => $a->matricule ?? '',
                    'classe_id' => $a->classe_id,
                ])->toArray(),
            // Classes portent le contexte (école/campus/année) pour la cascade.
            'classes' => Classe::orderBy('nom')->get(['id', 'nom', 'ecole_id', 'campus_id', 'annee_scolaire_id'])
                ->map(fn ($c) => ['id' => $c->id, 'libelle' => $c->nom, 'ecole_id' => $c->ecole_id, 'campus_id' => $c->campus_id, 'annee_scolaire_id' => $c->annee_scolaire_id])->toArray(),
            'matieres' => MatiereUnite::orderBy('libelle')->get(['id', 'libelle'])->map(fn ($m) => ['id' => $m->id, 'libelle' => $m->libelle])->toArray(),
            'ecoles' => \Modules\Parametrage\Entities\Ecole::orderBy('nom')->get(['id', 'nom'])->map(fn ($e) => ['id' => $e->id, 'libelle' => $e->nom])->toArray(),
            'campuses' => \Modules\Parametrage\Entities\Campus::orderBy('nom')->get(['id', 'nom'])->map(fn ($c) => ['id' => $c->id, 'libelle' => $c->nom])->toArray(),
            'anneesScolaires' => \Modules\Parametrage\Entities\AnneeScolaire::orderBy('libelle', 'desc')->get(['id', 'libelle'])->toArray(),
            'enseignants' => \Modules\Academique\Entities\Enseignant::orderBy('nom')->get(['id', 'nom', 'prenoms'])
                ->map(fn ($e) => ['id' => $e->id, 'libelle' => trim(($e->nom ?? '') . ' ' . ($e->prenoms ?? ''))])->toArray(),
        ];
    }

    public function create()
    {
        try {
            return Inertia::render('Academique::AbsencesApprenants/Create', array_merge($this->formOptions(), [
                'title' => 'Nouvelle absence apprenant',
            ]));
        } catch (\Throwable $th) {
            log_error('Academique', 'AbsenceApprenantController::create', $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    /** Règles pour l'édition d'UNE absence (formulaire single). */
    private function rules(): array
    {
        return [
            'apprenant_id' => 'required|exists:apprenants,id',
            'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
            'classe_id' => 'nullable|exists:classes,id',
            'ecole_id' => 'nullable|exists:ecoles,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'matiere_id' => 'nullable|exists:matieres_unites,id',
            'enseignant_id' => 'nullable|exists:enseignants,id',
            'date_debut' => 'required|date_format:Y-m-d\TH:i',
            'date_fin' => 'required|date_format:Y-m-d\TH:i|after_or_equal:date_debut',
            'nombre_heures' => 'nullable|numeric|min:0',
            'motif' => 'nullable|string',
            'commentaire' => 'nullable|string',
            'statut' => 'required|in:en_attente,validee,rejetee',
            'justificatif_path' => 'nullable|array',
            'justificatif_path.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,gif|max:5120',
            'etat' => 'nullable|in:actif,inactif',
        ];
    }

    private function storeFilesFor($files): array
    {
        $paths = [];
        if (!$files) {
            return $paths;
        }
        if (!is_array($files)) {
            $files = [$files];
        }
        foreach ($files as $file) {
            if (!$file) {
                continue;
            }
            $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
            $paths[] = $file->storeAs('absences_apprenants', $filename, 'public');
        }
        return $paths;
    }

    /**
     * Saisie EN LOT : un contexte commun (année/classe/école/campus/matière/
     * enseignant + dates/statut) appliqué à plusieurs apprenants. L'onglet
     * Justificatifs fournit, par apprenant, un commentaire + des fichiers + état.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
                'classe_id' => 'nullable|exists:classes,id',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'campus_id' => 'nullable|exists:campuses,id',
                'matiere_id' => 'nullable|exists:matieres_unites,id',
                'enseignant_id' => 'nullable|exists:enseignants,id',
                'date_debut' => 'required|date_format:Y-m-d\TH:i',
                'date_fin' => 'required|date_format:Y-m-d\TH:i|after_or_equal:date_debut',
                'nombre_heures' => 'nullable|numeric|min:0',
                'statut' => 'required|in:en_attente,validee,rejetee',
                'apprenants' => 'required|array|min:1',
                'apprenants.*' => 'exists:apprenants,id',
                'justificatifs' => 'nullable|array',
            ]);

            $contexte = [
                'annee_scolaire_id' => $validated['annee_scolaire_id'] ?? null,
                'classe_id' => $validated['classe_id'] ?? null,
                'ecole_id' => $validated['ecole_id'] ?? null,
                'campus_id' => $validated['campus_id'] ?? null,
                'matiere_id' => $validated['matiere_id'] ?? null,
                'enseignant_id' => $validated['enseignant_id'] ?? null,
                'date_debut' => $validated['date_debut'],
                'date_fin' => $validated['date_fin'],
                'nombre_heures' => $validated['nombre_heures'] ?? null,
                'statut' => $validated['statut'],
            ];

            $justificatifs = $request->input('justificatifs', []);

            \DB::transaction(function () use ($request, $validated, $contexte, $justificatifs) {
                foreach ($validated['apprenants'] as $apprenantId) {
                    $j = $justificatifs[$apprenantId] ?? [];
                    $files = $request->file("justificatifs.$apprenantId.files");
                    AbsenceApprenant::create(array_merge($contexte, [
                        'apprenant_id' => $apprenantId,
                        'commentaire' => $j['commentaire'] ?? null,
                        'etat' => ($j['etat'] ?? 'actif') === 'inactif' ? 'inactif' : 'actif',
                        'justificatif_path' => $this->storeFilesFor($files) ?: null,
                    ]));
                }
            });

            return redirect()->route('academique.absences_apprenants.index')
                ->with('success', __('messages.created_successfully'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            log_error('Academique', 'AbsenceApprenantController::store', $th->getMessage());
            return back()->with('error', __('messages.error_occurred') . ' : ' . $th->getMessage())->withInput();
        }
    }

    public function show(AbsenceApprenant $absenceApprenant)
    {
        try {
            $data = $absenceApprenant->load(['apprenant', 'classe'])->toArray();
            $data['date_debut'] = $absenceApprenant->date_debut?->format('Y-m-d\TH:i');
            $data['date_fin'] = $absenceApprenant->date_fin?->format('Y-m-d\TH:i');

            return Inertia::render('Academique::AbsencesApprenants/Show', array_merge($this->formOptions(), [
                'title' => 'Détail absence apprenant',
                'absenceApprenant' => $data,
            ]));
        } catch (\Throwable $th) {
            log_error('Academique', 'AbsenceApprenantController::show', $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(AbsenceApprenant $absenceApprenant)
    {
        try {
            $data = $absenceApprenant->load(['apprenant', 'classe'])->toArray();
            $data['date_debut'] = $absenceApprenant->date_debut?->format('Y-m-d\TH:i');
            $data['date_fin'] = $absenceApprenant->date_fin?->format('Y-m-d\TH:i');

            return Inertia::render('Academique::AbsencesApprenants/Edit', array_merge($this->formOptions(), [
                'title' => 'Modifier absence apprenant',
                'absenceApprenant' => $data,
            ]));
        } catch (\Throwable $th) {
            log_error('Academique', 'AbsenceApprenantController::edit', $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, AbsenceApprenant $absenceApprenant)
    {
        try {
            $validated = $request->validate($this->rules());

            if ($request->hasFile('justificatif_path')) {
                if (is_array($absenceApprenant->justificatif_path)) {
                    foreach ($absenceApprenant->justificatif_path as $old) {
                        if (Storage::disk('public')->exists($old)) {
                            Storage::disk('public')->delete($old);
                        }
                    }
                }
                $validated['justificatif_path'] = $this->storeFilesFor($request->file('justificatif_path'));
            }

            $absenceApprenant->update($validated);

            return redirect()->route('academique.absences_apprenants.show', $absenceApprenant)
                ->with('success', __('messages.updated_successfully'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            log_error('Academique', 'AbsenceApprenantController::update', $th->getMessage());
            return back()->with('error', __('messages.error_occurred') . ' : ' . $th->getMessage())->withInput();
        }
    }

    public function destroy(AbsenceApprenant $absenceApprenant)
    {
        try {
            $absenceApprenant->delete();
            return back()->with('success', __('messages.deleted_successfully'));
        } catch (\Throwable $th) {
            log_error('Academique', 'AbsenceApprenantController::destroy', $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function activate(AbsenceApprenant $absenceApprenant)
    {
        try {
            $absenceApprenant->etat = $absenceApprenant->etat === 'actif' ? 'inactif' : 'actif';
            $absenceApprenant->save();
            return redirect()->route('academique.absences_apprenants.index')
                ->with('success', __('messages.status_changed'));
        } catch (\Throwable $th) {
            log_error('Academique', 'AbsenceApprenantController::activate', $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
