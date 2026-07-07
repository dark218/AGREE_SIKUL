<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academique\Entities\Bulletin;
use Modules\Academique\Entities\Apprenant;
use Modules\Academique\Entities\Note;
use Modules\Parametrage\Entities\MatiereUnite;
use Modules\Academique\Services\BulletinService;
use Modules\Academique\Services\AcademicCalculationService;
use Modules\Parametrage\Entities\Classe;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\PeriodeColaire;

class BulletinController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:bulletins-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:bulletins-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:bulletins-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:bulletins-delete', ['only' => ['destroy', 'activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Bulletin::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('apprenant', function ($q) use ($search) {
                    $q->where('nom', 'like', "%$search%")
                        ->orWhere('prenoms', 'like', "%$search%");
                });
            }

            if ($request->filled('decision_conseil')) {
                $query->where('decision_conseil', $request->input('decision_conseil'));
            }

            if ($request->filled('periode')) {
                $query->where('periode', $request->input('periode'));
            }

            $bulletins = $query->with(['apprenant', 'classe', 'anneeScolaire'])->paginate(10)->withQueryString();

            // Use through() to properly serialize relationships for Inertia
            $bulletins->through(function ($bulletin) {
                return [
                    'id' => $bulletin->id,
                    'apprenant_id' => $bulletin->apprenant_id,
                    'classe_id' => $bulletin->classe_id,
                    'annee_scolaire_id' => $bulletin->annee_scolaire_id,
                    'periode' => $bulletin->periode,
                    'moyenne_generale' => $bulletin->moyenne_generale,
                    'rang' => $bulletin->rang,
                    'decision_conseil' => $bulletin->decision_conseil,
                    'apprenant_display' => $bulletin->apprenant
                        ? trim(($bulletin->apprenant->prenoms ?? '') . ' ' . ($bulletin->apprenant->nom ?? ''))
                        : 'N/A',
                    'classe' => $bulletin->classe ? [
                        'id' => $bulletin->classe->id,
                        'nom' => $bulletin->classe->nom,
                    ] : null,
                    'anneeScolaire' => $bulletin->anneeScolaire ? [
                        'id' => $bulletin->anneeScolaire->id,
                        'libelle' => $bulletin->anneeScolaire->libelle,
                    ] : null,
                ];
            });

            return Inertia::render('Academique::Bulletins/Index', [
                'title' => __('common.bulletins'),
                'bulletins' => $bulletins,
                'filters' => $request->only(['search', 'decision_conseil', 'periode']),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "BulletinController::index", $th->getMessage());
            return back()->withErrors(['_error' => 'Erreur lors du chargement des bulletins: ' . $th->getMessage()]);
        }
    }

    public function create()
    {
        try {
            $apprenants = Apprenant::get()->map(function ($apprenant) {
                return [
                    'id' => $apprenant->id,
                    'label' => ($apprenant->prenoms ?? '') . ' ' . ($apprenant->nom ?? ''),
                ];
            })->toArray();

            $classes = Classe::with(['ecole:id,nom', 'campus:id,nom', 'niveau:id,libelle', 'section:id,libelle', 'cycle:id,libelle', 'anneeScolaire:id,libelle'])
                ->select('id', 'nom', 'libelle', 'libelle_affichage', 'ecole_id', 'campus_id', 'niveau_id', 'section_id', 'cycle_id', 'annee_scolaire_id')
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'nom' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                    'libelle' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                    'ecole_id' => $c->ecole_id, 'ecole_nom' => $c->ecole?->nom,
                    'campus_id' => $c->campus_id, 'campus_nom' => $c->campus?->nom,
                    'niveau_id' => $c->niveau_id, 'niveau_libelle' => $c->niveau?->libelle,
                    'section_id' => $c->section_id, 'section_libelle' => $c->section?->libelle,
                    'cycle_id' => $c->cycle_id, 'cycle_libelle' => $c->cycle?->libelle,
                    'annee_scolaire_id' => $c->annee_scolaire_id, 'annee_scolaire_libelle' => $c->anneeScolaire?->libelle,
                ])->toArray();
            $anneesScolaires = AnneeScolaire::select('id', 'libelle')->get()->toArray();

            $periodes = [
                ['id' => 'trimestre1', 'libelle' => 'Trimestre 1'],
                ['id' => 'trimestre2', 'libelle' => 'Trimestre 2'],
                ['id' => 'trimestre3', 'libelle' => 'Trimestre 3'],
                ['id' => 'semestre1', 'libelle' => 'Semestre 1'],
                ['id' => 'semestre2', 'libelle' => 'Semestre 2'],
                ['id' => 'annuel', 'libelle' => 'Annuel'],
            ];

            $decisionConseilOptions = [
                ['id' => 'admis', 'libelle' => 'Admis'],
                ['id' => 'ajourne', 'libelle' => 'Ajourné'],
                ['id' => 'en_attente', 'libelle' => 'En attente'],
            ];

            return Inertia::render('Academique::Bulletins/Create', [
                'title' => __('common.new_bulletin'),
                'apprenants' => $apprenants,
                'classes' => $classes,
                'anneesScolaires' => $anneesScolaires,
                'periodes' => $periodes,
                'decisionConseilOptions' => $decisionConseilOptions,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "BulletinController::create", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur lors du chargement de la page: ' . $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'apprenant_id' => 'required|exists:apprenants,id',
                'classe_id' => 'required|exists:classes,id',
                'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
                'rang' => 'nullable|integer|min:1',
                'periode' => 'nullable|in:trimestre1,trimestre2,trimestre3,semestre1,semestre2,annuel',
                'decision_conseil' => 'nullable|in:admis,ajourne,en_attente',
            ]);

            // Vérifier qu'il n'existe pas déjà un bulletin pour cette combinaison
            $existingBulletin = Bulletin::where('apprenant_id', $validated['apprenant_id'])
                ->where('classe_id', $validated['classe_id'])
                ->where('periode', $validated['periode'] ?? null)
                ->where('annee_scolaire_id', $validated['annee_scolaire_id'])
                ->first();

            if ($existingBulletin) {
                return back()->withInput()->withErrors([
                    '_error' => 'Un bulletin existe déjà pour cet apprenant pour cette période. Veuillez le modifier ou en créer un nouveau avec des paramètres différents.'
                ]);
            }

            $bulletin = Bulletin::create($validated);

            // Calculate moyenne_generale from student's notes
            $bulletinService = new BulletinService();
            $bulletin->moyenne_generale = $bulletinService->calculerMoyenneGenerale($bulletin->id);
            $bulletin->save();

            return redirect()->route('academique.bulletins.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "BulletinController::store", $th->getMessage());
            // Return detailed error message - withErrors for validation errors display
            return back()->withInput()->withErrors(['_error' => 'Erreur lors de la création du bulletin: ' . $th->getMessage()]);
        }
    }

    public function show(Bulletin $bulletin)
    {
        try {
            $bulletin->load('apprenant', 'classe', 'anneeScolaire');

            $apprenants = Apprenant::get()->map(function ($apprenant) {
                return [
                    'id' => $apprenant->id,
                    'label' => ($apprenant->prenoms ?? '') . ' ' . ($apprenant->nom ?? ''),
                ];
            })->toArray();

            $classes = Classe::with(['ecole:id,nom', 'campus:id,nom', 'niveau:id,libelle', 'section:id,libelle', 'cycle:id,libelle', 'anneeScolaire:id,libelle'])
                ->select('id', 'nom', 'libelle', 'libelle_affichage', 'ecole_id', 'campus_id', 'niveau_id', 'section_id', 'cycle_id', 'annee_scolaire_id')
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'nom' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                    'libelle' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                    'ecole_id' => $c->ecole_id, 'ecole_nom' => $c->ecole?->nom,
                    'campus_id' => $c->campus_id, 'campus_nom' => $c->campus?->nom,
                    'niveau_id' => $c->niveau_id, 'niveau_libelle' => $c->niveau?->libelle,
                    'section_id' => $c->section_id, 'section_libelle' => $c->section?->libelle,
                    'cycle_id' => $c->cycle_id, 'cycle_libelle' => $c->cycle?->libelle,
                    'annee_scolaire_id' => $c->annee_scolaire_id, 'annee_scolaire_libelle' => $c->anneeScolaire?->libelle,
                ])->toArray();
            $anneesScolaires = AnneeScolaire::select('id', 'libelle')->get()->toArray();

            $periodes = [
                ['id' => 'trimestre1', 'libelle' => 'Trimestre 1'],
                ['id' => 'trimestre2', 'libelle' => 'Trimestre 2'],
                ['id' => 'trimestre3', 'libelle' => 'Trimestre 3'],
                ['id' => 'semestre1', 'libelle' => 'Semestre 1'],
                ['id' => 'semestre2', 'libelle' => 'Semestre 2'],
                ['id' => 'annuel', 'libelle' => 'Annuel'],
            ];

            $decisionConseilOptions = [
                ['id' => 'admis', 'libelle' => 'Admis'],
                ['id' => 'ajourne', 'libelle' => 'Ajourné'],
                ['id' => 'en_attente', 'libelle' => 'En attente'],
            ];

            return Inertia::render('Academique::Bulletins/Show', [
                'title' => __('common.view_bulletin'),
                'bulletin' => $bulletin,
                'apprenants' => $apprenants,
                'classes' => $classes,
                'anneesScolaires' => $anneesScolaires,
                'periodes' => $periodes,
                'decisionConseilOptions' => $decisionConseilOptions,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "BulletinController::show", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur lors de l\'affichage du bulletin: ' . $th->getMessage()]);
        }
    }

    public function edit(Bulletin $bulletin)
    {
        try {
            $bulletin->load('apprenant', 'classe', 'anneeScolaire');

            $apprenants = Apprenant::get()->map(function ($apprenant) {
                return [
                    'id' => $apprenant->id,
                    'label' => ($apprenant->prenoms ?? '') . ' ' . ($apprenant->nom ?? ''),
                ];
            })->toArray();

            $classes = Classe::with(['ecole:id,nom', 'campus:id,nom', 'niveau:id,libelle', 'section:id,libelle', 'cycle:id,libelle', 'anneeScolaire:id,libelle'])
                ->select('id', 'nom', 'libelle', 'libelle_affichage', 'ecole_id', 'campus_id', 'niveau_id', 'section_id', 'cycle_id', 'annee_scolaire_id')
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'nom' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                    'libelle' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                    'ecole_id' => $c->ecole_id, 'ecole_nom' => $c->ecole?->nom,
                    'campus_id' => $c->campus_id, 'campus_nom' => $c->campus?->nom,
                    'niveau_id' => $c->niveau_id, 'niveau_libelle' => $c->niveau?->libelle,
                    'section_id' => $c->section_id, 'section_libelle' => $c->section?->libelle,
                    'cycle_id' => $c->cycle_id, 'cycle_libelle' => $c->cycle?->libelle,
                    'annee_scolaire_id' => $c->annee_scolaire_id, 'annee_scolaire_libelle' => $c->anneeScolaire?->libelle,
                ])->toArray();
            $anneesScolaires = AnneeScolaire::select('id', 'libelle')->get()->toArray();

            $periodes = [
                ['id' => 'trimestre1', 'libelle' => 'Trimestre 1'],
                ['id' => 'trimestre2', 'libelle' => 'Trimestre 2'],
                ['id' => 'trimestre3', 'libelle' => 'Trimestre 3'],
                ['id' => 'semestre1', 'libelle' => 'Semestre 1'],
                ['id' => 'semestre2', 'libelle' => 'Semestre 2'],
                ['id' => 'annuel', 'libelle' => 'Annuel'],
            ];

            $decisionConseilOptions = [
                ['id' => 'admis', 'libelle' => 'Admis'],
                ['id' => 'ajourne', 'libelle' => 'Ajourné'],
                ['id' => 'en_attente', 'libelle' => 'En attente'],
            ];

            return Inertia::render('Academique::Bulletins/Edit', [
                'title' => __('common.edit_bulletin'),
                'bulletin' => $bulletin,
                'apprenants' => $apprenants,
                'classes' => $classes,
                'anneesScolaires' => $anneesScolaires,
                'periodes' => $periodes,
                'decisionConseilOptions' => $decisionConseilOptions,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "BulletinController::edit", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur lors du chargement de l\'édition: ' . $th->getMessage()]);
        }
    }

    public function update(Request $request, Bulletin $bulletin)
    {
        try {
            $validated = $request->validate([
                'apprenant_id' => 'required|exists:apprenants,id',
                'classe_id' => 'required|exists:classes,id',
                'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
                'rang' => 'nullable|integer|min:1',
                'periode' => 'nullable|in:trimestre1,trimestre2,trimestre3,semestre1,semestre2,annuel',
                'decision_conseil' => 'nullable|in:admis,ajourne,en_attente',
            ]);

            $bulletin->update($validated);

            // Recalculate moyenne_generale from updated notes
            $bulletinService = new BulletinService();
            $bulletin->moyenne_generale = $bulletinService->calculerMoyenneGenerale($bulletin->id);
            $bulletin->save();

            return redirect()->route('academique.bulletins.show', $bulletin)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "BulletinController::update", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur lors de la mise à jour du bulletin: ' . $th->getMessage()]);
        }
    }

    public function destroy(Bulletin $bulletin)
    {
        try {
            $bulletin->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "BulletinController::destroy", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur lors de la suppression du bulletin: ' . $th->getMessage()]);
        }
    }

    public function activate(Bulletin $bulletin)
    {
        try {
            // Toggle statut between actif and inactif
            $bulletin->statut = $bulletin->statut === 'actif' ? 'inactif' : 'actif';
            $bulletin->save();

            return redirect()->route('academique.bulletins.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Academique", "BulletinController::activate", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur lors du changement de statut: ' . $th->getMessage()]);
        }
    }

    /**
     * API: Calcule automatiquement les données d'un bulletin
     * FONCTION CRITIQUE - Récupère les notes et calcule les moyennes
     *
     * Query params:
     * - bulletin_id: ID du bulletin (required)
     * - matiere_id: (Optionnel) Si passé, calcule juste pour cette matière
     *
     * Retourne:
     * - Si matiere_id: { moyenne, coefficient, appreciation, notes }
     * - Sinon: { moyennes_matieres, moyenne_generale, notes }
     */
    public function calculateBulletinData(Request $request)
    {
        try {
            $validated = $request->validate([
                'bulletin_id' => 'required|integer|exists:bulletins,id',
                'matiere_id' => 'nullable|integer|exists:matieres_unites,id',
            ]);

            // Charger le bulletin avec ses relations
            $bulletin = Bulletin::with(['apprenant', 'classe', 'anneeScolaire'])
                ->findOrFail($validated['bulletin_id']);

            // Mapper la période ENUM (trimestre1, trimestre2, etc.) vers periode_id
            $periodeColaire = $this->findPeriodeColaire($bulletin);

            if (!$periodeColaire) {
                return response()->json([
                    'success' => false,
                    'message' => 'Période correspondante non trouvée',
                ], 422);
            }

            $periodeId = $periodeColaire->id;
            $calculationService = new AcademicCalculationService();

            // Si matiere_id passé: calculer juste pour cette matière
            if (!empty($validated['matiere_id'])) {
                $matiereId = $validated['matiere_id'];
                $matiere = MatiereUnite::findOrFail($matiereId);

                // Calculer la moyenne pour cette matière
                $moyenne = $calculationService->calculateMoyenneMatiere(
                    $bulletin->apprenant_id,
                    $matiereId,
                    $periodeId
                );

                // Récupérer les notes de cette matière
                $notes = Note::where('apprenant_id', $bulletin->apprenant_id)
                    ->where('matiere_id', $matiereId)
                    ->where('periode_id', $periodeId)
                    ->where('statut', 'validee')
                    ->with(['evaluation'])
                    ->get();

                // Formater les notes
                $notesFormatted = $notes->map(function ($note) {
                    return [
                        'id' => $note->id,
                        'matiere_id' => $note->matiere_id,
                        'evaluation' => $note->evaluation?->libelle ?? 'N/A',
                        'note' => $note->note,
                        'date' => $note->date_examen?->format('d/m/Y') ?? '',
                    ];
                })->toArray();

                return response()->json([
                    'success' => $moyenne !== null,
                    'message' => $moyenne !== null ? 'Moyenne calculée' : 'Aucune note validée trouvée',
                    'data' => [
                        'matiere_id' => $matiereId,
                        'moyenne' => $moyenne,
                        'coefficient' => $matiere->coefficient,
                        'appreciation' => $moyenne !== null ? $calculationService->determineAppreciation($moyenne) : null,
                        'notes' => $notesFormatted,
                    ],
                ]);
            }

            // Sinon: calculer pour TOUTES les matières
            $bulletinData = $calculationService->generateBulletinData(
                $bulletin->apprenant_id,
                $bulletin->classe_id,
                $periodeId,
                $bulletin->annee_scolaire_id
            );

            // Récupérer les notes source pour affichage (traçabilité)
            $notes = $calculationService->getApprenantNotesByPeriode(
                $bulletin->apprenant_id,
                $periodeId
            );

            // Formater les notes pour le frontend
            $notesFormatted = $notes->map(function ($note) {
                return [
                    'id' => $note->id,
                    'matiere_id' => $note->matiere_id,
                    'evaluation' => $note->evaluation?->libelle ?? 'N/A',
                    'note' => $note->note,
                    'date' => $note->date_examen?->format('d/m/Y') ?? '',
                ];
            })->groupBy('matiere_id')->toArray();

            return response()->json([
                'success' => $bulletinData['success'],
                'message' => $bulletinData['message'],
                'data' => [
                    'moyennes_matieres' => $bulletinData['moyennes_matieres'],
                    'moyenne_generale' => $bulletinData['moyenne_generale'],
                    'stats' => $bulletinData['stats'] ?? [],
                    'notes' => $notesFormatted,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $ve->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('BulletinController::calculateBulletinData', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Trouve la PeriodeColaire correspondant au bulletin
     * Mappe la période ENUM (trimestre1, trimestre2, etc.) vers une PeriodeColaire
     *
     * @param Bulletin $bulletin
     * @return PeriodeColaire|null
     */
    private function findPeriodeColaire(Bulletin $bulletin): ?PeriodeColaire
    {
        // Parser la période ENUM: trimestre1 → ['type' => 'trimestre', 'numero' => 1]
        $periodeMatch = $this->parsePeriode($bulletin->periode);

        if (!$periodeMatch) {
            \Log::warning('BulletinController::findPeriodeColaire - Période invalide', [
                'periode' => $bulletin->periode,
            ]);
            return null;
        }

        // Chercher une PeriodeColaire correspondante
        $periodeColaire = PeriodeColaire::where('annee_scolaire_id', $bulletin->annee_scolaire_id)
            ->where('type_periode', $periodeMatch['type'])
            ->where('numero_ordre', $periodeMatch['numero'])
            ->first();

        if (!$periodeColaire) {
            \Log::warning('BulletinController::findPeriodeColaire - PeriodeColaire non trouvée', [
                'bulletin_id' => $bulletin->id,
                'annee_scolaire_id' => $bulletin->annee_scolaire_id,
                'type_periode' => $periodeMatch['type'],
                'numero_ordre' => $periodeMatch['numero'],
                'bulletin_periode_enum' => $bulletin->periode,
            ]);
        }

        return $periodeColaire;
    }

    /**
     * Parse une période ENUM en type et numéro
     * Ex: 'trimestre1' → ['type' => 'trimestre', 'numero' => 1]
     *
     * @param string $periode
     * @return array|null
     */
    private function parsePeriode(string $periode): ?array
    {
        $mapping = [
            'trimestre1' => ['type' => 'trimestre', 'numero' => 1],
            'trimestre2' => ['type' => 'trimestre', 'numero' => 2],
            'trimestre3' => ['type' => 'trimestre', 'numero' => 3],
            'semestre1' => ['type' => 'semestre', 'numero' => 1],
            'semestre2' => ['type' => 'semestre', 'numero' => 2],
            'annuel' => ['type' => 'annuel', 'numero' => 1],
        ];

        return $mapping[$periode] ?? null;
    }

    /**
     * Templates de bulletin disponibles
     */
    private const TEMPLATES = [
        'bulletin'             => 'Standard (actuel)',
        'bulletin-primaire'    => 'Primaire (CP → CM2)',
        'bulletin-secondaire'  => 'Secondaire (Collège / Lycée)',
        'bulletin-universite'  => 'Université (CC + Galop + Examen)',
        'releve-universite'    => 'Relevé Universitaire (Master, UE/EC, Crédits)',
        'releve-licence'       => 'Relevé Licence (UE Fondamentales / Découverte)',
        'grille-prof'          => 'Grille Enseignant (Partielles, Devoirs, Exam Final)',
    ];

    public function exportPdf(Request $request, Bulletin $bulletin)
    {
        try {
            // Utiliser le service pour générer les données PDF
            $bulletinService = new BulletinService();
            $data = $bulletinService->genererDonneesPdf($bulletin->id);

            // Charger la classe pour l'auto-détection
            $bulletin->load('classe');

            // Sélection du template (paramètre GET ?template=xxx ou auto-détection)
            $templateKey = $request->query('template') ?? $this->autoDetectTemplate($bulletin);

            // Vérifier que le template existe
            if (!array_key_exists($templateKey, self::TEMPLATES)) {
                $templateKey = 'bulletin';
            }

            // Générer le PDF avec le template sélectionné
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('academique::pdf.' . $templateKey, [
                'data' => $data,
            ]);

            // Nom du fichier
            $nom = $data['apprenant']['nom'] ?? 'apprenant';
            $prenoms = $data['apprenant']['prenoms'] ?? '';
            $filename = $templateKey . '-' . trim($prenoms . '-' . $nom) . '-' . date('Y-m-d') . '.pdf';

            return $pdf->download($filename);

        } catch (\Throwable $th) {
            log_error("Academique", "BulletinController::exportPdf", $th->getMessage());
            return back()->withErrors(['_error' => 'Erreur lors de la génération du PDF: ' . $th->getMessage()]);
        }
    }

    /**
     * Auto-détection du template selon le cycle de la classe
     */
    private function autoDetectTemplate(Bulletin $bulletin): string
    {
        try {
            $classe = $bulletin->classe;
            if (!$classe) return 'bulletin';

            // Détecter via le cycle du niveau
            $niveau = \DB::table('niveaux')->where('id', $classe->niveau_id)->first();
            $cycle = $niveau->cycle ?? null;

            if ($cycle === 'primaire') return 'bulletin-primaire';
            if (in_array($cycle, ['secondaire', 'college', 'lycee'])) return 'bulletin-secondaire';
            if (in_array($cycle, ['superieur', 'universite', 'master'])) return 'bulletin-universite';

            // Fallback par nom de classe
            $nom = strtolower($classe->nom ?? '');
            if (preg_match('/^(cp|ce[12]|cm[12])/', $nom)) return 'bulletin-primaire';
            if (preg_match('/^(6|5|4|3)(e|è|eme|ème)/', $nom)) return 'bulletin-secondaire';
            if (preg_match('/^(seconde|première|1ere|terminale|2nde|1ère|term)/', $nom)) return 'bulletin-secondaire';

            return 'bulletin';
        } catch (\Throwable $e) {
            return 'bulletin';
        }
    }

    /**
     * Liste des templates disponibles (pour l'API front)
     */
    public static function getAvailableTemplates(): array
    {
        return self::TEMPLATES;
    }
}
