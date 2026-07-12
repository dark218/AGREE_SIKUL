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
            'classes' => Classe::orderBy('nom')->get(['id', 'nom']),
            'matieres' => MatiereUnite::orderBy('libelle')->get(['id', 'libelle as nom']),
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

    private function rules(): array
    {
        return [
            'apprenant_id' => 'required|exists:apprenants,id',
            'classe_id' => 'nullable|exists:classes,id',
            'matiere_id' => 'nullable|exists:matieres_unites,id',
            'date_debut' => 'required|date_format:Y-m-d\TH:i',
            'date_fin' => 'required|date_format:Y-m-d\TH:i|after_or_equal:date_debut',
            'nombre_heures' => 'nullable|numeric|min:0',
            'motif' => 'nullable|string',
            'statut' => 'required|in:en_attente,validee,rejetee',
            'justificatif_path' => 'nullable|array',
            'justificatif_path.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,gif|max:5120',
            'etat' => 'nullable|in:actif,inactif',
        ];
    }

    private function storeFiles(Request $request): array
    {
        $paths = [];
        if ($request->hasFile('justificatif_path')) {
            $files = $request->file('justificatif_path');
            if (!is_array($files)) {
                $files = [$files];
            }
            foreach ($files as $file) {
                $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                $paths[] = $file->storeAs('absences_apprenants', $filename, 'public');
            }
        }
        return $paths;
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->rules());
            $files = $this->storeFiles($request);
            if (!empty($files)) {
                $validated['justificatif_path'] = $files;
            }

            AbsenceApprenant::create($validated);

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
                $validated['justificatif_path'] = $this->storeFiles($request);
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
