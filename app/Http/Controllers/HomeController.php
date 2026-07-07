<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();

        // Redirection POS
        if ($user->hasRole(config('appconstants.role.caissier'))) {
            return redirect()->route('session-caisse-caissier.index');
        }
        if ($user->hasRole(config('appconstants.role.manager'))) {
            return redirect()->route('session-caisse-manager.index');
        }

        // Dashboard Parent
        if ($user->hasRole('parent')) {
            return $this->parentDashboard($user);
        }

        // Dashboard Élève
        if ($user->hasRole('eleve')) {
            return $this->eleveDashboard($user);
        }

        // Dashboard Admin (défaut)
        return $this->adminDashboard($user);
    }

    /**
     * Dashboard Parent — affiche les infos de ses enfants
     */
    private function parentDashboard($user)
    {
        $tuteurs = DB::table('tuteurs')
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->get();

        $enfants = [];
        foreach ($tuteurs as $tuteur) {
            $apprenant = DB::table('apprenants')->where('id', $tuteur->apprenant_id)->first();
            if (!$apprenant) continue;

            $enfants[] = $this->buildApprenantData($apprenant);
        }

        return Inertia::render('Dashboard/ParentDashboard', [
            'user' => [
                'nom' => $user->nom,
                'prenoms' => $user->prenoms,
            ],
            'enfants' => $enfants,
        ]);
    }

    /**
     * Dashboard Élève — affiche ses propres infos
     */
    private function eleveDashboard($user)
    {
        $apprenant = DB::table('apprenants')->where('user_id', $user->id)->first();

        $data = $apprenant ? $this->buildApprenantData($apprenant) : [];

        return Inertia::render('Dashboard/EleveDashboard', [
            'user' => [
                'nom' => $user->nom,
                'prenoms' => $user->prenoms,
            ],
            'apprenant' => $data,
        ]);
    }

    /**
     * Construit les données complètes d'un apprenant
     */
    private function buildApprenantData($apprenant): array
    {
        $classe = DB::table('classes')->where('id', $apprenant->classe_id)->first();
        $ecole = $classe ? DB::table('ecoles')->where('id', $classe->ecole_id)->first() : null;

        // Dernier bulletin
        $dernierBulletin = DB::table('bulletins')
            ->where('apprenant_id', $apprenant->id)
            ->orderBy('created_at', 'desc')
            ->first();

        // Toutes les notes
        $notes = DB::table('notes')
            ->where('apprenant_id', $apprenant->id)
            ->leftJoin('matieres_unites', 'notes.matiere_id', '=', 'matieres_unites.id')
            ->select('notes.*', 'matieres_unites.libelle as matiere_libelle')
            ->orderBy('notes.created_at', 'desc')
            ->limit(15)
            ->get();

        // Bulletins
        $bulletins = DB::table('bulletins')
            ->where('apprenant_id', $apprenant->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $periodeLabels = [
            'trimestre1' => '1er Trimestre', 'trimestre2' => '2e Trimestre', 'trimestre3' => '3e Trimestre',
            'semestre1' => '1er Semestre', 'semestre2' => '2e Semestre', 'annuel' => 'Annuel',
        ];

        // Absences
        $absences = DB::table('absences_apprenants')
            ->where('apprenant_id', $apprenant->id)
            ->leftJoin('matieres_unites', 'absences_apprenants.matiere_id', '=', 'matieres_unites.id')
            ->select('absences_apprenants.*', 'matieres_unites.libelle as matiere_libelle')
            ->orderBy('absences_apprenants.date_debut', 'desc')
            ->limit(10)
            ->get();

        // Emploi du temps
        $edt = DB::table('emplois_temps')
            ->where('classe_id', $apprenant->classe_id)
            ->leftJoin('matieres_unites', 'emplois_temps.matiere_id', '=', 'matieres_unites.id')
            ->leftJoin('enseignants', 'emplois_temps.enseignant_id', '=', 'enseignants.id')
            ->select('emplois_temps.*', 'matieres_unites.libelle as matiere_libelle', 'enseignants.nom as ens_nom', 'enseignants.prenoms as ens_prenoms')
            ->orderByRaw("FIELD(emplois_temps.jour, 'lundi','mardi','mercredi','jeudi','vendredi','samedi')")
            ->orderBy('emplois_temps.date_debut')
            ->limit(30)
            ->get();

        return [
            'id' => $apprenant->id,
            'nom' => $apprenant->nom,
            'prenoms' => $apprenant->prenoms,
            'matricule' => $apprenant->matricule,
            'date_naissance' => $apprenant->date_naissance ? Carbon::parse($apprenant->date_naissance)->format('d/m/Y') : '—',
            'sexe' => $apprenant->sexe ?? '—',
            'classe' => $classe->nom ?? '—',
            'ecole' => $ecole->nom ?? '—',
            'moyenne_generale' => $dernierBulletin->moyenne_generale ?? null,
            'rang' => $dernierBulletin->rang ?? null,
            'absences_count' => DB::table('absences_apprenants')->where('apprenant_id', $apprenant->id)->count(),
            'notes_count' => DB::table('notes')->where('apprenant_id', $apprenant->id)->count(),

            'dernieres_notes' => $notes->map(fn($n) => [
                'id' => $n->id,
                'matiere' => $n->matiere_libelle ?? '—',
                'note' => $n->note,
                'note_sur' => $n->note_sur ?? 20,
                'date' => $n->date_examen ? Carbon::parse($n->date_examen)->format('d/m/Y') : '—',
                'statut' => $n->statut ?? '—',
            ])->toArray(),

            'bulletins' => $bulletins->map(fn($b) => [
                'id' => $b->id,
                'periode' => $periodeLabels[$b->periode] ?? $b->periode,
                'moyenne' => $b->moyenne_generale,
                'rang' => $b->rang,
                'decision' => $b->decision_conseil,
            ])->toArray(),

            'absences' => $absences->map(fn($a) => [
                'id' => $a->id,
                'date' => $a->date_debut ? Carbon::parse($a->date_debut)->format('d/m/Y') : '—',
                'matiere' => $a->matiere_libelle ?? '—',
                'heures' => $a->nombre_heures ?? 0,
                'motif' => $a->motif ?? '—',
                'statut' => $a->statut ?? '—',
            ])->toArray(),

            'emploi_du_temps' => $edt->map(fn($e) => [
                'id' => $e->id,
                'jour' => ucfirst($e->jour ?? ''),
                'heure_debut' => $e->date_debut ? substr($e->date_debut, 11, 5) : '—',
                'heure_fin' => $e->date_fin ? substr($e->date_fin, 11, 5) : '—',
                'matiere' => $e->matiere_libelle ?? '—',
                'enseignant' => trim(($e->ens_prenoms ?? '') . ' ' . ($e->ens_nom ?? '')),
            ])->toArray(),
        ];
    }

    /**
     * Dashboard Admin
     */
    private function adminDashboard($user)
    {
        $totalApprenants = DB::table('apprenants')->whereNull('deleted_at')->count();
        $totalEnseignants = DB::table('enseignants')->whereNull('deleted_at')->count();
        $totalClasses = DB::table('classes')->whereNull('deleted_at')->count();
        $totalEcoles = DB::table('ecoles')->whereNull('deleted_at')->count();
        $totalUsers = DB::table('users')->whereNull('deleted_at')->count();

        $filles = DB::table('apprenants')->whereNull('deleted_at')->where('sexe', 'F')->count();
        $garcons = $totalApprenants - $filles;

        $totalExamens = DB::table('examens_en_ligne')->whereNull('deleted_at')->count();
        $examensPublies = DB::table('examens_en_ligne')->whereNull('deleted_at')->whereIn('etat', ['publie', 'en_cours'])->count();
        $totalCompositions = DB::table('tentatives_examen')->whereNull('deleted_at')->whereIn('statut', ['corrigee', 'soumise'])->count();
        $moyenneExamens = DB::table('tentatives_examen')->whereNull('deleted_at')->where('statut', 'corrigee')->avg('score');

        $totalNotes = DB::table('notes')->whereNull('deleted_at')->count();
        $totalBulletins = DB::table('bulletins')->whereNull('deleted_at')->count();

        $parClasse = DB::table('apprenants')
            ->whereNull('apprenants.deleted_at')
            ->whereNotNull('classe_id')
            ->join('classes', 'apprenants.classe_id', '=', 'classes.id')
            ->select('classes.nom', DB::raw('COUNT(*) as total'))
            ->groupBy('classes.nom')
            ->get()
            ->map(fn($c) => ['label' => $c->nom, 'value' => $c->total])
            ->toArray();

        $parEcole = DB::table('apprenants')
            ->whereNull('apprenants.deleted_at')
            ->whereNotNull('classe_id')
            ->join('classes', 'apprenants.classe_id', '=', 'classes.id')
            ->join('ecoles', 'classes.ecole_id', '=', 'ecoles.id')
            ->select('ecoles.nom', DB::raw('COUNT(*) as total'))
            ->groupBy('ecoles.nom')
            ->get()
            ->map(fn($e) => ['label' => $e->nom, 'value' => $e->total])
            ->toArray();

        $recentActivities = DB::table('tentatives_examen')
            ->join('examens_en_ligne', 'tentatives_examen.examen_en_ligne_id', '=', 'examens_en_ligne.id')
            ->join('apprenants', 'tentatives_examen.apprenant_id', '=', 'apprenants.id')
            ->whereNull('tentatives_examen.deleted_at')
            ->orderBy('tentatives_examen.created_at', 'desc')
            ->limit(5)
            ->select('apprenants.prenoms', 'apprenants.nom', 'examens_en_ligne.titre as examen', 'tentatives_examen.score', 'tentatives_examen.statut', 'tentatives_examen.created_at')
            ->get()
            ->map(fn($t) => [
                'apprenant' => $t->prenoms . ' ' . $t->nom,
                'examen' => $t->examen,
                'score' => $t->score,
                'statut' => $t->statut,
                'date' => Carbon::parse($t->created_at)->diffForHumans(),
            ])
            ->toArray();

        $evolutionMensuelle = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $count = DB::table('apprenants')
                ->whereNull('deleted_at')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            $evolutionMensuelle[] = [
                'mois' => $date->locale('fr')->isoFormat('MMM YYYY'),
                'value' => $count,
            ];
        }

        $parMatiere = DB::table('notes')
            ->whereNull('notes.deleted_at')
            ->join('matieres_unites', 'notes.matiere_id', '=', 'matieres_unites.id')
            ->select('matieres_unites.libelle', DB::raw('COUNT(*) as total'))
            ->groupBy('matieres_unites.libelle')
            ->orderBy('total', 'desc')
            ->limit(8)
            ->get()
            ->map(fn($m) => ['label' => $m->libelle, 'value' => $m->total])
            ->toArray();

        $admis = DB::table('tentatives_examen')
            ->whereNull('deleted_at')
            ->where('statut', 'corrigee')
            ->whereRaw('score >= (SELECT COALESCE(note_minimum_passage, note_maximum/2) FROM examens_en_ligne WHERE examens_en_ligne.id = tentatives_examen.examen_en_ligne_id)')
            ->count();
        $refuses = $totalCompositions - $admis;

        return Inertia::render('Dashboard/Home', [
            'stats' => [
                'totalApprenants' => $totalApprenants,
                'totalEnseignants' => $totalEnseignants,
                'totalClasses' => $totalClasses,
                'totalEcoles' => $totalEcoles,
                'totalUsers' => $totalUsers,
                'filles' => $filles,
                'garcons' => $garcons,
                'tauxFilles' => $totalApprenants > 0 ? round(($filles / $totalApprenants) * 100, 1) : 0,
                'totalExamens' => $totalExamens,
                'examensPublies' => $examensPublies,
                'totalCompositions' => $totalCompositions,
                'moyenneExamens' => $moyenneExamens ? round($moyenneExamens, 2) : 0,
                'totalNotes' => $totalNotes,
                'totalBulletins' => $totalBulletins,
            ],
            'parClasse' => $parClasse,
            'parEcole' => $parEcole,
            'recentActivities' => $recentActivities,
            'evolutionMensuelle' => $evolutionMensuelle,
            'parMatiere' => $parMatiere,
            'resultatsExamens' => ['admis' => $admis, 'refuses' => $refuses],
        ]);
    }
}
