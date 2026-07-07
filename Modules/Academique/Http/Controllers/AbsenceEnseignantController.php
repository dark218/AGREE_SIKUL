<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Modules\Academique\Entities\AbsenceEnseignant;
use Modules\Academique\Entities\Enseignant;
use Modules\Parametrage\Entities\MatiereUnite;
use Modules\Parametrage\Entities\Classe;

class AbsenceEnseignantController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:absences_enseignants-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:absences_enseignants-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:absences_enseignants-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:absences_enseignants-delete', ['only' => ['destroy', 'activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = AbsenceEnseignant::query();

            // Filtre par enseignant
            if ($request->filled('enseignant')) {
                $enseignant = $request->input('enseignant');
                $query->whereHas('enseignant', function ($q) use ($enseignant) {
                    $q->where('nom', 'like', "%$enseignant%")
                      ->orWhere('prenoms', 'like', "%$enseignant%");
                });
            }

            // Filtre par statut (validation status: en_attente, validee, rejetee)
            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            // Filtre par etat (actif/inactif)
            if ($request->filled('etat')) {
                $query->where('etat', $request->input('etat'));
            }

            $absences = $query->with(['enseignant'])
                ->paginate(10)->withQueryString();

            // Serialize relationships for Inertia
            $absences->setCollection(
                $absences->getCollection()->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'enseignant' => [
                            'nom' => $item->enseignant?->nom ?? '-',
                            'prenoms' => $item->enseignant?->prenoms ?? '-',
                        ],
                        'date_debut' => $item->date_debut ? $item->date_debut->format('Y-m-d H:i') : '-',
                        'date_fin' => $item->date_fin ? $item->date_fin->format('Y-m-d H:i') : '-',
                        'nombre_heures' => $item->nombre_heures,
                        'motif' => $item->motif ?? '-',
                        'statut' => $item->statut,
                        'etat' => $item->etat,
                    ];
                })
            );

            return Inertia::render('Academique::AbsencesEnseignants/Index', [
                'title' => __('common.absence_enseignant'),
                'absencesEnseignants' => $absences,
                'filters' => $request->only(['enseignant', 'statut', 'etat']),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceEnseignantController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            $enseignants = Enseignant::select('id', 'nom', 'prenoms')->get()
                ->map(function ($enseignant) {
                    return [
                        'id' => $enseignant->id,
                        'nom' => $enseignant->nom ?? '',
                        'prenoms' => $enseignant->prenoms ?? '',
                    ];
                })->toArray();

            $matieres = MatiereUnite::orderBy('libelle')->get(['id', 'libelle as nom']);
            $classes = Classe::orderBy('nom')->get(['id', 'nom']);

            return Inertia::render('Academique::AbsencesEnseignants/Create', [
                'title' => __('common.new_absence_enseignant'),
                'enseignants' => $enseignants,
                'matieres' => $matieres,
                'classes' => $classes,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceEnseignantController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'enseignant_id' => 'required|exists:enseignants,id',
                'matiere_id' => 'nullable|exists:matieres_unites,id',
                'classe_id' => 'nullable|exists:classes,id',
                'date_debut' => 'required|date_format:Y-m-d\TH:i',
                'date_fin' => 'required|date_format:Y-m-d\TH:i|after_or_equal:date_debut',
                'nombre_heures' => 'nullable|numeric|min:0',
                'motif' => 'nullable|string',
                'statut' => 'required|in:en_attente,validee,rejetee',
                'justificatif_path' => 'nullable|array',
                'justificatif_path.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,gif|max:5120',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            // Handle multiple file uploads
            $filePaths = [];
            if ($request->hasFile('justificatif_path')) {
                $files = $request->file('justificatif_path');
                // Ensure it's an array
                if (!is_array($files)) {
                    $files = [$files];
                }

                foreach ($files as $file) {
                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('absences_enseignants', $filename, 'public');
                    $filePaths[] = $path;
                }
            }

            if (!empty($filePaths)) {
                $validated['justificatif_path'] = $filePaths;
            }

            AbsenceEnseignant::create($validated);

            return redirect()->route('academique.absences_enseignants.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceEnseignantController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred') . ' : ' . $th->getMessage())->withInput();
        }
    }

    public function show(AbsenceEnseignant $absenceEnseignant)
    {
        try {
            $enseignants = Enseignant::select('id', 'nom', 'prenoms')->get()
                ->map(function ($enseignant) {
                    return [
                        'id' => $enseignant->id,
                        'nom' => $enseignant->nom ?? '',
                        'prenoms' => $enseignant->prenoms ?? '',
                    ];
                })->toArray();

            $matieres = MatiereUnite::orderBy('libelle')->get(['id', 'libelle as nom']);
            $classes = Classe::orderBy('nom')->get(['id', 'nom']);

            // Format dates for Vue datetime-local inputs (Y-m-d\TH:i format)
            $absence = $absenceEnseignant->load('enseignant')->toArray();
            if ($absenceEnseignant->date_debut) {
                $absence['date_debut'] = $absenceEnseignant->date_debut->format('Y-m-d\TH:i');
            }
            if ($absenceEnseignant->date_fin) {
                $absence['date_fin'] = $absenceEnseignant->date_fin->format('Y-m-d\TH:i');
            }

            return Inertia::render('Academique::AbsencesEnseignants/Show', [
                'title' => __('common.view_absence_enseignant'),
                'absenceEnseignant' => $absence,
                'enseignants' => $enseignants,
                'matieres' => $matieres,
                'classes' => $classes,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceEnseignantController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(AbsenceEnseignant $absenceEnseignant)
    {
        try {
            $enseignants = Enseignant::select('id', 'nom', 'prenoms')->get()
                ->map(function ($enseignant) {
                    return [
                        'id' => $enseignant->id,
                        'nom' => $enseignant->nom ?? '',
                        'prenoms' => $enseignant->prenoms ?? '',
                    ];
                })->toArray();

            $matieres = MatiereUnite::orderBy('libelle')->get(['id', 'libelle as nom']);
            $classes = Classe::orderBy('nom')->get(['id', 'nom']);

            // Format dates for Vue datetime-local inputs (Y-m-d\TH:i format)
            $absence = $absenceEnseignant->load('enseignant')->toArray();
            if ($absenceEnseignant->date_debut) {
                $absence['date_debut'] = $absenceEnseignant->date_debut->format('Y-m-d\TH:i');
            }
            if ($absenceEnseignant->date_fin) {
                $absence['date_fin'] = $absenceEnseignant->date_fin->format('Y-m-d\TH:i');
            }

            return Inertia::render('Academique::AbsencesEnseignants/Edit', [
                'title' => __('common.edit_absence_enseignant'),
                'absenceEnseignant' => $absence,
                'enseignants' => $enseignants,
                'matieres' => $matieres,
                'classes' => $classes,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceEnseignantController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, AbsenceEnseignant $absenceEnseignant)
    {
        try {
            $validated = $request->validate([
                'enseignant_id' => 'required|exists:enseignants,id',
                'matiere_id' => 'nullable|exists:matieres_unites,id',
                'classe_id' => 'nullable|exists:classes,id',
                'date_debut' => 'required|date_format:Y-m-d\TH:i',
                'date_fin' => 'required|date_format:Y-m-d\TH:i|after_or_equal:date_debut',
                'nombre_heures' => 'nullable|numeric|min:0',
                'motif' => 'nullable|string',
                'statut' => 'required|in:en_attente,validee,rejetee',
                'justificatif_path' => 'nullable|array',
                'justificatif_path.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,gif|max:5120',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            // Handle multiple file uploads
            if ($request->hasFile('justificatif_path')) {
                // Delete old files if they exist
                if ($absenceEnseignant->justificatif_path && is_array($absenceEnseignant->justificatif_path)) {
                    foreach ($absenceEnseignant->justificatif_path as $oldPath) {
                        if (\Storage::disk('public')->exists($oldPath)) {
                            \Storage::disk('public')->delete($oldPath);
                        }
                    }
                }

                $filePaths = [];
                $files = $request->file('justificatif_path');
                // Ensure it's an array
                if (!is_array($files)) {
                    $files = [$files];
                }

                foreach ($files as $file) {
                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('absences_enseignants', $filename, 'public');
                    $filePaths[] = $path;
                }

                $validated['justificatif_path'] = $filePaths;
            }

            $absenceEnseignant->update($validated);

            return redirect()->route('academique.absences_enseignants.show', $absenceEnseignant)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceEnseignantController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred') . ' : ' . $th->getMessage())->withInput();
        }
    }

    public function destroy(AbsenceEnseignant $absenceEnseignant)
    {
        try {
            $absenceEnseignant->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceEnseignantController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function activate(AbsenceEnseignant $absenceEnseignant)
    {
        try {
            // Toggle etat between actif and inactif
            $absenceEnseignant->etat = $absenceEnseignant->etat === 'actif' ? 'inactif' : 'actif';
            $absenceEnseignant->save();

            return redirect()->route('academique.absences_enseignants.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceEnseignantController::activate", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    /**
     * Get schedule slots for a teacher
     */
    public function getScheduleSlots(Request $request)
    {
        try {
            $enseignantId = $request->input('enseignant_id');

            if (!$enseignantId) {
                return response()->json(['schedules' => []]);
            }

            // Get schedule slots for the specific teacher
            // Filter by teacher's affectations
            $schedules = \Modules\Academique\Entities\EmploiTemps::query()
                ->with(['matiere:id,libelle', 'classe:id,nom'])
                ->where('enseignant_id', $enseignantId)  // Filter by teacher
                ->whereIn('statut', ['brouillon', 'valide', 'publie'])
                ->select('id', 'classe_id', 'matiere_id', 'jour', 'duree', 'date_debut', 'date_fin')
                ->orderBy('date_debut')
                ->get()
                ->map(function($slot) {
                    return [
                        'id' => $slot->id,
                        'jour' => $slot->jour,
                        'matiere_id' => $slot->matiere_id,
                        'matiere_libelle' => $slot->matiere?->libelle ?? 'N/A',
                        'classe_id' => $slot->classe_id,
                        'classe_nom' => $slot->classe?->nom ?? 'N/A',
                        'duree' => $slot->duree,
                        'date_debut' => $slot->date_debut ? $slot->date_debut->format('Y-m-d\TH:i') : null,
                        'date_fin' => $slot->date_fin ? $slot->date_fin->format('Y-m-d\TH:i') : null,
                    ];
                });

            return response()->json(['schedules' => $schedules]);
        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceEnseignantController::getScheduleSlots", $th->getMessage());
            return response()->json(['schedules' => [], 'error' => $th->getMessage()], 500);
        }
    }

    /**
     * Télécharger/visualiser un fichier de justificatif d'absence
     */
    public function downloadFile($filePath)
    {
        try {
            // Sécuriser le chemin du fichier pour éviter les traversées de répertoires
            $filePath = str_replace('..', '', $filePath);

            // Vérifier si le fichier existe
            if (!Storage::disk('public')->exists($filePath)) {
                return response()->json(['error' => 'File not found'], 404);
            }

            // Retourner le fichier
            return Storage::disk('public')->download($filePath);
        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceEnseignantController::downloadFile", $th->getMessage());
            return response()->json(['error' => 'Error downloading file'], 500);
        }
    }
}
