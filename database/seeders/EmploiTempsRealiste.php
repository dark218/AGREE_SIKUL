<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmploiTempsRealiste extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // 1. Récupérer les données existantes
        $ecole = DB::table('ecoles')->first();
        $annee = DB::table('annees_scolaires')->first();

        if (!$ecole || !$annee) {
            echo "❌ Manque école ou année scolaire\n";
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            return;
        }

        // 2. Récupérer les matières existantes
        $matieres_map = [
            'MAT' => null, 'FRA' => null, 'SCI' => null,
            'HG' => null, 'ANG' => null, 'EPS' => null
        ];

        // §1.1 : table `matieres` → `matieres_unites`
        $matieresTable = \Schema::hasTable('matieres_unites') ? 'matieres_unites' : null;
        if ($matieresTable) {
            foreach (array_keys($matieres_map) as $code) {
                $m = DB::table($matieresTable)->where('code', $code)->first();
                if ($m) {
                    $matieres_map[$code] = $m->id;
                }
            }
        }

        // 3. Créer les enseignants (si besoin)
        $enseignants_map = [];
        $enseignants_data = [
            ['nom' => 'Paul', 'prenoms' => 'Jean', 'email' => 'jean.paul@school.com', 'type' => 'MAT'],
            ['nom' => 'Martin', 'prenoms' => 'Sophie', 'email' => 'sophie.martin@school.com', 'type' => 'FRA'],
            ['nom' => 'Dupont', 'prenoms' => 'Marc', 'email' => 'marc.dupont@school.com', 'type' => 'SCI'],
            ['nom' => 'Leclerc', 'prenoms' => 'Anne', 'email' => 'anne.leclerc@school.com', 'type' => 'HG'],
            ['nom' => 'Bernard', 'prenoms' => 'Pierre', 'email' => 'pierre.bernard@school.com', 'type' => 'ANG'],
            ['nom' => 'Moreau', 'prenoms' => 'Claude', 'email' => 'claude.moreau@school.com', 'type' => 'EPS'],
        ];

        foreach ($enseignants_data as $e) {
            // Vérifier si existe
            $existing = DB::table('enseignants')->where('nom', $e['nom'])->where('prenoms', $e['prenoms'])->first();
            if ($existing) {
                $enseignants_map[$e['nom']] = $existing->id;
            } else {
                // Créer user
                $user_id = DB::table('users')->insertGetId([
                    'name' => $e['prenoms'] . ' ' . $e['nom'],
                    'email' => $e['email'],
                    'password' => bcrypt('password'),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Créer enseignant
                $ens_id = DB::table('enseignants')->insertGetId([
                    'nom' => $e['nom'],
                    'prenoms' => $e['prenoms'],
                    'user_id' => $user_id,
                    'email' => $e['email'],
                    'gender' => (rand(0, 1) ? 'M' : 'F'),
                    'date_of_birth' => now()->subYears(rand(30, 50))->format('Y-m-d'),
                    'statut' => 'actif',
                    'type_contrat' => 'cdi',
                    'specialite' => $e['nom'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $enseignants_map[$e['nom']] = $ens_id;
            }
        }

        // 4. Créer une classe
        $classe_id = DB::table('classes')->insertGetId([
            'nom' => '6ème A',
            'libelle_affichage' => 'Classe 6ème A - Emploi du temps complet',
            'ecole_id' => $ecole->id,
            'annee_scolaire_id' => $annee->id,
            'niveau_id' => \Schema::hasTable('niveaux_etudes')
                ? (DB::table('niveaux_etudes')->where('code', '6')->first()?->id ?? 1)
                : 1,
            'statut' => 'actif',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 5. Créer l'emploi du temps réaliste (Lundi-Samedi)
        $emploi_du_temps = [
            // LUNDI
            ['jour' => 'lundi', 'heure_debut' => '08:00', 'heure_fin' => '10:00', 'matiere' => 'MAT', 'enseignant' => 'Paul', 'salle' => '6A-01'],
            ['jour' => 'lundi', 'heure_debut' => '10:15', 'heure_fin' => '12:00', 'matiere' => 'FRA', 'enseignant' => 'Martin', 'salle' => '6A-01'],
            ['jour' => 'lundi', 'heure_debut' => '13:00', 'heure_fin' => '14:30', 'matiere' => 'SCI', 'enseignant' => 'Dupont', 'salle' => 'Labo'],
            ['jour' => 'lundi', 'heure_debut' => '14:45', 'heure_fin' => '16:30', 'matiere' => 'EPS', 'enseignant' => 'Moreau', 'salle' => 'Gym'],

            // MARDI
            ['jour' => 'mardi', 'heure_debut' => '08:00', 'heure_fin' => '09:30', 'matiere' => 'FRA', 'enseignant' => 'Martin', 'salle' => '6A-01'],
            ['jour' => 'mardi', 'heure_debut' => '09:45', 'heure_fin' => '11:30', 'matiere' => 'HG', 'enseignant' => 'Leclerc', 'salle' => '6A-01'],
            ['jour' => 'mardi', 'heure_debut' => '13:00', 'heure_fin' => '14:45', 'matiere' => 'ANG', 'enseignant' => 'Bernard', 'salle' => '6A-02'],
            ['jour' => 'mardi', 'heure_debut' => '15:00', 'heure_fin' => '16:30', 'matiere' => 'MAT', 'enseignant' => 'Paul', 'salle' => '6A-01'],

            // MERCREDI
            ['jour' => 'mercredi', 'heure_debut' => '08:00', 'heure_fin' => '09:30', 'matiere' => 'SCI', 'enseignant' => 'Dupont', 'salle' => 'Labo'],
            ['jour' => 'mercredi', 'heure_debut' => '09:45', 'heure_fin' => '11:30', 'matiere' => 'MAT', 'enseignant' => 'Paul', 'salle' => '6A-01'],
            ['jour' => 'mercredi', 'heure_debut' => '13:00', 'heure_fin' => '14:30', 'matiere' => 'FRA', 'enseignant' => 'Martin', 'salle' => '6A-01'],

            // JEUDI
            ['jour' => 'jeudi', 'heure_debut' => '08:00', 'heure_fin' => '09:30', 'matiere' => 'ANG', 'enseignant' => 'Bernard', 'salle' => '6A-02'],
            ['jour' => 'jeudi', 'heure_debut' => '09:45', 'heure_fin' => '11:30', 'matiere' => 'HG', 'enseignant' => 'Leclerc', 'salle' => '6A-01'],
            ['jour' => 'jeudi', 'heure_debut' => '13:00', 'heure_fin' => '14:45', 'matiere' => 'SCI', 'enseignant' => 'Dupont', 'salle' => 'Labo'],
            ['jour' => 'jeudi', 'heure_debut' => '15:00', 'heure_fin' => '16:30', 'matiere' => 'EPS', 'enseignant' => 'Moreau', 'salle' => 'Gym'],

            // VENDREDI
            ['jour' => 'vendredi', 'heure_debut' => '08:00', 'heure_fin' => '10:00', 'matiere' => 'MAT', 'enseignant' => 'Paul', 'salle' => '6A-01'],
            ['jour' => 'vendredi', 'heure_debut' => '10:15', 'heure_fin' => '12:00', 'matiere' => 'FRA', 'enseignant' => 'Martin', 'salle' => '6A-01'],
            ['jour' => 'vendredi', 'heure_debut' => '13:00', 'heure_fin' => '14:30', 'matiere' => 'ANG', 'enseignant' => 'Bernard', 'salle' => '6A-02'],

            // SAMEDI
            ['jour' => 'samedi', 'heure_debut' => '08:00', 'heure_fin' => '09:30', 'matiere' => 'HG', 'enseignant' => 'Leclerc', 'salle' => '6A-01'],
            ['jour' => 'samedi', 'heure_debut' => '09:45', 'heure_fin' => '11:30', 'matiere' => 'SCI', 'enseignant' => 'Dupont', 'salle' => 'Labo'],
            ['jour' => 'samedi', 'heure_debut' => '13:00', 'heure_fin' => '14:30', 'matiere' => 'EPS', 'enseignant' => 'Moreau', 'salle' => 'Gym'],
        ];

        // Insert emploi du temps
        foreach ($emploi_du_temps as $emp) {
            DB::table('emplois_temps')->insert([
                'classe_id' => $classe_id,
                'jour' => $emp['jour'],
                'heure_debut' => $emp['heure_debut'],
                'heure_fin' => $emp['heure_fin'],
                'matiere_id' => $matieres_map[$emp['matiere']],
                'enseignant_id' => $enseignants_map[$emp['enseignant']],
                'salle' => $emp['salle'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        echo "\n✅ EMPLOI DU TEMPS RÉALISTE CRÉÉ!\n";
        echo "   Classe: 6ème A\n";
        echo "   Enseignants: " . count($enseignants_map) . "\n";
        echo "   Créneaux: " . count($emploi_du_temps) . "\n";
        echo "\n📚 Emploi du temps:\n";
        echo "   Lundi-Samedi: 8h00-17h30 (avec pause midi 12h-13h)\n";
        echo "   4 créneaux par jour de 1h30 à 2h\n";
        echo "\n💡 Utilisation:\n";
        echo "   Quand on crée une absence enseignant et qu'on sélectionne un enseignant,\n";
        echo "   l'emploi du temps se charge automatiquement pour remplir les détails!\n";
    }
}
