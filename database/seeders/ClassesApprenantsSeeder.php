<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Parametrage\Entities\Classe;
use Modules\Academique\Entities\Apprenant;
use DB;

class ClassesApprenantsSeeder extends Seeder
{
    public function run()
    {
        // Données fixes
        $ecole_id = 1;
        $niveau_id = 1;
        $section_id = 1;
        $cycle_id = 1;
        $annee_scolaire_id = 1;

        // Créer 2 classes
        if (!Classe::where('nom', 'TEST_6A')->exists()) {
            Classe::create([
                'nom' => 'TEST_6A',
                'niveau_id' => $niveau_id,
                'section_id' => $section_id,
                'cycle_id' => $cycle_id,
                'ecole_id' => $ecole_id,
                'annee_scolaire_id' => $annee_scolaire_id,
                'statut' => 'actif',
            ]);
            $this->command->info('✓ Classe créée: TEST_6A');
        }

        if (!Classe::where('nom', 'TEST_6B')->exists()) {
            Classe::create([
                'nom' => 'TEST_6B',
                'niveau_id' => $niveau_id,
                'section_id' => $section_id,
                'cycle_id' => $cycle_id,
                'ecole_id' => $ecole_id,
                'annee_scolaire_id' => $annee_scolaire_id,
                'statut' => 'actif',
            ]);
            $this->command->info('✓ Classe créée: TEST_6B');
        }

        // Récupérer les IDs des classes
        $classe_6a = Classe::where('nom', 'TEST_6A')->first();
        $classe_6b = Classe::where('nom', 'TEST_6B')->first();
        $classe_6a_id = $classe_6a ? $classe_6a->id : 1;
        $classe_6b_id = $classe_6b ? $classe_6b->id : 2;

        // Créer 8 apprenants via INSERT SQL direct
        $noms = ['Dupont', 'Martin', 'Bernard', 'Thomas', 'Robert', 'Richard', 'Petit', 'Durand'];
        $prenoms = ['Jean', 'Marie', 'Pierre', 'Sophie', 'Luc', 'Anne', 'Marc', 'Claire'];

        for ($i = 0; $i < 8; $i++) {
            $matricule = 'APP' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);

            if (!Apprenant::where('matricule', $matricule)->exists()) {
                // Alterner entre les deux classes: 0-3 en TEST_6A, 4-7 en TEST_6B
                $classe_id = $i < 4 ? $classe_6a_id : $classe_6b_id;

                DB::table('apprenants')->insert([
                    'user_id' => 1,  // Admin par défaut
                    'matricule' => $matricule,
                    'nom' => $noms[$i],
                    'prenoms' => $prenoms[$i],
                    'sexe' => ($i % 2 === 0) ? 'M' : 'F',
                    'date_naissance' => '2011-' . str_pad(($i % 12) + 1, 2, '0', STR_PAD_LEFT) . '-15',
                    'classe_id' => $classe_id,
                    'ecole_id' => $ecole_id,
                    'section_id' => $section_id,
                    'cycle_id' => $cycle_id,
                    'annee_scolaire_id' => $annee_scolaire_id,
                    'statut' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->command->info('✓ Apprenant: ' . $prenoms[$i] . ' ' . $noms[$i]);
            }
        }

        $this->command->info('');
        $this->command->info('=== Résumé ===');
        $this->command->info('Apprenants: ' . Apprenant::count());
        $this->command->info('Classes: ' . Classe::count());
        $this->command->info('✓ Seeding terminé!');
    }
}
