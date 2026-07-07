<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestDataCompleteSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $this->command->info('🚀 Création des données de test complètes...');

        $now = now();
        $anneeId = DB::table('annees_scolaires')->where('est_courante', 1)->value('id') ?? 1;
        $ecoleId = DB::table('ecoles')->value('id') ?? 1;
        $campusId = DB::table('campuses')->value('id') ?? 1;
        $enseignants = DB::table('enseignants')->pluck('id')->toArray();
        $matieres = DB::table('matieres_unites')->pluck('id', 'code')->toArray();
        $matiereIds = array_values($matieres);
        $classes = DB::table('classes')->pluck('id', 'nom')->toArray();
        $classeIds = array_values($classes);
        $apprenants = DB::table('apprenants')->pluck('id')->toArray();
        $salleId = DB::table('salles')->value('id');

        // =============================================
        // 1. AFFECTATIONS ENSEIGNANT ↔ MATIÈRE
        // =============================================
        try {
            if (DB::table('enseignant_matieres')->count() === 0) {
                $affectations = [
                    // Jean Dupont → Maths + Physique
                    ['enseignant_id' => $enseignants[0] ?? 1, 'matiere_id' => $matiereIds[0] ?? 1],
                    ['enseignant_id' => $enseignants[0] ?? 1, 'matiere_id' => $matiereIds[1] ?? 2],
                    // Marie Martin → Biologie + Chimie
                    ['enseignant_id' => $enseignants[1] ?? 2, 'matiere_id' => $matiereIds[3] ?? 4],
                    ['enseignant_id' => $enseignants[1] ?? 2, 'matiere_id' => $matiereIds[2] ?? 3],
                    // Pierre Bernard → Français + Histoire
                    ['enseignant_id' => $enseignants[2] ?? 3, 'matiere_id' => $matiereIds[4] ?? 5],
                    ['enseignant_id' => $enseignants[2] ?? 3, 'matiere_id' => $matiereIds[7] ?? 8],
                    // Sophie Moreau → Anglais + Géographie
                    ['enseignant_id' => $enseignants[3] ?? 4, 'matiere_id' => $matiereIds[5] ?? 6],
                    ['enseignant_id' => $enseignants[3] ?? 4, 'matiere_id' => $matiereIds[6] ?? 7],
                    // Luc Rousseau → Éducation Civique + Sport
                    ['enseignant_id' => $enseignants[4] ?? 5, 'matiere_id' => $matiereIds[8] ?? 9],
                    ['enseignant_id' => $enseignants[4] ?? 5, 'matiere_id' => $matiereIds[9] ?? 10],
                    // Anne Lefevre → Maths + Chimie
                    ['enseignant_id' => $enseignants[5] ?? 6, 'matiere_id' => $matiereIds[0] ?? 1],
                    ['enseignant_id' => $enseignants[5] ?? 6, 'matiere_id' => $matiereIds[2] ?? 3],
                    // Claude Dubois → Physique + Biologie
                    ['enseignant_id' => $enseignants[6] ?? 7, 'matiere_id' => $matiereIds[1] ?? 2],
                    ['enseignant_id' => $enseignants[6] ?? 7, 'matiere_id' => $matiereIds[3] ?? 4],
                    // Isabelle Laurent → Français + Anglais
                    ['enseignant_id' => $enseignants[7] ?? 8, 'matiere_id' => $matiereIds[4] ?? 5],
                    ['enseignant_id' => $enseignants[7] ?? 8, 'matiere_id' => $matiereIds[5] ?? 6],
                ];
                foreach ($affectations as $a) {
                    DB::table('enseignant_matieres')->insert(array_merge($a, ['created_at' => $now, 'updated_at' => $now]));
                }
                $this->command->info('✅ 16 affectations enseignant-matière créées');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Affectations: ' . $e->getMessage());
        }

        // =============================================
        // 2. EMPLOIS DU TEMPS (3 semaines × 4 classes)
        // =============================================
        try {
            if (DB::table('emplois_temps')->count() < 10) {
                $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'];
                $horaires = [
                    ['08:00', '10:00'], ['10:15', '12:15'], ['14:00', '16:00'], ['16:15', '18:00'],
                ];
                $semaines = [
                    ['S1 - Octobre', '2026-10-05', '2026-10-10'],
                    ['S2 - Octobre', '2026-10-12', '2026-10-17'],
                    ['S3 - Octobre', '2026-10-19', '2026-10-24'],
                ];
                $jourOffsets = ['lundi' => 0, 'mardi' => 1, 'mercredi' => 2, 'jeudi' => 3, 'vendredi' => 4];
                $targetClasses = array_slice($classeIds, 4, 4); // 6ème A, 6ème B, 5ème A, 4ème A

                $records = [];
                foreach ($semaines as [$weekName, $start, $end]) {
                    foreach ($targetClasses as $classeId) {
                        foreach ($jours as $jour) {
                            $nbCours = rand(2, 4);
                            $usedHoraires = array_slice($horaires, 0, $nbCours);
                            foreach ($usedHoraires as $i => [$hd, $hf]) {
                                $matId = $matiereIds[($i + array_search($classeId, $classeIds)) % count($matiereIds)];
                                // Trouver un enseignant affecté à cette matière
                                $ensAffecte = DB::table('enseignant_matieres')->where('matiere_id', $matId)->value('enseignant_id') ?? $enseignants[array_rand($enseignants)];
                                $offset = $jourOffsets[$jour] ?? 0;
                                $dateJour = date('Y-m-d', strtotime("$start +$offset days"));
                                $records[] = [
                                    'week_name' => $weekName,
                                    'week_start_date' => $start,
                                    'week_end_date' => $end,
                                    'annee_scolaire_id' => $anneeId,
                                    'classe_id' => $classeId,
                                    'ecole_id' => $ecoleId,
                                    'campus_id' => $campusId,
                                    'jour' => $jour,
                                    'matiere_id' => $matId,
                                    'enseignant_id' => $ensAffecte,
                                    'date_debut' => $dateJour . 'T' . $hd,
                                    'date_fin' => $dateJour . 'T' . $hf,
                                    'duree' => 2,
                                    'statut' => 'valide',
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ];
                            }
                        }
                    }
                }
                DB::table('emplois_temps')->insert($records);
                $this->command->info('✅ ' . count($records) . ' créneaux emploi du temps créés (3 semaines × 4 classes)');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Emplois du temps: ' . $e->getMessage());
        }

        // =============================================
        // 3. NOTES pour chaque apprenant (20 × 5 matières × 3 évaluations)
        // =============================================
        try {
            if (DB::table('notes')->count() < 200) {
                DB::table('notes')->truncate();
                $evalIds = DB::table('evaluations')->pluck('id')->toArray();
                $periodeId = DB::table('periodes_colaires')->value('id');
                $natureExId = DB::table('natures_examens')->value('id');
                $typeExId = DB::table('type_examens')->value('id');

                $records = [];
                foreach ($apprenants as $appId) {
                    $app = DB::table('apprenants')->where('id', $appId)->first();
                    foreach (array_slice($matiereIds, 0, 5) as $mIdx => $matId) {
                        for ($eval = 1; $eval <= 3; $eval++) {
                            $records[] = [
                                'apprenant_id' => $appId,
                                'annee_scolaire_id' => $anneeId,
                                'classe_id' => $app->classe_id,
                                'ecole_id' => $ecoleId,
                                'campus_id' => $campusId,
                                'matiere_id' => $matId,
                                'evaluation_id' => $evalIds[($mIdx * 3 + $eval - 1) % max(count($evalIds), 1)] ?? null,
                                'periode_id' => $periodeId,
                                'nature_examen_id' => $natureExId,
                                'date_examen' => date('Y-m-d', strtotime('+' . ($eval * 30) . ' days')),
                                'note' => round(rand(600, 1900) / 100, 2),
                                'note_sur' => 20,
                                'note_max' => 20,
                                'statut' => 'valide',
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }
                }
                foreach (array_chunk($records, 100) as $chunk) {
                    DB::table('notes')->insert($chunk);
                }
                $this->command->info('✅ ' . count($records) . ' notes créées (20 apprenants × 5 matières × 3 évals)');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Notes: ' . $e->getMessage());
        }

        // =============================================
        // 4. DEVOIRS (1 par matière par classe)
        // =============================================
        try {
            if (DB::table('devoirs')->count() < 20) {
                DB::table('devoirs')->truncate();
                $coursIds = DB::table('cours')->pluck('id', 'matiere_id')->toArray();
                $n = 0;
                foreach (array_slice($classeIds, 4, 4) as $classeId) {
                    foreach (array_slice($matiereIds, 0, 5) as $matId) {
                        $n++;
                        DB::table('devoirs')->insert([
                            'matiere_id' => $matId,
                            'classe_id' => $classeId,
                            'cours_id' => $coursIds[$matId] ?? null,
                            'titre' => 'Devoir ' . $n . ' — ' . DB::table('matieres_unites')->where('id', $matId)->value('libelle'),
                            'description' => 'Exercices du chapitre ' . $n,
                            'date_debut' => date('Y-m-d', strtotime('+' . $n . ' days')),
                            'date_fin' => date('Y-m-d', strtotime('+' . ($n + 7) . ' days')),
                            'date_remise' => date('Y-m-d', strtotime('+' . ($n + 10) . ' days')),
                            'coefficient' => rand(1, 3),
                            'statut' => 'actif',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
                $this->command->info('✅ ' . $n . ' devoirs créés');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Devoirs: ' . $e->getMessage());
        }

        // =============================================
        // 5. BULLETINS + MOYENNES MATIÈRES (trimestre 1, 2, 3)
        // =============================================
        try {
            if (DB::table('bulletins')->count() < 30) {
                DB::table('moyennes_matieres')->truncate();
                DB::table('bulletins')->truncate();
                $trimestres = ['trimestre1', 'trimestre2', 'trimestre3'];
                $decisions = ['admis', 'ajourne', 'en_attente'];
                $appreciations = [
                    'Excellent trimestre, continue ainsi.',
                    'Bon travail, peut encore progresser.',
                    'Des efforts à fournir en sciences.',
                    'Résultats satisfaisants dans l\'ensemble.',
                    'Très bon trimestre, félicitations.',
                ];

                foreach ($trimestres as $tIdx => $trimestre) {
                    foreach (array_slice($apprenants, 0, 15) as $appId) {
                        $app = DB::table('apprenants')->where('id', $appId)->first();
                        $moyenneGen = round(rand(800, 1800) / 100, 2);
                        $bulletinId = DB::table('bulletins')->insertGetId([
                            'apprenant_id' => $appId,
                            'classe_id' => $app->classe_id,
                            'periode' => $trimestre,
                            'annee_scolaire_id' => $anneeId,
                            'moyenne_generale' => $moyenneGen,
                            'rang' => rand(1, 20),
                            'decision_conseil' => $decisions[array_rand($decisions)],
                            'appreciation_generale' => $appreciations[array_rand($appreciations)],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);

                        foreach (array_slice($matiereIds, 0, 6) as $matId) {
                            DB::table('moyennes_matieres')->insert([
                                'bulletin_id' => $bulletinId,
                                'matiere_id' => $matId,
                                'apprenant_id' => $appId,
                                'moyenne' => round(rand(600, 1900) / 100, 2),
                                'coefficient' => rand(1, 4),
                                'rang' => rand(1, 20),
                                'appreciation' => ['Bien', 'Très bien', 'Assez bien', 'Passable', 'Excellent'][array_rand([0,1,2,3,4])],
                                'statut' => 'actif',
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }
                    }
                }
                $bulletinCount = DB::table('bulletins')->count();
                $moyCount = DB::table('moyennes_matieres')->count();
                $this->command->info("✅ $bulletinCount bulletins + $moyCount moyennes matières créés (3 trimestres × 15 apprenants)");
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Bulletins: ' . $e->getMessage());
        }

        // =============================================
        // 6. PLANIFICATION EXAMENS
        // =============================================
        try {
            if (DB::table('planification_examens')->count() === 0) {
                $typeExIds = DB::table('type_examens')->pluck('id')->toArray();
                $natureExIds = DB::table('natures_examens')->pluck('id')->toArray();
                $exams = [
                    ['2026-12-15', '08:00', '10:00', 120],
                    ['2026-12-16', '08:00', '10:00', 120],
                    ['2027-03-15', '14:00', '16:00', 120],
                    ['2027-04-10', '08:00', '12:00', 240],
                    ['2027-06-01', '08:00', '10:00', 120],
                ];
                foreach ($exams as $i => [$date, $hd, $hf, $duree]) {
                    DB::table('planification_examens')->insert([
                        'nature_examen_id' => $natureExIds[$i % count($natureExIds)] ?? null,
                        'type_examen_id' => $typeExIds[$i % count($typeExIds)] ?? null,
                        'classe_id' => $classeIds[4 + ($i % 4)] ?? $classeIds[0],
                        'matiere_id' => $matiereIds[$i % count($matiereIds)],
                        'jour' => ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'][$i % 5],
                        'date' => $date,
                        'heure_debut' => $hd,
                        'heure_fin' => $hf,
                        'duree' => $duree,
                        'etat' => 'actif',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
                $this->command->info('✅ 5 planifications d\'examens créées');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Planification examens: ' . $e->getMessage());
        }

        // =============================================
        // 7. ABSENCES APPRENANTS (30 absences)
        // =============================================
        try {
            if (DB::table('absences_apprenants')->count() === 0) {
                $motifs = ['Maladie', 'Raison familiale', 'Non justifiée', 'Retard transport', 'Rendez-vous médical'];
                for ($i = 0; $i < 30; $i++) {
                    $appId = $apprenants[array_rand($apprenants)];
                    $app = DB::table('apprenants')->where('id', $appId)->first();
                    $dateDebut = date('Y-m-d H:i:s', strtotime('-' . rand(1, 60) . ' days 08:00'));
                    $dateFin = date('Y-m-d H:i:s', strtotime('-' . rand(1, 60) . ' days 16:00'));
                    DB::table('absences_apprenants')->insert([
                        'apprenant_id' => $appId,
                        'classe_id' => $app->classe_id,
                        'matiere_id' => $matiereIds[array_rand($matiereIds)],
                        'date_debut' => $dateDebut,
                        'date_fin' => $dateFin,
                        'nombre_heures' => rand(2, 8),
                        'nombre_jours' => 1,
                        'motif' => $motifs[array_rand($motifs)],
                        'statut' => ['en_attente', 'justifiee', 'non_justifiee'][rand(0, 2)],
                        'etat' => 'actif',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
                $this->command->info('✅ 30 absences apprenants créées');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Absences apprenants: ' . $e->getMessage());
        }

        // =============================================
        // 8. ABSENCES ENSEIGNANTS (15 absences)
        // =============================================
        try {
            if (DB::table('absences_enseignants')->count() === 0) {
                $motifs = ['Formation', 'Maladie', 'Congé', 'Mission', 'Raison personnelle'];
                for ($i = 0; $i < 15; $i++) {
                    $ensId = $enseignants[array_rand($enseignants)];
                    $dateDebut = date('Y-m-d H:i:s', strtotime('-' . rand(1, 60) . ' days 08:00'));
                    $dateFin = date('Y-m-d H:i:s', strtotime('-' . rand(1, 60) . ' days 18:00'));
                    DB::table('absences_enseignants')->insert([
                        'enseignant_id' => $ensId,
                        'classe_id' => $classeIds[array_rand($classeIds)],
                        'matiere_id' => $matiereIds[array_rand($matiereIds)],
                        'date_debut' => $dateDebut,
                        'date_fin' => $dateFin,
                        'nombre_heures' => rand(2, 8),
                        'motif' => $motifs[array_rand($motifs)],
                        'statut' => ['en_attente', 'validee', 'rejetee'][rand(0, 2)],
                        'etat' => 'actif',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
                $this->command->info('✅ 15 absences enseignants créées');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Absences enseignants: ' . $e->getMessage());
        }

        // =============================================
        // 9. EXAMENS EN LIGNE
        // =============================================
        try {
            if (DB::table('examens_en_ligne')->count() === 0) {
                $exams = [
                    ['Examen Maths T1', $matiereIds[0], 60, 20],
                    ['Quiz Physique', $matiereIds[1], 30, 10],
                    ['Contrôle Français', $matiereIds[4], 45, 20],
                    ['Test Anglais', $matiereIds[5], 30, 20],
                    ['Examen SVT', $matiereIds[3], 60, 20],
                ];
                $planifIds = DB::table('planification_examens')->pluck('id')->toArray();
                foreach ($exams as $i => [$titre, $matId, $duree, $total]) {
                    DB::table('examens_en_ligne')->insert([
                        'titre' => $titre,
                        'matiere_id' => $matId,
                        'classe_id' => $classeIds[4] ?? $classeIds[0],
                        'planification_examen_id' => $planifIds[$i % max(count($planifIds), 1)] ?? null,
                        'duree_minutes' => $duree,
                        'note_maximum' => $total,
                        'nombre_questions' => rand(10, 30),
                        'date_debut' => date('Y-m-d H:i:s', strtotime('+' . rand(1, 30) . ' days')),
                        'date_fin' => date('Y-m-d H:i:s', strtotime('+' . rand(31, 60) . ' days')),
                        'etat' => 'actif',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
                $this->command->info('✅ 5 examens en ligne créés');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Examens en ligne: ' . $e->getMessage());
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('');
        $this->command->info('🎉 ============================================');
        $this->command->info('✅ DONNÉES DE TEST COMPLÈTES CRÉÉES !');
        $this->command->info('   Affectations enseignant-matière · Emplois du temps');
        $this->command->info('   Notes · Devoirs · Bulletins · Moyennes');
        $this->command->info('   Planification examens · Examens en ligne');
        $this->command->info('   Absences apprenants + enseignants');
        $this->command->info('🎉 ============================================');
    }
}
