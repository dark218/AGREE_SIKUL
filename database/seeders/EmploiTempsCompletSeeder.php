<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmploiTempsCompletSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // 1. Récupérer les données existantes
        $ecole = DB::table('ecoles')->first();
        $annee = DB::table('annees_scolaires')->first();

        if (!$ecole || !$annee) {
            echo "❌ Manque école ou année scolaire\n";
            return;
        }

        // 2. Récupérer ou créer les niveaux
        $niveaux = [];
        $niveaux_data = [
            ['code' => '6', 'libelle' => '6ème', 'ordre' => 1],
            ['code' => '5', 'libelle' => '5ème', 'ordre' => 2],
            ['code' => '4', 'libelle' => '4ème', 'ordre' => 3],
            ['code' => '3', 'libelle' => '3ème', 'ordre' => 4],
        ];

        // §1.1 : niveaux → niveaux_etudes. Skip si absente.
        $niveauxTable = \Schema::hasTable('niveaux_etudes') ? 'niveaux_etudes' : null;
        if ($niveauxTable) {
            foreach ($niveaux_data as $n) {
                // Chercher d'abord
                $existing = DB::table($niveauxTable)->where('code', $n['code'])->where('ecole_id', $ecole->id)->first();
                if ($existing) {
                    $niveaux[$n['code']] = $existing->id;
                } else {
                    $id = DB::table($niveauxTable)->insertGetId(array_merge($n, [
                        'ecole_id'      => $ecole->id,
                        'etat'          => 'actif',
                        'source_system' => 'manual',
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]));
                    $niveaux[$n['code']] = $id;
                }
            }
        }

        // 3. Créer les matieres
        $matieres = [];
        $matieres_data = [
            ['code' => 'MAT', 'libelle' => 'Mathématiques'],
            ['code' => 'FRA', 'libelle' => 'Français'],
            ['code' => 'SCI', 'libelle' => 'Sciences'],
            ['code' => 'HG', 'libelle' => 'Histoire Géographie'],
            ['code' => 'ANG', 'libelle' => 'Anglais'],
            ['code' => 'EPS', 'libelle' => 'Éducation Physique'],
        ];

        // §1.1 : matieres → matieres_unites. Skip si absente.
        if (\Schema::hasTable('matieres_unites')) {
            foreach ($matieres_data as $m) {
                $id = DB::table('matieres_unites')->insertGetId(array_merge($m, [
                    'ecole_id'          => $ecole->id,
                    'annee_scolaire_id' => $annee->id,
                    'coefficient'       => 1,
                    'note_max'          => 20,
                    'etat'              => 'actif',
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]));
                $matieres[$m['code']] = $id;
            }
        }

        // 4. Créer les users pour les enseignants
        $users = [];
        $enseignants_data = [
            ['nom' => 'Paul', 'prenoms' => 'Jean', 'specialite' => 'Mathématiques', 'email' => 'jean.paul@school.com'],
            ['nom' => 'Martin', 'prenoms' => 'Sophie', 'specialite' => 'Français', 'email' => 'sophie.martin@school.com'],
            ['nom' => 'Dupont', 'prenoms' => 'Marc', 'specialite' => 'Sciences', 'email' => 'marc.dupont@school.com'],
            ['nom' => 'Leclerc', 'prenoms' => 'Anne', 'specialite' => 'Histoire', 'email' => 'anne.leclerc@school.com'],
            ['nom' => 'Bernard', 'prenoms' => 'Pierre', 'specialite' => 'Anglais', 'email' => 'pierre.bernard@school.com'],
            ['nom' => 'Moreau', 'prenoms' => 'Claude', 'specialite' => 'EPS', 'email' => 'claude.moreau@school.com'],
        ];

        foreach ($enseignants_data as $e) {
            $user_id = DB::table('users')->insertGetId([
                'name' => $e['prenoms'] . ' ' . $e['nom'],
                'email' => $e['email'],
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $users[$e['nom']] = $user_id;
        }

        // 5. Créer les enseignants
        $enseignants = [];
        foreach ($enseignants_data as $e) {
            $id = DB::table('enseignants')->insertGetId(array_merge($e, [
                'user_id' => $users[$e['nom']],
                'gender' => rand(0, 1) ? 'M' : 'F',
                'date_of_birth' => now()->subYears(rand(30, 50))->format('Y-m-d'),
                'statut' => 'actif',
                'type_contrat' => 'cdi',
                'created_at' => now(),
                'updated_at' => now()
            ]));
            $enseignants[$e['nom']] = $id;
        }

        // 6. Créer la classe
        $classe_id = DB::table('classes')->insertGetId([
            'nom' => '6ème A',
            'libelle_affichage' => 'Classe 6ème A',
            'ecole_id' => $ecole->id,
            'annee_scolaire_id' => $annee->id,
            'niveau_id' => $niveaux['6'],
            'statut' => 'actif',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 7. Créer l'emploi du temps réaliste (Lundi-Samedi, 8h-17h)
        $emploi_du_temps = [
            // LUNDI
            ['jour' => 'lundi', 'heure_debut' => '08:00', 'heure_fin' => '10:00', 'matiere' => 'MAT', 'enseignant' => 'Paul'],
            ['jour' => 'lundi', 'heure_debut' => '10:15', 'heure_fin' => '12:00', 'matiere' => 'FRA', 'enseignant' => 'Martin'],
            ['jour' => 'lundi', 'heure_debut' => '13:00', 'heure_fin' => '14:30', 'matiere' => 'SCI', 'enseignant' => 'Dupont'],
            ['jour' => 'lundi', 'heure_debut' => '14:45', 'heure_fin' => '16:30', 'matiere' => 'EPS', 'enseignant' => 'Moreau'],

            // MARDI
            ['jour' => 'mardi', 'heure_debut' => '08:00', 'heure_fin' => '09:30', 'matiere' => 'FRA', 'enseignant' => 'Martin'],
            ['jour' => 'mardi', 'heure_debut' => '09:45', 'heure_fin' => '11:30', 'matiere' => 'HG', 'enseignant' => 'Leclerc'],
            ['jour' => 'mardi', 'heure_debut' => '13:00', 'heure_fin' => '14:45', 'matiere' => 'ANG', 'enseignant' => 'Bernard'],
            ['jour' => 'mardi', 'heure_debut' => '15:00', 'heure_fin' => '16:30', 'matiere' => 'MAT', 'enseignant' => 'Paul'],

            // MERCREDI
            ['jour' => 'mercredi', 'heure_debut' => '08:00', 'heure_fin' => '09:30', 'matiere' => 'SCI', 'enseignant' => 'Dupont'],
            ['jour' => 'mercredi', 'heure_debut' => '09:45', 'heure_fin' => '11:30', 'matiere' => 'MAT', 'enseignant' => 'Paul'],
            ['jour' => 'mercredi', 'heure_debut' => '13:00', 'heure_fin' => '14:30', 'matiere' => 'FRA', 'enseignant' => 'Martin'],

            // JEUDI
            ['jour' => 'jeudi', 'heure_debut' => '08:00', 'heure_fin' => '09:30', 'matiere' => 'ANG', 'enseignant' => 'Bernard'],
            ['jour' => 'jeudi', 'heure_debut' => '09:45', 'heure_fin' => '11:30', 'matiere' => 'HG', 'enseignant' => 'Leclerc'],
            ['jour' => 'jeudi', 'heure_debut' => '13:00', 'heure_fin' => '14:45', 'matiere' => 'SCI', 'enseignant' => 'Dupont'],
            ['jour' => 'jeudi', 'heure_debut' => '15:00', 'heure_fin' => '16:30', 'matiere' => 'EPS', 'enseignant' => 'Moreau'],

            // VENDREDI
            ['jour' => 'vendredi', 'heure_debut' => '08:00', 'heure_fin' => '10:00', 'matiere' => 'MAT', 'enseignant' => 'Paul'],
            ['jour' => 'vendredi', 'heure_debut' => '10:15', 'heure_fin' => '12:00', 'matiere' => 'FRA', 'enseignant' => 'Martin'],
            ['jour' => 'vendredi', 'heure_debut' => '13:00', 'heure_fin' => '14:30', 'matiere' => 'ANG', 'enseignant' => 'Bernard'],

            // SAMEDI
            ['jour' => 'samedi', 'heure_debut' => '08:00', 'heure_fin' => '09:30', 'matiere' => 'HG', 'enseignant' => 'Leclerc'],
            ['jour' => 'samedi', 'heure_debut' => '09:45', 'heure_fin' => '11:30', 'matiere' => 'SCI', 'enseignant' => 'Dupont'],
            ['jour' => 'samedi', 'heure_debut' => '13:00', 'heure_fin' => '14:30', 'matiere' => 'EPS', 'enseignant' => 'Moreau'],
        ];

        // Insert emploi du temps
        foreach ($emploi_du_temps as $emp) {
            DB::table('emplois_temps')->insert([
                'classe_id' => $classe_id,
                'jour' => $emp['jour'],
                'heure_debut' => $emp['heure_debut'],
                'heure_fin' => $emp['heure_fin'],
                'matiere_id' => $matieres[$emp['matiere']],
                'enseignant_id' => $enseignants[$emp['enseignant']],
                'salle' => 'Salle 6A',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        echo "✅ Emploi du temps complet créé!\n";
        echo "   Classe: 6ème A\n";
        echo "   Matières: " . count($matieres) . "\n";
        echo "   Enseignants: " . count($enseignants) . "\n";
        echo "   Créneaux: " . count($emploi_du_temps) . "\n";
    }
}
