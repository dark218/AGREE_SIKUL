<?php

namespace Modules\Academique\Services;

use Modules\Academique\Entities\Bulletin;
use Modules\Academique\Entities\MoyenneMatiere;

class BulletinService
{
    /**
     * Calculer la moyenne générale pondérée pour un bulletin
     */
    public function calculerMoyenneGenerale($bulletinId): float
    {
        $bulletin = Bulletin::find($bulletinId);
        if (!$bulletin) return 0;

        $moyennes = MoyenneMatiere::where('bulletin_id', $bulletinId)->get();

        if ($moyennes->isEmpty()) return 0;

        $totalPondere = 0;
        $totalCoefficient = 0;

        foreach ($moyennes as $moyenne) {
            $totalPondere += ($moyenne->moyenne ?? 0) * ($moyenne->coefficient ?? 1);
            $totalCoefficient += ($moyenne->coefficient ?? 1);
        }

        $moyenneGenerale = $totalCoefficient > 0 ? $totalPondere / $totalCoefficient : 0;

        // Mettre à jour la moyenne générale dans le bulletin
        $bulletin->update(['moyenne_generale' => $moyenneGenerale]);

        return round($moyenneGenerale, 2);
    }

    /**
     * Calculer le rang du bulletin dans sa classe
     */
    public function calculerRang($bulletinId, $classeId): int
    {
        $bulletin = Bulletin::find($bulletinId);
        if (!$bulletin) return 0;

        // Récupérer tous les bulletins de la même classe et période
        $bulletins = Bulletin::where('classe_id', $classeId)
            ->where('periode', $bulletin->periode)
            ->whereNotNull('moyenne_generale')
            ->orderBy('moyenne_generale', 'desc')
            ->get();

        $rang = 1;
        foreach ($bulletins as $index => $b) {
            if ($b->id === $bulletinId) {
                $rang = $index + 1;
                break;
            }
        }

        // Mettre à jour le rang dans le bulletin
        $bulletin->update(['rang' => $rang]);

        return $rang;
    }

    /**
     * Générer les données pour l'affichage du PDF
     */
    public function genererDonneesPdf($bulletinId): array
    {
        $bulletin = Bulletin::with([
            'apprenant.user',
            'classe',
            'anneeScolaire'
        ])->find($bulletinId);

        if (!$bulletin) {
            return [];
        }

        // Calculer ou mettre à jour les métriques
        $moyenneGenerale = $this->calculerMoyenneGenerale($bulletinId);
        $rang = $this->calculerRang($bulletinId, $bulletin->classe_id);

        // Récupérer les moyennes par matière
        $moyennes = MoyenneMatiere::where('bulletin_id', $bulletinId)
            ->with('matiere')
            ->get();

        // Nom apprenant : d'abord depuis apprenants, fallback user
        $apprenant = $bulletin->apprenant;
        $nom = $apprenant->nom ?? $apprenant->user?->nom ?? '';
        $prenoms = $apprenant->prenoms ?? $apprenant->user?->prenoms ?? '';

        // Effectif classe
        $effectifClasse = \DB::table('apprenants')->where('classe_id', $bulletin->classe_id)->whereNull('deleted_at')->count();

        // Stats classe (moyenne, min, max)
        $classeStats = Bulletin::where('classe_id', $bulletin->classe_id)
            ->where('periode', $bulletin->periode)
            ->where('annee_scolaire_id', $bulletin->annee_scolaire_id)
            ->selectRaw('AVG(moyenne_generale) as avg_moy, MIN(moyenne_generale) as min_moy, MAX(moyenne_generale) as max_moy')
            ->first();

        // Ecole
        $ecole = $bulletin->classe?->ecole_id
            ? \DB::table('ecoles')->where('id', $bulletin->classe->ecole_id)->first()
            : null;

        // Libellé période
        $periodeLabels = [
            'trimestre1' => '1er Trimestre', 'trimestre2' => '2ème Trimestre', 'trimestre3' => '3ème Trimestre',
            'semestre1' => '1er Semestre', 'semestre2' => '2ème Semestre', 'annuel' => 'Annuel',
        ];

        // §3.3 : Presence = source unique. On compte les séances où l'apprenant
        //        est marqué absent/malade/permis. 'permis' = justifié (autorisation
        //        officielle). 'malade' = justifié via certificat médical. 'absent'
        //        seul = non justifié tant qu'un justificatif_path n'est pas fourni.
        //        Fallback rétro-compat : lit `absences_apprenants` si encore présente.
        if (\Schema::hasTable('presences')) {
            $absJust = \DB::table('presences')
                ->where('apprenant_id', $apprenant->id)
                ->where(function ($q) {
                    $q->whereIn('statut', ['malade', 'permis'])
                      ->orWhere(function ($qq) {
                          $qq->where('statut', 'absent')->whereNotNull('justificatif_path');
                      });
                })
                ->count();
            $absNonJust = \DB::table('presences')
                ->where('apprenant_id', $apprenant->id)
                ->where('statut', 'absent')
                ->whereNull('justificatif_path')
                ->count();
        } elseif (\Schema::hasTable('absences_apprenants')) {
            $absJust = \DB::table('absences_apprenants')
                ->where('apprenant_id', $apprenant->id)
                ->where('statut', 'justifiee')
                ->sum('nombre_heures');
            $absNonJust = \DB::table('absences_apprenants')
                ->where('apprenant_id', $apprenant->id)
                ->where('statut', 'non_justifiee')
                ->sum('nombre_heures');
        } else {
            $absJust = 0;
            $absNonJust = 0;
        }

        return [
            'id' => $bulletin->id,
            'bulletin_id' => $bulletin->id,
            'apprenant' => [
                'nom' => $nom,
                'prenoms' => $prenoms,
                'matricule' => $apprenant->matricule ?? '',
                'date_naissance' => $apprenant->date_naissance ?? null,
                'lieu_naissance' => $apprenant->lieu_naissance ?? '',
                'sexe' => $apprenant->sexe ?? '',
            ],
            'classe' => [
                'nom' => $bulletin->classe->nom ?? '',
                'section' => $bulletin->classe->section?->libelle ?? '',
            ],
            'ecole' => [
                'nom' => $ecole->nom ?? 'AGREE SIKUL',
                'telephone' => $ecole->telephone ?? '',
                'email' => $ecole->email ?? '',
            ],
            'annee_scolaire' => $bulletin->anneeScolaire?->libelle ?? '',
            'periode' => $bulletin->periode ?? '',
            'periode_libelle' => $periodeLabels[$bulletin->periode] ?? $bulletin->periode ?? '',
            'effectif_classe' => $effectifClasse,
            'moyenne_classe' => round($classeStats->avg_moy ?? 0, 2),
            'max_classe' => round($classeStats->max_moy ?? 0, 2),
            'min_classe' => round($classeStats->min_moy ?? 0, 2),
            'moyennes_matieres' => $moyennes->map(fn($m) => [
                'matiere' => $m->matiere?->libelle ?? '',
                'coefficient' => $m->coefficient ?? 1,
                'note' => $m->moyenne ?? 0,
                'moyenne' => $m->moyenne ?? 0,
                'rang' => $m->rang ?? '',
                'appreciation' => $m->appreciation ?? '',
            ])->toArray(),
            'moyenne_generale' => round($moyenneGenerale, 2),
            'rang' => $rang,
            'decision_conseil' => $bulletin->decision_conseil ?? 'en_attente',
            'appreciation_generale' => $bulletin->appreciation_generale ?? '',
            'absences_justifiees' => $absJust ?? 0,
            'absences_non_justifiees' => $absNonJust ?? 0,
            'statut' => $bulletin->statut ?? 'brouillon',
        ];
    }
}
