<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Academique\Entities\Absence;
use Modules\Academique\Entities\Apprenant;
use Modules\Parametrage\Entities\Classe;

class AbsenceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:absences-list', ['only' => ['index']]);
        $this->middleware('permission.check:absences-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:absences-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:absences-delete', ['only' => ['destroy', 'statut']]);
    }

    /**
     * Index: Grille des absences par semaine (5 semaines × 7 jours)
     */
    public function index(Request $request): Response
    {
        try {
            \Log::info('🔍 AbsenceController::index() START');

            $query = Absence::with([
                'apprenant',
                'classe',
            ])->whereNull('deleted_at');

            \Log::info('📊 Total absences in DB:', ['count' => Absence::count()]);
            \Log::info('📊 Non-deleted absences:', ['count' => $query->count()]);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('apprenant', function ($q) use ($search) {
                    $q->where('matricule', 'like', "%{$search}%")
                      ->orWhere('nom', 'like', "%{$search}%")
                      ->orWhere('prenoms', 'like', "%{$search}%");
                });
            }

            if ($request->filled('classe_id')) {
                $query->where('classe_id', $request->classe_id);
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            $absences = $query->orderBy('date_absence', 'desc')->paginate(10)->withQueryString();

            \Log::info('📋 Paginated absences count:', ['count' => $absences->count()]);
            \Log::info('📋 Raw paginated data:', $absences->items());

            // Serialize relationships for Inertia
            try {
                $absences->setCollection(
                    $absences->getCollection()->map(function ($item) {
                        // Calculer le nombre d'heures à partir de time_from et time_to
                        $nombreHeures = null;
                        if ($item->time_from && $item->time_to) {
                            $timeFrom = \Carbon\Carbon::parse($item->time_from);
                            $timeTo = \Carbon\Carbon::parse($item->time_to);
                            $nombreHeures = round($timeFrom->diffInHours($timeTo) + ($timeFrom->diffInMinutes($timeTo) % 60) / 60, 2);
                        }

                        \Log::info('🔄 Mapping absence item:', [
                            'id' => $item->id,
                            'time_from' => $item->time_from,
                            'time_to' => $item->time_to,
                            'nombre_heures_calculé' => $nombreHeures,
                            'nombre_heures_original' => $item->nombre_heures,
                        ]);

                        return [
                            'id' => $item->id,
                            'apprenant' => [
                                'matricule' => $item->apprenant?->matricule ?? '-',
                                'nom' => $item->apprenant?->nom ?? '-',
                                'prenoms' => $item->apprenant?->prenoms ?? '-',
                            ],
                            'classe' => [
                                'nom' => $item->classe?->nom ?? '-',
                                'libelle' => $item->classe?->libelle ?? '-',
                            ],
                            'date_absence' => $item->date_absence ? $item->date_absence->format('Y-m-d') : '-',
                            'week_number' => $item->week_number,
                            'day_of_week' => $item->day_of_week ?? '-',
                            'nombre_heures' => $nombreHeures ?? $item->nombre_heures,
                            'statut' => $item->statut,
                            'etat' => $item->etat,
                            'deleted_at' => $item->deleted_at,
                        ];
                    })
                );
                \Log::info('✅ Serialization successful');
            } catch (\Throwable $mapError) {
                \Log::error('❌ Serialization error:', ['error' => $mapError->getMessage()]);
                throw $mapError;
            }

            $classes = Classe::select('id', 'nom as libelle')->orderBy('nom')->get();

            \Log::info('✅ About to render with absences:', ['data' => $absences]);

            return Inertia::render('Academique::Absences/Index', [
                'title' => __('Absences'),
                'absences' => $absences,
                'classes' => $classes,
                'filters' => $request->only(['search', 'classe_id', 'statut']),
            ]);
        } catch (\Throwable $th) {
            \Log::error('❌ AbsenceController::index ERROR', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);
            log_error("Academique", "AbsenceController::index", $th->getMessage());
            return Inertia::render('Academique::Absences/Index', [
                'title' => __('Absences'),
                'absences' => [],
                'classes' => [],
                'filters' => [],
            ]);
        }
    }

    private function buildAbsencesGrid($apprenantId): array
    {
        $grid = [];
        $days = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];

        for ($week = 1; $week <= 5; $week++) {
            $grid[$week] = [];
            foreach ($days as $day) {
                $absence = Absence::where('apprenant_id', $apprenantId)
                    ->where('week_number', $week)
                    ->where('day_of_week', $day)
                    ->first();
                $grid[$week][$day] = $absence;
            }
        }

        return $grid;
    }

    public function create(Request $request)
    {
        try {
            $apprenantId = $request->query('apprenant_id');
            $week = $request->query('week', 1);
            $day = $request->query('day', 'lundi');

            $apprenant = null;
            if ($apprenantId) {
                $apprenant = Apprenant::with([
                    'classe',
                    'section',
                    'cycle',
                    'ecole',
                    'campus',
                ])->findOrFail($apprenantId);
            }

            $apprenants = Apprenant::with(['classe', 'section', 'cycle', 'ecole', 'campus'])
                ->select('id', 'matricule', 'nom', 'prenoms')
                ->selectRaw("CONCAT(matricule, ' - ', prenoms, ' ', nom) as libelle")
                ->orderBy('matricule')
                ->get();

            $classes = Classe::select('id', 'nom as libelle')->orderBy('nom')->get();

            return Inertia::render('Academique::Absences/Create', [
                'title' => __('Créer une absence'),
                'apprenant' => $apprenant,
                'apprenants' => $apprenants,
                'classes' => $classes,
                'week' => $week,
                'day' => $day,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceController::create", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function show($id): Response
    {
        try {
            $absence = Absence::with(['apprenant', 'classe'])->findOrFail($id);

            // Format date_absence to Y-m-d for input type="date"
            if ($absence->date_absence) {
                $absence->date_absence = $absence->date_absence->format('Y-m-d');
            }

            $apprenants = Apprenant::with(['classe', 'section', 'cycle', 'ecole', 'campus'])
                ->select('id', 'matricule', 'nom', 'prenoms')
                ->selectRaw("CONCAT(matricule, ' - ', prenoms, ' ', nom) as libelle")
                ->orderBy('matricule')
                ->get();

            $classes = Classe::select('id', 'nom as libelle')->orderBy('nom')->get();

            return Inertia::render('Academique::Absences/Show', [
                'title' => __('Détails de l\'absence'),
                'absence' => $absence,
                'apprenants' => $apprenants,
                'classes' => $classes,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceController::show", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'apprenant_id' => 'required|exists:apprenants,id',
                'classe_id' => 'required|exists:classes,id',
                'week_number' => 'required|integer|min:1|max:5',
                'date_absence' => 'required|date',
                'day_of_week' => 'required|in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche',
                'time_from' => 'nullable|date_format:H:i',
                'time_to' => 'nullable|date_format:H:i',
                'nombre_heures' => 'nullable|numeric|min:0|max:24',
                'motif' => 'nullable|string|max:1000',
                'statut' => 'required|in:en_attente,justifiee,non_justifiee,partiellement_justifiee',
                'etat' => 'required|in:actif,inactif',
            ]);

            Absence::create($validated);

            return redirect()->route('academique.absences.index')
                ->with('success', __('messages.created'));
        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceController::store", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function edit($id): Response
    {
        try {
            $absence = Absence::with(['apprenant', 'classe'])->findOrFail($id);

            // Format date_absence to Y-m-d for input type="date"
            if ($absence->date_absence) {
                $absence->date_absence = $absence->date_absence->format('Y-m-d');
            }

            $apprenants = Apprenant::with(['classe', 'section', 'cycle', 'ecole', 'campus'])
                ->select('id', 'matricule', 'nom', 'prenoms')
                ->selectRaw("CONCAT(matricule, ' - ', prenoms, ' ', nom) as libelle")
                ->orderBy('matricule')
                ->get();

            $classes = Classe::select('id', 'nom as libelle')->orderBy('nom')->get();

            return Inertia::render('Academique::Absences/Edit', [
                'title' => __('Modifier l\'absence'),
                'absence' => $absence,
                'apprenants' => $apprenants,
                'classes' => $classes,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceController::edit", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function update($id, Request $request)
    {
        try {
            $absence = Absence::findOrFail($id);

            $validated = $request->validate([
                'classe_id' => 'required|exists:classes,id',
                'week_number' => 'required|integer|min:1|max:5',
                'date_absence' => 'required|date',
                'day_of_week' => 'required|in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche',
                'time_from' => 'nullable|date_format:H:i',
                'time_to' => 'nullable|date_format:H:i',
                'nombre_heures' => 'nullable|numeric|min:0|max:24',
                'motif' => 'nullable|string|max:1000',
                'statut' => 'required|in:en_attente,justifiee,non_justifiee,partiellement_justifiee',
                'etat' => 'required|in:actif,inactif',
            ]);

            $absence->update($validated);

            return redirect()->route('academique.absences.index')
                ->with('success', __('messages.updated'));
        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $absence = Absence::findOrFail($id);
            $absence->delete();

            return redirect()->route('academique.absences.index')
                ->with('success', __('messages.deleted'));
        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceController::destroy", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function statut($id)
    {
        try {
            $absence = Absence::findOrFail($id);
            $absence->etat = $absence->etat === 'actif' ? 'inactif' : 'actif';
            $absence->save();

            return redirect()->route('academique.absences.index')
                ->with('success', __('messages.updated'));
        } catch (\Throwable $th) {
            log_error("Academique", "AbsenceController::statut", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }
}
