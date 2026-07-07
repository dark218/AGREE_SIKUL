<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MatiereClasseTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create a test ecole for matieres
        $ecole = \Modules\Parametrage\Entities\Ecole::first() ??
                 \Modules\Parametrage\Entities\Ecole::create([
                     'nom' => 'Ecole Test',
                     'statut' => 'actif'
                 ]);

        // Create test Matieres
        $matieres = [
            ['libelle' => 'Français', 'code' => 'FR', 'ecole_id' => $ecole->id],
            ['libelle' => 'Mathématiques', 'code' => 'MATH', 'ecole_id' => $ecole->id],
            ['libelle' => 'Anglais', 'code' => 'EN', 'ecole_id' => $ecole->id],
            ['libelle' => 'Sciences Naturelles', 'code' => 'SN', 'ecole_id' => $ecole->id],
            ['libelle' => 'Histoire-Géographie', 'code' => 'HG', 'ecole_id' => $ecole->id],
        ];

        foreach ($matieres as $matiere) {
            try {
                \Modules\Parametrage\Entities\MatiereUnite::create($matiere);
            } catch (\Exception $e) {
                // Matiere may already exist
            }
        }

        // Create test Classes
        $classes = [
            ['nom' => '6ème A', 'code' => 'C001'],
            ['nom' => '6ème B', 'code' => 'C002'],
            ['nom' => '5ème A', 'code' => 'C003'],
            ['nom' => '5ème B', 'code' => 'C004'],
            ['nom' => '4ème A', 'code' => 'C005'],
        ];

        foreach ($classes as $classe) {
            try {
                \Modules\Parametrage\Entities\Classe::create($classe);
            } catch (\Exception $e) {
                // Classe may already exist
            }
        }

        echo "✅ Matieres et Classes de test créées!\n";
    }
}
