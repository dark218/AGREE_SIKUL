<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeder global pour Académique, Personnel, Service, Finance,
 * Ressources Logistiques, Documents et Communication.
 *
 * Chaque section est isolée dans un try/catch afin que la moindre
 * incompatibilité de schéma n'interrompe pas l'ensemble.
 */
class ModulesRealDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $this->command->info('🚀 Seeding données réelles: Académique, Personnel, Service, Finance, Logistique, Documents, Communication...');

        // Références communes
        $anneeId       = DB::table('annees_scolaires')->where('est_courante', 1)->value('id')
                      ?? DB::table('annees_scolaires')->value('id');
        $ecoleId       = DB::table('ecoles')->value('id') ?? 1;
        $ecolePrim     = DB::table('ecoles')->where('type_enseignement', 'primaire')->value('id') ?? $ecoleId;
        $ecoleSec      = DB::table('ecoles')->where('type_enseignement', 'secondaire')->value('id') ?? $ecoleId;
        $campusId      = DB::table('campuses')->value('id') ?? 1;
        $classes       = DB::table('classes')->pluck('id', 'nom')->toArray();
        $classeIds     = array_values($classes);
        // §1.1 : matieres → matieres_unites, niveaux → niveaux_etudes
        $matieres      = \Schema::hasTable('matieres_unites')
            ? DB::table('matieres_unites')->pluck('id', 'code')->toArray()
            : [];
        $matiereIds    = array_values($matieres);
        $enseignantIds = DB::table('enseignants')->pluck('id')->toArray();
        $niveauIds     = \Schema::hasTable('niveaux_etudes')
            ? DB::table('niveaux_etudes')->pluck('id')->toArray()
            : [];
        $userAdmin     = DB::table('users')->value('id') ?? 1;
        $banqueId      = DB::table('banques')->value('id');

        // =============================================================
        // A. APPRENANTS — créer 20 apprenants réels si vides
        // =============================================================
        try {
            if (DB::table('apprenants')->count() === 0) {
                $noms = [
                    ['Diallo', 'Mamadou', 'M', '2010-05-12'],
                    ['Ndiaye', 'Fatou', 'F', '2011-07-23'],
                    ['Sow', 'Abdoulaye', 'M', '2012-03-15'],
                    ['Ba', 'Aissatou', 'F', '2011-11-04'],
                    ['Diop', 'Moussa', 'M', '2010-09-08'],
                    ['Sarr', 'Awa', 'F', '2013-01-19'],
                    ['Fall', 'Cheikh', 'M', '2009-06-25'],
                    ['Faye', 'Mariama', 'F', '2012-12-10'],
                    ['Sy', 'Ibrahima', 'M', '2011-04-02'],
                    ['Gueye', 'Bineta', 'F', '2010-08-17'],
                    ['Cissé', 'Amadou', 'M', '2008-02-28'],
                    ['Kane', 'Rokhaya', 'F', '2009-10-14'],
                    ['Thiam', 'Ousmane', 'M', '2007-05-30'],
                    ['Diagne', 'Khadidiatou', 'F', '2008-07-06'],
                    ['Camara', 'Souleymane', 'M', '2009-11-22'],
                    ['Seck', 'Ndeye', 'F', '2010-03-09'],
                    ['Mbaye', 'Alioune', 'M', '2006-09-16'],
                    ['Ndoye', 'Marième', 'F', '2007-12-01'],
                    ['Samb', 'Moustapha', 'M', '2008-04-18'],
                    ['Toure', 'Sokhna', 'F', '2009-08-27'],
                ];
                $n = 0;
                foreach ($noms as [$nom, $prenom, $sexe, $dn]) {
                    $n++;
                    $classeId = $classeIds[($n - 1) % max(count($classeIds), 1)] ?? null;
                    DB::table('apprenants')->insert([
                        'matricule'     => 'APP-' . str_pad((string)$n, 4, '0', STR_PAD_LEFT),
                        'nom'           => $nom,
                        'prenoms'       => $prenom,
                        'date_naissance'=> $dn,
                        'lieu_naissance'=> 'Dakar',
                        'sexe'          => $sexe,
                        'email'         => strtolower($prenom . '.' . $nom) . '@eleve.agreesikul.sn',
                        'telephone'     => '+221 77 ' . rand(100, 999) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                        'nationalite'   => 'Sénégalaise',
                        'classe_id'     => $classeId,
                        'ecole_id'      => $ecoleId,
                        'campus_id'     => $campusId,
                        'annee_scolaire_id' => $anneeId,
                        'statut'        => 'actif',
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                }
                $this->command->info('✅ 20 apprenants créés');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Apprenants: ' . $e->getMessage());
        }

        $apprenantIds = DB::table('apprenants')->pluck('id')->toArray();

        // =============================================================
        // B. MODULE ACADÉMIQUE
        // =============================================================

        // B.1 Cours
        try {
            if (DB::table('cours')->count() === 0 && !empty($matiereIds) && !empty($enseignantIds) && !empty($classeIds)) {
                $n = 0;
                foreach ($classeIds as $classeId) {
                    foreach (array_slice($matiereIds, 0, 5) as $matiereId) {
                        $n++;
                        DB::table('cours')->insert([
                            'matiere_id'    => $matiereId,
                            'classe_id'     => $classeId,
                            'enseignant_id' => $enseignantIds[array_rand($enseignantIds)],
                            'code'          => 'COURS-' . str_pad((string)$n, 4, '0', STR_PAD_LEFT),
                            'titre'         => 'Cours ' . $n,
                            'description'   => 'Programme annuel du cours.',
                            'date_debut'    => '2026-09-15',
                            'date_fin'      => '2027-06-30',
                            'annee_scolaire_id' => $anneeId,
                            'volume_horaire' => 60,
                            'statut'        => 'actif',
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ]);
                        if ($n >= 30) break 2;
                    }
                }
                $this->command->info('✅ Cours créés');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Cours: ' . $e->getMessage());
        }

        // B.2 Séances
        try {
            $cours = DB::table('cours')->pluck('id')->toArray();
            if (!empty($cours) && DB::table('seances')->count() === 0) {
                $salleId = DB::table('salles')->value('id');
                $n = 0;
                foreach (array_slice($cours, 0, 15) as $coursId) {
                    for ($i = 1; $i <= 3; $i++) {
                        $n++;
                        DB::table('seances')->insert([
                            'cours_id'   => $coursId,
                            'titre'      => 'Séance ' . $i,
                            'sujet'      => 'Chapitre ' . $i,
                            'date'       => date('Y-m-d', strtotime("+$n days")),
                            'heure_debut'=> '08:00',
                            'heure_fin'  => '10:00',
                            'duree'      => 120,
                            'salle_id'   => $salleId,
                            'salle'      => 'Salle 01',
                            'type_seance'=> 'cours',
                            'statut'     => 'planifiee',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
                $this->command->info('✅ Séances créées');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Séances: ' . $e->getMessage());
        }

        // B.3 Évaluations
        try {
            if (DB::table('evaluations')->count() === 0 && !empty($matiereIds) && !empty($classeIds)) {
                $types = ['devoir', 'composition', 'interrogation', 'examen'];
                $n = 0;
                foreach ($classeIds as $classeId) {
                    foreach (array_slice($matiereIds, 0, 3) as $matiereId) {
                        $n++;
                        DB::table('evaluations')->insert([
                            'classe_id'   => $classeId,
                            'matiere_id'  => $matiereId,
                            'code'        => 'EVAL-' . str_pad((string)$n, 4, '0', STR_PAD_LEFT),
                            'type'        => $types[array_rand($types)],
                            'titre'       => 'Évaluation ' . $n,
                            'date'        => date('Y-m-d', strtotime("+$n days")),
                            'duree'       => 120,
                            'coefficient' => 2,
                            'sur'         => 20,
                            'statut'      => 'actif',
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                        if ($n >= 20) break 2;
                    }
                }
                $this->command->info('✅ Évaluations créées');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Évaluations: ' . $e->getMessage());
        }

        // B.4 Devoirs
        try {
            $cours = DB::table('cours')->pluck('id')->toArray();
            if (DB::table('devoirs')->count() === 0 && !empty($cours) && !empty($matiereIds) && !empty($classeIds)) {
                $n = 0;
                foreach (array_slice($cours, 0, 10) as $coursId) {
                    $n++;
                    $coursRow = DB::table('cours')->where('id', $coursId)->first();
                    DB::table('devoirs')->insert([
                        'matiere_id'  => $coursRow->matiere_id,
                        'classe_id'   => $coursRow->classe_id,
                        'cours_id'    => $coursId,
                        'titre'       => 'Devoir maison ' . $n,
                        'description' => 'Exercices à rendre',
                        'date_debut'  => date('Y-m-d'),
                        'date_fin'    => date('Y-m-d', strtotime('+7 days')),
                        'date_remise' => date('Y-m-d', strtotime('+10 days')),
                        'coefficient' => 1,
                        'statut'      => 'actif',
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
                $this->command->info('✅ Devoirs créés');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Devoirs: ' . $e->getMessage());
        }

        // B.5 Notes
        try {
            $evalIds = DB::table('evaluations')->pluck('id', 'classe_id')->toArray();
            $evalRows = DB::table('evaluations')->get();
            if (DB::table('notes')->count() === 0 && $evalRows->isNotEmpty() && !empty($apprenantIds)) {
                $n = 0;
                foreach ($evalRows->take(10) as $eval) {
                    foreach (array_slice($apprenantIds, 0, 8) as $appId) {
                        $n++;
                        DB::table('notes')->insert([
                            'evaluation_id'    => $eval->id,
                            'apprenant_id'     => $appId,
                            'annee_scolaire_id'=> $anneeId,
                            'classe_id'        => $eval->classe_id,
                            'ecole_id'         => $ecoleId,
                            'campus_id'        => $campusId,
                            'matiere_id'       => $eval->matiere_id,
                            'note'             => round(rand(800, 1800) / 100, 2),
                            'note_sur'         => 20,
                            'note_max'         => 20,
                            'statut'           => 'valide',
                            'created_at'       => now(),
                            'updated_at'       => now(),
                        ]);
                    }
                }
                $this->command->info('✅ Notes créées');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Notes: ' . $e->getMessage());
        }

        // B.6 Bulletins
        try {
            if (DB::table('bulletins')->count() === 0 && !empty($apprenantIds)) {
                foreach (array_slice($apprenantIds, 0, 10) as $appId) {
                    $appRow = DB::table('apprenants')->where('id', $appId)->first();
                    $bulletinId = DB::table('bulletins')->insertGetId([
                        'apprenant_id'      => $appId,
                        'classe_id'         => $appRow->classe_id,
                        'periode'           => 'trimestre1',
                        'annee_scolaire_id' => $anneeId,
                        'moyenne_generale'  => round(rand(1000, 1700) / 100, 2),
                        'rang'              => rand(1, 20),
                        'decision_conseil'  => 'Admis',
                        'appreciation_generale' => 'Bon trimestre, continuez ainsi.',
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                    // moyennes par matière
                    foreach (array_slice($matiereIds, 0, 5) as $matiereId) {
                        try {
                            DB::table('moyennes_matieres')->insert([
                                'bulletin_id'  => $bulletinId,
                                'matiere_id'   => $matiereId,
                                'apprenant_id' => $appId,
                                'moyenne'      => round(rand(800, 1800) / 100, 2),
                                'coefficient'  => rand(1, 4),
                                'rang'         => rand(1, 20),
                                'appreciation' => 'Bien',
                                'statut'       => 'actif',
                                'created_at'   => now(),
                                'updated_at'   => now(),
                            ]);
                        } catch (\Throwable $e) {}
                    }
                }
                $this->command->info('✅ Bulletins + moyennes matières créés');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Bulletins: ' . $e->getMessage());
        }

        // =============================================================
        // C. MODULE SERVICES (Cantine + Transport + Menus)
        // =============================================================

        // C.1 Services cantine
        try {
            if (DB::table('services_cantines')->count() === 0) {
                DB::table('services_cantines')->insert([
                    [
                        'code' => 'CANTINE_MIDI', 'nom' => 'Cantine déjeuner',
                        'description' => 'Service de restauration du midi',
                        'ecole_id' => $ecoleId, 'campus_id' => $campusId,
                        'annee_scolaire_id' => $anneeId,
                        'capacite' => 200, 'prix_cents' => 150000,
                        'tarif_mensuel' => 30000, 'tarif_trimestriel' => 85000,
                        'tarif_semestriel' => 160000, 'tarif_annuel' => 300000,
                        'date_debut' => '2026-09-01', 'date_fin' => '2027-06-30',
                        'statut' => 'actif', 'created_at' => now(), 'updated_at' => now(),
                    ],
                    [
                        'code' => 'CANTINE_SOIR', 'nom' => 'Cantine dîner (internat)',
                        'description' => 'Service de restauration du soir pour internes',
                        'ecole_id' => $ecoleId, 'campus_id' => $campusId,
                        'annee_scolaire_id' => $anneeId,
                        'capacite' => 80, 'prix_cents' => 200000,
                        'tarif_mensuel' => 40000, 'tarif_trimestriel' => 115000,
                        'tarif_semestriel' => 220000, 'tarif_annuel' => 420000,
                        'date_debut' => '2026-09-01', 'date_fin' => '2027-06-30',
                        'statut' => 'actif', 'created_at' => now(), 'updated_at' => now(),
                    ],
                ]);
                $this->command->info('✅ Services cantine créés');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Services cantine: ' . $e->getMessage());
        }

        // C.2 Menus (semaine courante). §10.6 : table canonique = menu_cantines.
        try {
            $serviceCantineId = DB::table('services_cantines')->value('id');
            if ($serviceCantineId && \Schema::hasTable('menu_cantines')
                && DB::table('menu_cantines')->count() === 0) {
                $jours = [
                    ['lundi',    'Thiéboudienne (riz au poisson)', 'Salade de fruits'],
                    ['mardi',    'Yassa poulet',                    'Yaourt'],
                    ['mercredi', 'Mafé (viande sauce arachide)',    'Banane'],
                    ['jeudi',    'Poulet braisé et attiéké',        'Pastèque'],
                    ['vendredi', 'Couscous au mouton',              'Dessert maison'],
                ];
                $start       = date('Y-m-d', strtotime('monday this week'));
                $end         = date('Y-m-d', strtotime('friday this week'));
                $weekNumber  = (int) date('W', strtotime($start));
                $year        = (int) date('o', strtotime($start));
                foreach ($jours as [$jour, $plat, $dessert]) {
                    DB::table('menu_cantines')->insert([
                        'service_cantine_id' => $serviceCantineId,
                        'week_start_date'    => $start,
                        'week_end_date'      => $end,
                        'week_name'          => 'Semaine du ' . date('d/m', strtotime($start)),
                        'week_number'        => $weekNumber,
                        'year'               => $year,
                        'jour'               => $jour,
                        'entree'             => 'Légumes de saison',
                        'plat'               => $plat,
                        'dessert'            => $dessert,
                        'remarques'          => 'Menu équilibré',
                        'statut'             => 'actif',
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                }
                $this->command->info('✅ Menus de la semaine créés (menu_cantines)');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Menus: ' . $e->getMessage());
        }

        // C.3 Inscriptions cantine
        try {
            $serviceCantineId = DB::table('services_cantines')->value('id');
            if ($serviceCantineId && DB::table('inscriptions_cantines')->count() === 0 && !empty($apprenantIds)) {
                foreach (array_slice($apprenantIds, 0, 10) as $appId) {
                    DB::table('inscriptions_cantines')->insert([
                        'service_cantine_id' => $serviceCantineId,
                        'apprenant_id'       => $appId,
                        'annee_scolaire_id'  => $anneeId,
                        'type_formule'       => 'complet',
                        'statut'             => 'active',
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                }
                $this->command->info('✅ Inscriptions cantine créées');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Inscriptions cantine: ' . $e->getMessage());
        }

        // C.4 Services transport
        try {
            if (DB::table('services_transports')->count() === 0) {
                DB::table('services_transports')->insert([
                    [
                        'annee_scolaire_id' => $anneeId, 'ecole_id' => $ecoleId, 'campus_id' => $campusId,
                        'zone' => 'Dakar Centre', 'ligne' => 'Ligne A',
                        'point_depart' => 'Plateau', 'point_1' => 'Médina', 'point_2' => 'HLM',
                        'point_3' => 'Sacré-Cœur', 'point_4' => 'Point E', 'point_5' => 'Mermoz',
                        'tarif_mensuel' => 25000, 'tarif_trimestriel' => 70000,
                        'tarif_semestriel' => 135000, 'tarif_annuel' => 250000,
                        'date_debut' => '2026-09-01', 'date_fin' => '2027-06-30',
                        'etat' => 'actif', 'created_at' => now(), 'updated_at' => now(),
                    ],
                    [
                        'annee_scolaire_id' => $anneeId, 'ecole_id' => $ecoleId, 'campus_id' => $campusId,
                        'zone' => 'Banlieue', 'ligne' => 'Ligne B',
                        'point_depart' => 'Pikine', 'point_1' => 'Guédiawaye', 'point_2' => 'Thiaroye',
                        'point_3' => 'Yeumbeul', 'point_4' => 'Malika', 'point_5' => 'Rufisque',
                        'tarif_mensuel' => 30000, 'tarif_trimestriel' => 85000,
                        'tarif_semestriel' => 165000, 'tarif_annuel' => 310000,
                        'date_debut' => '2026-09-01', 'date_fin' => '2027-06-30',
                        'etat' => 'actif', 'created_at' => now(), 'updated_at' => now(),
                    ],
                ]);
                $this->command->info('✅ Services transport créés');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Services transport: ' . $e->getMessage());
        }

        // C.5 Inscriptions transport
        try {
            $serviceTransportId = DB::table('services_transports')->value('id');
            if ($serviceTransportId && DB::table('inscriptions_transports')->count() === 0 && !empty($apprenantIds)) {
                foreach (array_slice($apprenantIds, 0, 8) as $appId) {
                    DB::table('inscriptions_transports')->insert([
                        'service_transport_id' => $serviceTransportId,
                        'apprenant_id'         => $appId,
                        'annee_scolaire_id'    => $anneeId,
                        'point_ramassage'      => 'Médina',
                        'statut'               => 'active',
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ]);
                }
                $this->command->info('✅ Inscriptions transport créées');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Inscriptions transport: ' . $e->getMessage());
        }

        // =============================================================
        // D. MODULE FINANCE
        // =============================================================

        // D.1 Types de frais
        try {
            if (DB::table('types_frais')->count() === 0) {
                DB::table('types_frais')->insert([
                    ['code' => 'INSC', 'libelle' => 'Frais d\'inscription', 'description' => 'Frais d\'inscription annuels', 'montant_cents' => 5000000, 'obligatoire' => 1, 'created_at' => now(), 'updated_at' => now()],
                    ['code' => 'SCOL', 'libelle' => 'Frais de scolarité',  'description' => 'Frais de scolarité annuels',  'montant_cents' => 30000000, 'obligatoire' => 1, 'created_at' => now(), 'updated_at' => now()],
                    ['code' => 'DOSS', 'libelle' => 'Frais de dossier',    'description' => 'Frais de dossier',            'montant_cents' => 1500000, 'obligatoire' => 1, 'created_at' => now(), 'updated_at' => now()],
                    ['code' => 'UNIF', 'libelle' => 'Uniforme',            'description' => 'Uniforme scolaire',           'montant_cents' => 2500000, 'obligatoire' => 0, 'created_at' => now(), 'updated_at' => now()],
                    ['code' => 'LIVR', 'libelle' => 'Livres',              'description' => 'Livres scolaires',            'montant_cents' => 3500000, 'obligatoire' => 0, 'created_at' => now(), 'updated_at' => now()],
                    ['code' => 'ACT',  'libelle' => 'Activités parascolaires', 'description' => 'Sport, arts', 'montant_cents' => 1000000, 'obligatoire' => 0, 'created_at' => now(), 'updated_at' => now()],
                    ['code' => 'EXAM', 'libelle' => 'Frais d\'examens',    'description' => 'Frais examens officiels',     'montant_cents' => 2000000, 'obligatoire' => 1, 'created_at' => now(), 'updated_at' => now()],
                ]);
                $this->command->info('✅ Types de frais créés');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Types de frais: ' . $e->getMessage());
        }

        // D.2 Frais apprenants
        try {
            $typeFraisIds = DB::table('types_frais')->pluck('id', 'code')->toArray();
            if (DB::table('frais')->count() === 0 && !empty($apprenantIds) && !empty($typeFraisIds)) {
                foreach (array_slice($apprenantIds, 0, 15) as $appId) {
                    foreach ($typeFraisIds as $tfId) {
                        $montant = DB::table('types_frais')->where('id', $tfId)->value('montant_cents');
                        DB::table('frais')->insert([
                            'apprenant_id'      => $appId,
                            'annee_scolaire_id' => $anneeId,
                            'type_frais_id'     => $tfId,
                            'montant_cents'     => $montant,
                            'montant_paye_cents'=> rand(0, (int)$montant),
                            'statut'            => 'partiellement_paye',
                            'created_at'        => now(),
                            'updated_at'        => now(),
                        ]);
                    }
                }
                $this->command->info('✅ Frais apprenants créés');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Frais: ' . $e->getMessage());
        }

        // D.3 Écolages
        try {
            if (DB::table('ecolages')->count() === 0 && !empty($niveauIds)) {
                foreach ($niveauIds as $niveauId) {
                    DB::table('ecolages')->insert([
                        'annee_scolaire_id' => $anneeId,
                        'niveau_id'         => $niveauId,
                        'ecole_id'          => $ecoleId,
                        'campus_id'         => $campusId,
                        'frais_dossier'     => 15000,
                        'frais_inscription' => 50000,
                        'frais_scolarite'   => 300000,
                        'etat'              => 'actif',
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                }
                $this->command->info('✅ Écolages créés');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Écolages: ' . $e->getMessage());
        }

        // D.4 Dépenses
        try {
            if (DB::table('depenses')->count() === 0) {
                $libs = [
                    ['Achat fournitures de bureau', 'fournitures', 450000],
                    ['Réparation climatisation', 'maintenance', 1200000],
                    ['Factures électricité', 'utilities', 2800000],
                    ['Factures eau', 'utilities', 650000],
                    ['Salaires enseignants — Septembre', 'salaires', 15000000],
                    ['Achat produits d\'entretien', 'entretien', 350000],
                    ['Impression bulletins', 'imprimerie', 280000],
                    ['Internet fibre', 'telecoms', 550000],
                    ['Carburant véhicules', 'transport', 780000],
                    ['Assurance scolaire', 'assurance', 1500000],
                ];
                foreach ($libs as [$lib, $cat, $mt]) {
                    DB::table('depenses')->insert([
                        'ecole_id'    => $ecoleId,
                        'categorie'   => $cat,
                        'libelle'     => $lib,
                        'montant_cents' => $mt,
                        'date_depense'=> date('Y-m-d', strtotime('-' . rand(1, 60) . ' days')),
                        'auteur_id'   => $userAdmin,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
                $this->command->info('✅ Dépenses créées');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Dépenses: ' . $e->getMessage());
        }

        // D.5 Achats / dépenses détaillés
        try {
            if (DB::table('achats_depenses')->count() === 0) {
                $items = [
                    ['Fourniture scolaire', 'Librairie Clairafrique', 450000, 'especes'],
                    ['Matériel informatique', 'CFAO Technologies', 3500000, 'cheque'],
                    ['Travaux peinture', 'Entreprise BTP Dakar', 1800000, 'virement'],
                    ['Équipement labo SVT', 'Scientific Sénégal', 2200000, 'virement'],
                    ['Mobilier scolaire', 'Meubles Afrique', 4500000, 'cheque'],
                ];
                foreach ($items as $i => [$nature, $tiers, $mt, $mode]) {
                    DB::table('achats_depenses')->insert([
                        'annee_scolaire_id' => $anneeId,
                        'ecole_id'          => $ecoleId,
                        'campus_id'         => $campusId,
                        'date_depense'      => date('Y-m-d', strtotime('-' . (10 + $i * 5) . ' days')),
                        'nature_depense'    => $nature,
                        'tiers_fournisseur' => $tiers,
                        'numero_identifiant'=> 'FAC-2026-' . str_pad((string)($i + 1), 4, '0', STR_PAD_LEFT),
                        'type_piece'        => 'facture',
                        'reference_piece'   => 'REF-' . ($i + 1000),
                        'intitule_operation'=> $nature,
                        'montant'           => $mt,
                        'mode_paiement'     => $mode,
                        'montant_total_paye'=> $mt,
                        'restant_a_payer'   => 0,
                        'etat'              => 'actif',
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                }
                $this->command->info('✅ Achats/dépenses créés');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Achats dépenses: ' . $e->getMessage());
        }

        // D.6 Autres revenus
        try {
            if (DB::table('autres_revenus')->count() === 0 && !empty($niveauIds)) {
                DB::table('autres_revenus')->insert([
                    'annee_scolaire_id' => $anneeId,
                    'niveau_id'         => $niveauIds[0],
                    'ecole_id'          => $ecoleId,
                    'campus_id'         => $campusId,
                    'uniforme'          => 25000,
                    'tenue_mercredi'    => 15000,
                    'tenue_sport'       => 12000,
                    'autre1'            => 5000,
                    'etat'              => 'actif',
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
                $this->command->info('✅ Autres revenus créés');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Autres revenus: ' . $e->getMessage());
        }

        // =============================================================
        // E. MODULE RESSOURCES LOGISTIQUES
        // =============================================================

        // E.1 Catégories équipements
        try {
            if (DB::table('categories_equipements')->count() === 0) {
                DB::table('categories_equipements')->insert([
                    ['libelle' => 'Informatique', 'description' => 'Ordinateurs, imprimantes, projecteurs', 'created_at' => now(), 'updated_at' => now()],
                    ['libelle' => 'Mobilier',     'description' => 'Tables, chaises, armoires',          'created_at' => now(), 'updated_at' => now()],
                    ['libelle' => 'Laboratoire',  'description' => 'Matériel scientifique',              'created_at' => now(), 'updated_at' => now()],
                    ['libelle' => 'Audiovisuel',  'description' => 'TV, sono, micro',                     'created_at' => now(), 'updated_at' => now()],
                    ['libelle' => 'Sport',        'description' => 'Ballons, matériel sportif',           'created_at' => now(), 'updated_at' => now()],
                ]);
                $this->command->info('✅ Catégories équipements créées');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Catégories équipements: ' . $e->getMessage());
        }

        // E.2 Équipements
        try {
            $catEquipIds = DB::table('categories_equipements')->pluck('id')->toArray();
            if (!empty($catEquipIds) && DB::table('equipements')->count() === 0) {
                $items = [
                    ['Ordinateur Dell OptiPlex', 'DELL-001', 45000000, 'Informatique'],
                    ['Imprimante HP LaserJet', 'HP-001', 18000000, 'Informatique'],
                    ['Vidéoprojecteur Epson', 'EPSON-001', 35000000, 'Informatique'],
                    ['Table de classe (lot 30)', 'TBL-001', 90000000, 'Mobilier'],
                    ['Chaise élève (lot 60)', 'CHS-001', 60000000, 'Mobilier'],
                    ['Microscope optique', 'MIC-001', 25000000, 'Laboratoire'],
                    ['Kit chimie complet', 'KIT-001', 15000000, 'Laboratoire'],
                    ['Téléviseur 55" Samsung', 'TV-001', 55000000, 'Audiovisuel'],
                    ['Système sono complet', 'SON-001', 40000000, 'Audiovisuel'],
                    ['Ballons (lot de 20)', 'BAL-001', 5000000, 'Sport'],
                ];
                foreach ($items as $i => [$nom, $ref, $prix, $catLib]) {
                    $catId = DB::table('categories_equipements')->where('libelle', $catLib)->value('id') ?? $catEquipIds[0];
                    DB::table('equipements')->insert([
                        'categorie_id'    => $catId,
                        'ecole_id'        => $ecoleId,
                        'nom'             => $nom,
                        'reference'       => $ref,
                        'date_acquisition'=> date('Y-m-d', strtotime('-' . rand(30, 365) . ' days')),
                        'prix_cents'      => $prix,
                        'etat'            => 'bon',
                        'localisation'    => 'Salle ' . str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT),
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
                $this->command->info('✅ Équipements créés');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Équipements: ' . $e->getMessage());
        }

        // E.3 Catégories fournitures
        try {
            if (DB::table('categories_fournitures')->count() === 0) {
                DB::table('categories_fournitures')->insert([
                    ['libelle' => 'Bureau',        'code' => 'BUR', 'description' => 'Fournitures de bureau', 'created_at' => now(), 'updated_at' => now()],
                    ['libelle' => 'Pédagogique',   'code' => 'PED', 'description' => 'Matériel pédagogique',   'created_at' => now(), 'updated_at' => now()],
                    ['libelle' => 'Entretien',     'code' => 'ENT', 'description' => 'Produits d\'entretien',  'created_at' => now(), 'updated_at' => now()],
                    ['libelle' => 'Infirmerie',    'code' => 'INF', 'description' => 'Trousse médicale',       'created_at' => now(), 'updated_at' => now()],
                ]);
                $this->command->info('✅ Catégories fournitures créées');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Catégories fournitures: ' . $e->getMessage());
        }

        // E.4 Fournitures
        try {
            $catFournIds = DB::table('categories_fournitures')->pluck('id', 'code')->toArray();
            if (!empty($catFournIds) && DB::table('fournitures')->count() === 0) {
                $items = [
                    ['Papier A4 (ramette)', 'BUR', 50, 350000, 'Papeterie Centrale'],
                    ['Stylos bleus (boîte)', 'BUR', 200, 150000, 'Papeterie Centrale'],
                    ['Cahiers 200 pages', 'PED', 500, 80000, 'Librairie Clairafrique'],
                    ['Craie blanche (boîte)', 'PED', 100, 30000, 'Papeterie Centrale'],
                    ['Marqueurs tableau (lot)', 'PED', 80, 120000, 'Papeterie Centrale'],
                    ['Détergent (bidon 5L)', 'ENT', 30, 400000, 'Hygiène Plus'],
                    ['Javel (bidon 5L)', 'ENT', 25, 250000, 'Hygiène Plus'],
                    ['Pansements (boîte)', 'INF', 20, 150000, 'Pharmacie du Lycée'],
                ];
                foreach ($items as $i => [$lib, $catCode, $qty, $prix, $four]) {
                    DB::table('fournitures')->insert([
                        'categorie_fourniture_id' => $catFournIds[$catCode] ?? array_values($catFournIds)[0],
                        'libelle'             => $lib,
                        'code'                => 'FRN-' . str_pad((string)($i + 1), 4, '0', STR_PAD_LEFT),
                        'quantite'            => $qty,
                        'prix_unitaire_cents' => $prix,
                        'fournisseur'         => $four,
                        'date_acquisition'    => date('Y-m-d', strtotime('-' . rand(5, 90) . ' days')),
                        'statut'              => 'disponible',
                        'localisation'        => 'Magasin principal',
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]);
                }
                $this->command->info('✅ Fournitures créées');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Fournitures: ' . $e->getMessage());
        }

        // =============================================================
        // F. MODULE DOCUMENTS + BIBLIOTHÈQUE
        // =============================================================

        // F.1 Catégories documents
        try {
            if (DB::table('categories_documents')->count() === 0) {
                DB::table('categories_documents')->insert([
                    ['code' => 'ADMIN', 'libelle' => 'Administratif', 'description' => 'Documents administratifs', 'icone' => 'file-text', 'couleur' => '#0B5697', 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()],
                    ['code' => 'PEDA',  'libelle' => 'Pédagogique',   'description' => 'Supports de cours',         'icone' => 'book',      'couleur' => '#0FBCAF', 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()],
                    ['code' => 'LEGAL', 'libelle' => 'Légal',         'description' => 'Documents légaux',          'icone' => 'shield',    'couleur' => '#E5590C', 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()],
                    ['code' => 'COMM',  'libelle' => 'Communication', 'description' => 'Affiches, brochures',       'icone' => 'megaphone', 'couleur' => '#8B5CF6', 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()],
                    ['code' => 'RH',    'libelle' => 'Ressources Humaines', 'description' => 'Contrats, fiches',    'icone' => 'users',     'couleur' => '#10B981', 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()],
                ]);
                $this->command->info('✅ Catégories documents créées');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Catégories documents: ' . $e->getMessage());
        }

        // F.2 Documents
        try {
            $catDocIds = DB::table('categories_documents')->pluck('id')->toArray();
            if (!empty($catDocIds) && DB::table('documents')->count() === 0) {
                $docs = [
                    ['Règlement intérieur 2026-2027', 'Règlement officiel de l\'établissement'],
                    ['Calendrier scolaire', 'Calendrier des activités scolaires'],
                    ['Guide parent', 'Guide à destination des parents'],
                    ['Charte informatique', 'Règles d\'utilisation du matériel informatique'],
                    ['Programme pédagogique', 'Programmes annuels des cours'],
                ];
                foreach ($docs as $i => [$titre, $desc]) {
                    DB::table('documents')->insert([
                        'categorie_id'    => $catDocIds[$i % count($catDocIds)],
                        'titre'           => $titre,
                        'description'     => $desc,
                        'auteur_id'       => $userAdmin,
                        'cibles'          => json_encode(['tous']),
                        'date_publication'=> date('Y-m-d'),
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
                $this->command->info('✅ Documents créés');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Documents: ' . $e->getMessage());
        }

        // F.3 Bibliothèque
        try {
            if (DB::table('bibliotheques')->count() === 0 && !empty($niveauIds)) {
                $livres = [
                    ['Les Bouts de bois de Dieu', 'Ousmane Sembène', 'Roman', 'Français', 'CLE', 1960, 15],
                    ['Une si longue lettre', 'Mariama Bâ', 'Roman', 'Français', 'NEA', 1979, 20],
                    ['Mathématiques 6ème', 'Collectif', 'Manuel', 'Français', 'Hatier', 2023, 30],
                    ['Physique-Chimie Terminale', 'Collectif', 'Manuel', 'Français', 'Bordas', 2023, 25],
                    ['L\'Aventure ambiguë', 'Cheikh Hamidou Kane', 'Roman', 'Français', 'Julliard', 1961, 18],
                    ['SVT 3ème', 'Collectif', 'Manuel', 'Français', 'Nathan', 2023, 25],
                    ['Histoire-Géographie 2nde', 'Collectif', 'Manuel', 'Français', 'Magnard', 2023, 20],
                ];
                foreach ($livres as [$titre, $auteurs, $sujet, $langue, $editeurs, $annee, $qty]) {
                    DB::table('bibliotheques')->insert([
                        'sujet'         => $sujet,
                        'langue'        => $langue,
                        'niveau_id'     => $niveauIds[0],
                        'type_manuel'   => $sujet === 'Manuel' ? 'scolaire' : 'litterature',
                        'titre_manuel'  => $titre,
                        'auteurs'       => $auteurs,
                        'editeurs'      => $editeurs,
                        'annee_edition' => $annee,
                        'quantite'      => $qty,
                        'sorties'       => rand(0, 5),
                        'disponibles'   => $qty - rand(0, 5),
                        'etat'          => 'actif',
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                }
                $this->command->info('✅ Livres bibliothèque créés');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Bibliothèque: ' . $e->getMessage());
        }

        // F.4 Ouvrages + Exemplaires + Emprunts
        try {
            if (DB::table('ouvrages')->count() === 0) {
                $biblioId = DB::table('bibliotheques')->value('id');
                $ouvrages = [
                    ['Les Bouts de bois de Dieu', 'Ousmane Sembène', '978-2-266-12345-1', 'Presses Pocket', 1960, 'Roman'],
                    ['Une si longue lettre', 'Mariama Bâ', '978-2-7236-1234-5', 'NEA', 1979, 'Roman'],
                    ['L\'Aventure ambiguë', 'Cheikh Hamidou Kane', '978-2-260-00456-7', 'Julliard', 1961, 'Roman'],
                    ['Le Petit Prince', 'Antoine de Saint-Exupéry', '978-2-07-040850-4', 'Gallimard', 1943, 'Conte'],
                ];
                foreach ($ouvrages as $i => [$titre, $auteur, $isbn, $editeur, $annee, $cat]) {
                    $ouvrageId = DB::table('ouvrages')->insertGetId([
                        'bibliotheque_id'    => $biblioId,
                        'titre'              => $titre,
                        'auteur'             => $auteur,
                        'isbn'               => $isbn,
                        'editeur'            => $editeur,
                        'annee_publication'  => $annee,
                        'categorie'          => $cat,
                        'nombre_exemplaires' => 5,
                        'statut'             => 'disponible',
                        'description'        => 'Œuvre de référence',
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                    // 5 exemplaires par ouvrage
                    for ($j = 1; $j <= 5; $j++) {
                        DB::table('exemplaires')->insert([
                            'ouvrage_id'      => $ouvrageId,
                            'code_exemplaire' => 'EX-' . ($i + 1) . '-' . str_pad((string)$j, 3, '0', STR_PAD_LEFT),
                            'numero_serie'   => 'SN-' . rand(10000, 99999),
                            'etat'           => 'bon',
                            'localisation'   => 'Bibliothèque centrale',
                            'date_acquisition' => '2025-09-01',
                            'statut'         => $j === 1 ? 'emprunte' : 'disponible',
                            'created_at'     => now(),
                            'updated_at'     => now(),
                        ]);
                    }
                }
                $this->command->info('✅ Ouvrages + exemplaires créés');

                // Quelques emprunts
                if (!empty($apprenantIds)) {
                    $exemplairesEmpruntes = DB::table('exemplaires')->where('statut', 'emprunte')->pluck('id')->toArray();
                    foreach (array_slice($exemplairesEmpruntes, 0, 4) as $k => $exId) {
                        DB::table('emprunts')->insert([
                            'exemplaire_id'     => $exId,
                            'apprenant_id'      => $apprenantIds[$k % count($apprenantIds)],
                            'date_emprunt'      => date('Y-m-d', strtotime('-' . rand(3, 15) . ' days')),
                            'date_retour_prevue'=> date('Y-m-d', strtotime('+' . rand(1, 10) . ' days')),
                            'statut'            => 'en_cours',
                            'created_at'        => now(),
                            'updated_at'        => now(),
                        ]);
                    }
                    $this->command->info('✅ Emprunts créés');
                }
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Ouvrages/emprunts: ' . $e->getMessage());
        }

        // =============================================================
        // G. MODULE COMMUNICATION
        // =============================================================

        // G.1 Annonces
        try {
            if (DB::table('annonces')->count() === 0) {
                $annonces = [
                    ['Rentrée scolaire 2026-2027', 'La rentrée aura lieu le lundi 15 septembre 2026. Tous les élèves sont attendus à 8h.', ['tous']],
                    ['Réunion parents-professeurs', 'Une réunion est organisée samedi 20 septembre de 9h à 13h.', ['parents']],
                    ['Journée portes ouvertes', 'Venez découvrir notre établissement le samedi 5 octobre.', ['tous']],
                    ['Fermeture exceptionnelle', 'L\'établissement sera fermé le vendredi 2 octobre pour maintenance.', ['tous']],
                    ['Distribution des bulletins', 'Les bulletins du 1er trimestre seront distribués le 18 décembre.', ['parents']],
                ];
                foreach ($annonces as $i => [$titre, $contenu, $cibles]) {
                    $annonceId = DB::table('annonces')->insertGetId([
                        'auteur_id'       => $userAdmin,
                        'titre'           => $titre,
                        'contenu'         => $contenu,
                        'cibles'          => json_encode($cibles),
                        'date_publication'=> date('Y-m-d', strtotime("-$i days")),
                        'date_expiration' => date('Y-m-d', strtotime('+30 days')),
                        'statut'          => 'active',
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                    // Commentaires
                    try {
                        DB::table('commentaires_annonces')->insert([
                            'annonce_id'      => $annonceId,
                            'auteur_id'       => $userAdmin,
                            'contenu'         => 'Merci pour l\'information.',
                            'date_publication'=> date('Y-m-d H:i:s'),
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);
                    } catch (\Throwable $e) {}
                }
                $this->command->info('✅ Annonces + commentaires créés');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Annonces: ' . $e->getMessage());
        }

        // G.2 Messages
        try {
            if (DB::table('messages')->count() === 0) {
                $userIds = DB::table('users')->pluck('id')->toArray();
                if (count($userIds) >= 2) {
                    $msgs = [
                        ['Bienvenue sur AGREE SIKUL', 'Bienvenue dans notre nouvelle plateforme de gestion scolaire.'],
                        ['Rappel: Réunion pédagogique', 'Réunion pédagogique prévue jeudi à 15h en salle de conférence.'],
                        ['Nouveau calendrier', 'Le nouveau calendrier scolaire est disponible dans les documents.'],
                    ];
                    foreach ($msgs as [$objet, $contenu]) {
                        DB::table('messages')->insert([
                            'expediteur_id'      => $userIds[0],
                            'destinataires_ids'  => json_encode([$userIds[1]]),
                            'objet'              => $objet,
                            'contenu'            => $contenu,
                            'lu_par'             => json_encode([]),
                            'date_envoi'         => now(),
                            'created_at'         => now(),
                            'updated_at'         => now(),
                        ]);
                    }
                    $this->command->info('✅ Messages créés');
                }
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Messages: ' . $e->getMessage());
        }

        // G.3 Notifications
        try {
            if (DB::table('notifications')->count() === 0) {
                $notifs = [
                    ['Bienvenue !', 'Votre compte a été créé avec succès.', 'info'],
                    ['Nouveau bulletin disponible', 'Votre bulletin du 1er trimestre est prêt.', 'academique'],
                    ['Paiement en retard', 'Un paiement est en attente sur votre compte.', 'finance'],
                    ['Réunion à venir', 'Réunion parents-professeurs samedi prochain.', 'communication'],
                ];
                foreach ($notifs as [$title, $body, $type]) {
                    DB::table('notifications')->insert([
                        'title'           => $title,
                        'body'            => $body,
                        'type'            => $type,
                        'user_id'         => $userAdmin,
                        'notifiable_type' => 'App\\Models\\User',
                        'notifiable_id'   => $userAdmin,
                        'data'            => json_encode(['extra' => 'info']),
                        'is_read'         => 0,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
                $this->command->info('✅ Notifications créées');
            }
        } catch (\Throwable $e) {
            $this->command->warn('⚠️  Notifications: ' . $e->getMessage());
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('');
        $this->command->info('🎉 ===========================================');
        $this->command->info('✅ TOUS LES MODULES SEEDÉS AVEC DONNÉES RÉELLES');
        $this->command->info('   Académique · Personnel · Service · Finance');
        $this->command->info('   Logistique · Documents · Communication');
        $this->command->info('🎉 ===========================================');
    }
}
