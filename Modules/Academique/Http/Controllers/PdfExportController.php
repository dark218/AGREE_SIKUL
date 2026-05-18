<?php

namespace Modules\Academique\Http\Controllers;

use Modules\Academique\Entities\Apprenant;
use Modules\Academique\Entities\Note;
use Modules\Academique\Entities\Matiere;
use Modules\Academique\Entities\MoyenneMatiere;
use Modules\Academique\Entities\Inscription;
use Modules\Academique\Entities\Presence;
use Modules\Academique\Entities\AbsenceApprenant;
use Modules\Academique\Entities\AbsenceEnseignant;
use Modules\Parametrage\Entities\Classe;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfExportController extends Controller
{
    /**
     * Liste des apprenants d'une classe
     */
    public function listeApprenants(Request $request)
    {
        $classeId = $request->input('classe_id');
        $query = Apprenant::query()->orderBy('nom');

        if ($classeId) {
            $query->where('classe_id', $classeId);
        }

        $apprenants = $query->get();
        $classe = $classeId ? Classe::with('ecole')->find($classeId) : null;

        $data = [
            'apprenants' => $apprenants->map(fn($a) => [
                'matricule' => $a->matricule,
                'nom' => $a->nom,
                'prenoms' => $a->prenoms,
                'sexe' => $a->sexe ?? '-',
                'date_naissance' => $a->date_naissance?->format('d/m/Y') ?? '-',
                'telephone' => $a->telephone ?? '-',
                'tuteur' => $a->nom_tuteur ?? $a->nom_pere ?? '-',
                'statut' => $a->statut ?? 'actif',
            ])->toArray(),
            'classe' => $classe?->nom,
            'ecole' => $classe?->ecole?->nom,
            'annee' => '-',
            'total' => $apprenants->count(),
            'garcons' => $apprenants->where('sexe', 'M')->count(),
            'filles' => $apprenants->where('sexe', 'F')->count(),
            'date' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('academique::pdf.liste-apprenants', $data);
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('liste_apprenants_' . ($classe?->nom ?? 'all') . '.pdf');
    }

    /**
     * Fiche individuelle d'un apprenant
     */
    public function ficheApprenant(Apprenant $apprenant)
    {
        $apprenant->load(['classe.ecole']);

        $data = [
            'apprenant' => $apprenant,
            'classe' => $apprenant->classe?->nom,
            'ecole' => $apprenant->classe?->ecole?->nom,
            'adresse' => $apprenant->classe?->ecole?->adresse,
            'annee' => '-',
            'date' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('academique::pdf.fiche-apprenant', $data);
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('fiche_' . $apprenant->matricule . '.pdf');
    }

    /**
     * Attestation d'inscription
     */
    public function attestationInscription(Apprenant $apprenant)
    {
        $apprenant->load(['classe.ecole']);

        $data = [
            'apprenant' => $apprenant,
            'classe' => $apprenant->classe?->nom,
            'ecole' => $apprenant->classe?->ecole?->nom,
            'adresse' => $apprenant->classe?->ecole?->adresse,
            'telephone' => $apprenant->classe?->ecole?->telephone ?? '',
            'annee' => '-',
            'statut' => 'Inscrit(e)',
            'date' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('academique::pdf.attestation-inscription', $data);
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('attestation_' . $apprenant->matricule . '.pdf');
    }

    /**
     * Relevé de notes complet d'une classe (toutes matières)
     */
    public function releveNotesClasse(Request $request)
    {
        $classeId = $request->input('classe_id');
        $classe = Classe::with('ecole')->findOrFail($classeId);

        $matieresList = Matiere::orderBy('libelle')->get();
        $apprenants = Apprenant::where('classe_id', $classeId)->orderBy('nom')->get();

        $notes = Note::where('classe_id', $classeId)
            ->get()
            ->groupBy('apprenant_id');

        $lignes = $apprenants->map(function ($a) use ($notes, $matieresList) {
            $notesApprenant = $notes->get($a->id, collect());
            $notesParMatiere = [];
            foreach ($matieresList as $mat) {
                $noteMat = $notesApprenant->where('matiere_id', $mat->id)->avg('note_originale');
                $notesParMatiere[] = $noteMat ? round($noteMat, 1) : null;
            }
            $notesValides = collect($notesParMatiere)->filter(fn($n) => $n !== null);
            $moyenne = $notesValides->count() > 0 ? round($notesValides->avg(), 2) : null;

            return [
                'matricule' => $a->matricule,
                'nom' => $a->nom . ' ' . $a->prenoms,
                'notes' => $notesParMatiere,
                'moyenne' => $moyenne,
                'rang' => 0,
            ];
        })->sortByDesc('moyenne')->values();

        // Calculer les rangs
        $lignes = $lignes->map(function ($l, $idx) {
            $l['rang'] = $l['moyenne'] !== null ? $idx + 1 : '-';
            return $l;
        });

        $moyennes = $lignes->pluck('moyenne')->filter();
        $nbAdmis = $moyennes->filter(fn($m) => $m >= 10)->count();

        $data = [
            'classe' => $classe->nom,
            'ecole' => $classe->ecole?->nom,
            'annee' => '-',
            'matieres' => $matieresList->pluck('libelle')->toArray(),
            'lignes' => $lignes->toArray(),
            'nbApprenants' => $apprenants->count(),
            'nbAdmis' => $nbAdmis,
            'nbRefuses' => $moyennes->count() - $nbAdmis,
            'tauxReussite' => $moyennes->count() > 0 ? round(($nbAdmis / $moyennes->count()) * 100) : 0,
            'moyenneClasse' => $moyennes->count() > 0 ? round($moyennes->avg(), 2) : 0,
            'date' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('academique::pdf.releve-notes-classe', $data);
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('releve_notes_' . $classe->nom . '.pdf');
    }

    /**
     * Feuille de présence
     */
    public function feuillePresence(Request $request)
    {
        $classeId = $request->input('classe_id');
        $classe = $classeId ? Classe::with('ecole')->find($classeId) : null;

        $query = Presence::query()->with(['apprenant', 'seance']);
        if ($classeId) {
            $query->whereHas('apprenant', fn($q) => $q->where('classe_id', $classeId));
        }

        $presences = $query->orderBy('created_at', 'desc')->limit(100)->get();

        $lignes = $presences->map(fn($p) => [
            'matricule' => $p->apprenant?->matricule ?? '-',
            'nom' => ($p->apprenant?->nom ?? '') . ' ' . ($p->apprenant?->prenoms ?? ''),
            'statut' => $p->statut ?? 'present',
            'heure' => $p->heure_arrivee ?? '',
            'observation' => $p->observation ?? '',
        ]);

        $data = [
            'classe' => $classe?->nom,
            'ecole' => $classe?->ecole?->nom,
            'matiere' => '',
            'dateSeance' => now()->format('d/m/Y'),
            'lignes' => $lignes->toArray(),
            'total' => $lignes->count(),
            'presents' => $lignes->where('statut', 'present')->count(),
            'absents' => $lignes->where('statut', 'absent')->count(),
            'retards' => $lignes->where('statut', 'retard')->count(),
            'date' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('academique::pdf.feuille-presence', $data);
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('feuille_presence_' . ($classe?->nom ?? 'all') . '.pdf');
    }

    /**
     * Rapport d'absences apprenants
     */
    public function rapportAbsencesApprenants(Request $request)
    {
        $classeId = $request->input('classe_id');
        $classe = $classeId ? Classe::with('ecole')->find($classeId) : null;

        $query = AbsenceApprenant::query()->with('apprenant');
        if ($classeId) {
            $query->whereHas('apprenant', fn($q) => $q->where('classe_id', $classeId));
        }

        $absences = $query->orderBy('created_at', 'desc')->limit(200)->get();

        $data = [
            'type' => 'apprenant',
            'classe' => $classe?->nom,
            'ecole' => $classe?->ecole?->nom,
            'periode' => '',
            'absences' => $absences->map(fn($a) => [
                'matricule' => $a->apprenant?->matricule ?? '-',
                'nom' => ($a->apprenant?->nom ?? '') . ' ' . ($a->apprenant?->prenoms ?? ''),
                'date' => $a->date_absence?->format('d/m/Y') ?? $a->created_at?->format('d/m/Y') ?? '-',
                'heures' => $a->nombre_heures ?? '-',
                'justifiee' => (bool) $a->justifiee,
                'motif' => $a->motif ?? '-',
                'observation' => $a->observation ?? '',
            ])->toArray(),
            'totalAbsences' => $absences->count(),
            'justifiees' => $absences->where('justifiee', true)->count(),
            'nonJustifiees' => $absences->where('justifiee', '!=', true)->count(),
            'totalHeures' => $absences->sum('nombre_heures') ?: '-',
            'date' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('academique::pdf.rapport-absences', $data);
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('rapport_absences_apprenants.pdf');
    }

    /**
     * Rapport d'absences enseignants
     */
    public function rapportAbsencesEnseignants(Request $request)
    {
        $absences = AbsenceEnseignant::with('enseignant')
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get();

        $data = [
            'type' => 'enseignant',
            'classe' => '',
            'ecole' => '',
            'periode' => '',
            'absences' => $absences->map(fn($a) => [
                'matricule' => '-',
                'nom' => ($a->enseignant?->prenoms ?? '') . ' ' . ($a->enseignant?->nom ?? ''),
                'date' => $a->date_absence?->format('d/m/Y') ?? $a->created_at?->format('d/m/Y') ?? '-',
                'heures' => $a->nombre_heures ?? '-',
                'justifiee' => (bool) $a->justifiee,
                'motif' => $a->motif ?? '-',
                'observation' => $a->observation ?? '',
            ])->toArray(),
            'totalAbsences' => $absences->count(),
            'justifiees' => $absences->where('justifiee', true)->count(),
            'nonJustifiees' => $absences->where('justifiee', '!=', true)->count(),
            'totalHeures' => $absences->sum('nombre_heures') ?: '-',
            'date' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('academique::pdf.rapport-absences', $data);
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('rapport_absences_enseignants.pdf');
    }

    /**
     * Relevé des moyennes par matière
     */
    public function releveMoyennesMatieres(Request $request)
    {
        $classeId = $request->input('classe_id');
        $classe = $classeId ? Classe::with('ecole')->find($classeId) : null;

        $query = MoyenneMatiere::with(['apprenant', 'matiere']);
        if ($classeId) {
            $query->whereHas('apprenant', fn($q) => $q->where('classe_id', $classeId));
        }

        $moyennes = $query->get()->groupBy('apprenant_id');

        $matieresList = Matiere::orderBy('libelle')->get();

        $lignes = $moyennes->map(function ($mmGroup) use ($matieresList) {
            $apprenant = $mmGroup->first()->apprenant;
            $notesParMatiere = [];
            foreach ($matieresList as $mat) {
                $mm = $mmGroup->firstWhere('matiere_id', $mat->id);
                $notesParMatiere[] = $mm ? round($mm->moyenne, 1) : null;
            }
            $valides = collect($notesParMatiere)->filter(fn($n) => $n !== null);
            $moyenne = $valides->count() > 0 ? round($valides->avg(), 2) : null;

            return [
                'matricule' => $apprenant?->matricule ?? '-',
                'nom' => ($apprenant?->nom ?? '') . ' ' . ($apprenant?->prenoms ?? ''),
                'notes' => $notesParMatiere,
                'moyenne' => $moyenne,
                'rang' => 0,
            ];
        })->sortByDesc('moyenne')->values();

        $lignes = $lignes->map(function ($l, $idx) {
            $l['rang'] = $l['moyenne'] !== null ? $idx + 1 : '-';
            return $l;
        });

        $moyennesValides = $lignes->pluck('moyenne')->filter();
        $nbAdmis = $moyennesValides->filter(fn($m) => $m >= 10)->count();

        $data = [
            'classe' => $classe?->nom ?? 'Toutes classes',
            'ecole' => $classe?->ecole?->nom ?? '',
            'annee' => '',
            'matieres' => $matieresList->pluck('libelle')->toArray(),
            'lignes' => $lignes->toArray(),
            'nbApprenants' => $lignes->count(),
            'nbAdmis' => $nbAdmis,
            'nbRefuses' => $moyennesValides->count() - $nbAdmis,
            'tauxReussite' => $moyennesValides->count() > 0 ? round(($nbAdmis / $moyennesValides->count()) * 100) : 0,
            'moyenneClasse' => $moyennesValides->count() > 0 ? round($moyennesValides->avg(), 2) : 0,
            'date' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('academique::pdf.releve-notes-classe', $data);
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('releve_moyennes_' . ($classe?->nom ?? 'all') . '.pdf');
    }
}
