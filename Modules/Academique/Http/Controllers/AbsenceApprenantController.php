<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Modules\Academique\Entities\AbsenceApprenant;
use Modules\Academique\Entities\Matiere;
use Modules\Parametrage\Entities\Classe;

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

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('apprenant', function ($q) use ($search) {
                    $q->whereHas('user', function ($user) use ($search) {
                        $user->where('nom', 'like', "%$search%")
                            ->orWhere('prenoms', 'like', "%$search%");
                    });
                });
            }

            if ($request->filled('activate')) {
                $query->where('activate', $request->input('activate'));
            }

            $absences = $query->with(['apprenant', 'apprenant.user'])->paginate(10)->withQueryString();

            // Serialize relationships for Inertia
            $absences->setCollection(
                $absences->getCollection()->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'apprenant' => [
                            'nom' => $item->apprenant?->user?->nom ?? $item->apprenant?->nom ?? '-',
                            'prenoms' => $item->apprenant?->user?->prenoms ?? $item->apprenant?->prenoms ?? '-',
                            'matricule' => $item->apprenant?->matricule ?? '-',
                        ],
                        'date_debut' => $item->date_debut ? $item->date_debut->format('Y-m-d H:i') : '-',
                        'date_fin' => $item->date_fin ? $item->date_fin->format('Y-m-d H:i') : '-',
                        'nombre_heures' => $item->nombre_heures,
                        'motif' => $item->motif ?? '-',
                        'activate' => $item->activate,
                    ];
                })
            );

            return Inertia::render('Academique::AbsencesApprenants/Index', [
                'absencesApprenants' => $absences,
                'filters' => $request->only(['search', 'activate']),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceApprenantController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            // Load apprenants with their user data and classe for auto-fill
            $apprenants = \Modules\Academique\Entities\Apprenant::whereNull('deleted_at')
                ->with(['user', 'classe'])
                ->get()
                ->map(function($apprenant) {
                    return [
                        'id' => $apprenant->id,
                        'nom' => $apprenant->user?->nom ?? '',
                        'prenoms' => $apprenant->user?->prenoms ?? '',
                        'matricule' => $apprenant->matricule ?? '',
                        'classe_id' => $apprenant->classe_id,
                    ];
                })
                ->values();

            $matieres = Matiere::whereNull('deleted_at')
                ->orderBy('libelle')
                ->get(['id', 'libelle'])
                ->map(fn($m) => ['id' => $m->id, 'nom' => $m->libelle])
                ->values();

            $classes = Classe::whereNull('deleted_at')
                ->orderBy('nom')
                ->get(['id', 'nom'])
                ->map(fn($c) => ['id' => $c->id, 'nom' => $c->nom])
                ->values();

            return Inertia::render('Academique::AbsencesApprenants/Create', [
                'title' => __('actions.create'),
                'apprenants' => $apprenants,
                'matieres' => $matieres,
                'classes' => $classes,
            ]);
        } catch (\Throwable $th) {
            \Log::error('AbsenceApprenantController::create ERROR', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            log_error("Academique", "AbsenceApprenantController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'apprenant_id' => 'required|exists:apprenants,id',
            'matiere_id' => 'nullable|exists:matieres,id',
            'classe_id' => 'nullable|exists:classes,id',
            'date_debut' => 'required|date_format:Y-m-d\TH:i',
            'date_fin' => 'required|date_format:Y-m-d\TH:i|after_or_equal:date_debut',
            'nombre_heures' => 'nullable|numeric|min:0',
            'motif' => 'nullable|string',
            'justificatif_path' => 'nullable|array',
            'justificatif_path.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,gif|max:5120',
            'statut' => 'required|in:non_justifiee,justifiee,en_attente',
            'etat' => 'nullable|in:actif,inactif',
        ]);


        try {
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
                    $path = $file->storeAs('absences_apprenants', $filename, 'public');
                    $filePaths[] = $path;
                }
            }

            if (!empty($filePaths)) {
                $validated['justificatif_path'] = $filePaths;
            }

            AbsenceApprenant::create($validated);

            return redirect()->route('academique.absences_apprenants.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceApprenantController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred') . ' : ' . $th->getMessage())->withInput();
        }
    }

    public function show(AbsenceApprenant $absenceApprenant)
    {
        try {
            $apprenants = \Modules\Academique\Entities\Apprenant::whereNull('deleted_at')
                ->with('user:id,nom,prenoms')
                ->select('id', 'nom', 'prenoms', 'user_id')
                ->get()
                ->map(function($apprenant) {
                    return [
                        'id' => $apprenant->id,
                        'nom' => $apprenant->nom ?? $apprenant->user?->nom,
                        'prenoms' => $apprenant->prenoms ?? $apprenant->user?->prenoms,
                    ];
                });
            $matieres = Matiere::whereNull('deleted_at')->orderBy('libelle')->get(['id', 'libelle as nom']);
            $classes = Classe::whereNull('deleted_at')->orderBy('nom')->get(['id', 'nom']);

            // Format dates for Vue datetime-local inputs (Y-m-d\TH:i format)
            $absence = $absenceApprenant->load('apprenant.user')->toArray();
            if ($absenceApprenant->date_debut) {
                $absence['date_debut'] = $absenceApprenant->date_debut->format('Y-m-d\TH:i');
            }
            if ($absenceApprenant->date_fin) {
                $absence['date_fin'] = $absenceApprenant->date_fin->format('Y-m-d\TH:i');
            }

            return Inertia::render('Academique::AbsencesApprenants/Show', [
                'title' => __('actions.view'),
                'absence' => $absence,
                'apprenants' => $apprenants,
                'matieres' => $matieres,
                'classes' => $classes,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceApprenantController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(AbsenceApprenant $absenceApprenant)
    {
        try {
            $apprenants = \Modules\Academique\Entities\Apprenant::whereNull('deleted_at')
                ->with('user:id,nom,prenoms')
                ->select('id', 'nom', 'prenoms', 'user_id')
                ->get()
                ->map(function($apprenant) {
                    return [
                        'id' => $apprenant->id,
                        'nom' => $apprenant->nom ?? $apprenant->user?->nom,
                        'prenoms' => $apprenant->prenoms ?? $apprenant->user?->prenoms,
                    ];
                });
            $matieres = Matiere::whereNull('deleted_at')->orderBy('libelle')->get(['id', 'libelle as nom']);
            $classes = Classe::whereNull('deleted_at')->orderBy('nom')->get(['id', 'nom']);

            // Format dates for Vue datetime-local inputs (Y-m-d\TH:i format)
            $absence = $absenceApprenant->load('apprenant.user')->toArray();
            if ($absenceApprenant->date_debut) {
                $absence['date_debut'] = $absenceApprenant->date_debut->format('Y-m-d\TH:i');
            }
            if ($absenceApprenant->date_fin) {
                $absence['date_fin'] = $absenceApprenant->date_fin->format('Y-m-d\TH:i');
            }

            return Inertia::render('Academique::AbsencesApprenants/Edit', [
                'title' => __('actions.edit'),
                'absence' => $absence,
                'apprenants' => $apprenants,
                'matieres' => $matieres,
                'classes' => $classes,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceApprenantController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, AbsenceApprenant $absenceApprenant)
    {
        $validated = $request->validate([
            'apprenant_id' => 'required|exists:apprenants,id',
            'matiere_id' => 'nullable|exists:matieres,id',
            'classe_id' => 'nullable|exists:classes,id',
            'date_debut' => 'required|date_format:Y-m-d\TH:i',
            'date_fin' => 'required|date_format:Y-m-d\TH:i|after_or_equal:date_debut',
            'nombre_heures' => 'nullable|numeric|min:0',
            'motif' => 'nullable|string',
            'justificatif_path' => 'nullable|array',
            'justificatif_path.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,gif|max:5120',
            'statut' => 'required|in:non_justifiee,justifiee,en_attente',
            'etat' => 'nullable|in:actif,inactif',
        ]);

        try {
            // Handle multiple file uploads
            if ($request->hasFile('justificatif_path')) {
                // Delete old files if they exist
                if ($absenceApprenant->justificatif_path && is_array($absenceApprenant->justificatif_path)) {
                    foreach ($absenceApprenant->justificatif_path as $oldPath) {
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
                    $path = $file->storeAs('absences_apprenants', $filename, 'public');
                    $filePaths[] = $path;
                }

                $validated['justificatif_path'] = $filePaths;
            }

            $absenceApprenant->update($validated);

            return redirect()->route('academique.absences_apprenants.show', $absenceApprenant)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceApprenantController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred') . ' : ' . $th->getMessage())->withInput();
        }
    }

    public function destroy(AbsenceApprenant $absenceApprenant)
    {
        try {
            $absenceApprenant->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceApprenantController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function activate(AbsenceApprenant $absenceApprenant)
    {
        try {
            // Toggle etat between actif and inactif
            $absenceApprenant->etat = $absenceApprenant->etat === 'actif' ? 'inactif' : 'actif';
            $absenceApprenant->save();

            return redirect()->route('academique.absences_apprenants.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceApprenantController::activate", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    /**
     * Get schedule slots for an apprenant to auto-fill absence form
     */
    public function getScheduleSlots(Request $request)
    {
        try {
            $apprenant_id = $request->query('apprenant_id');

            if (!$apprenant_id) {
                return response()->json(['schedules' => []]);
            }

            $apprenant = \Modules\Academique\Entities\Apprenant::find($apprenant_id);

            if (!$apprenant || !$apprenant->classe_id) {
                return response()->json(['schedules' => []]);
            }

            // Get schedule slots for the apprenant's classe (works with weekly schedules)
            // Each course is stored as an individual EmploiTemps record
            // Only show schedules starting from today onwards (exclude past weeks)
            $schedules = \Modules\Academique\Entities\EmploiTemps::where('classe_id', $apprenant->classe_id)
                ->with(['matiere:id,libelle as nom', 'classe:id,nom'])
                ->whereIn('statut', ['valide', 'publie', 'brouillon'])  // Fixed: 'actif' is not a valid statut
                ->whereNull('deleted_at')  // Exclude deactivated (soft-deleted) schedules
                ->where('week_start_date', '>=', now()->toDateString())  // Only show schedules starting from today onwards
                ->select('id', 'classe_id', 'matiere_id', 'jour', 'duree', 'date_debut', 'date_fin')
                ->orderBy('week_start_date')
                ->orderBy('date_debut')
                ->get()
                ->map(function($slot) {
                    return [
                        'id' => $slot->id,
                        'jour' => $slot->jour,
                        'matiere_id' => $slot->matiere_id,
                        'matiere_nom' => $slot->matiere?->nom,
                        'classe_id' => $slot->classe_id,
                        'classe_nom' => $slot->classe?->nom,
                        'duree' => $slot->duree,
                        'date_debut' => $slot->date_debut ? $slot->date_debut->format('Y-m-d\TH:i') : null,
                        'date_fin' => $slot->date_fin ? $slot->date_fin->format('Y-m-d\TH:i') : null,
                    ];
                });

            return response()->json(['schedules' => $schedules]);

        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceApprenantController::getScheduleSlots", $th->getMessage());
            return response()->json(['error' => __('messages.error_occurred')], 500);
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
            log_error("Academique", "AbsenceApprenantController::downloadFile", $th->getMessage());
            return response()->json(['error' => 'Error downloading file'], 500);
        }
    }
}
