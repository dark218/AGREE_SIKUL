<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MoveSchoolFeaturesToParametrage extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parametrageModuleId = 23; // Paramétrage module

        // Get the highest feature ID in Paramétrage to continue from there
        $maxId = DB::table('feature')
            ->where('module_id', $parametrageModuleId)
            ->max('id') ?? 0;
        $nextId = $maxId + 1;

        $features = [
            [
                'libelle' => 'Institution',
                'libelle_en' => 'Institution',
                'menu_url' => 'institutions',
                'icone' => 'fas fa-university',
                'ordre' => 100,
            ],
            [
                'libelle' => 'Campus',
                'libelle_en' => 'Campus',
                'menu_url' => 'campuses',
                'icone' => 'fas fa-map-marker-alt',
                'ordre' => 101,
            ],
            [
                'libelle' => 'Écoles',
                'libelle_en' => 'Schools',
                'menu_url' => 'ecoles',
                'icone' => 'fas fa-school',
                'ordre' => 102,
            ],
            [
                'libelle' => 'Configurations de l\'école',
                'libelle_en' => 'School Configuration',
                'menu_url' => 'annees-scolaires',
                'icone' => 'fas fa-cog',
                'ordre' => 103,
            ],
            [
                'libelle' => 'Sections',
                'libelle_en' => 'Sections',
                'menu_url' => 'sections',
                'icone' => 'fas fa-th-list',
                'ordre' => 104,
            ],
            [
                'libelle' => 'Cycles d\'Enseignement',
                'libelle_en' => 'Teaching Cycles',
                'menu_url' => 'cycles-enseignement',
                'icone' => 'fas fa-ring',
                'ordre' => 105,
            ],
            [
                'libelle' => 'Niveaux',
                'libelle_en' => 'Levels',
                'menu_url' => 'niveaux',
                'icone' => 'fas fa-layer-group',
                'ordre' => 106,
            ],
            [
                'libelle' => 'Classes',
                'libelle_en' => 'Classes',
                'menu_url' => 'classes',
                'icone' => 'fas fa-chalkboard',
                'ordre' => 107,
            ],
        ];

        foreach ($features as $feature) {
            // Check if already exists in Paramétrage
            $exists = DB::table('feature')
                ->where('module_id', $parametrageModuleId)
                ->where('menu_url', $feature['menu_url'])
                ->exists();

            if (!$exists) {
                DB::table('feature')->insert([
                    'id' => $nextId++,
                    'libelle' => $feature['libelle'],
                    'libelle_en' => $feature['libelle_en'],
                    'module_id' => $parametrageModuleId,
                    'menu_url' => $feature['menu_url'],
                    'icone' => $feature['icone'],
                    'ordre' => $feature['ordre'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                echo "✅ {$feature['libelle']} ajouté à Paramétrage\n";
            } else {
                echo "⏭️  {$feature['libelle']} existe déjà dans Paramétrage\n";
            }
        }

        echo "\n✨ Toutes les fonctionnalités sont maintenant dans le module Paramétrage!\n";
    }
}
