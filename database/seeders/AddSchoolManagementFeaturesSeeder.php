<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddSchoolManagementFeaturesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the highest feature ID to continue from there
        $maxId = DB::table('feature')->max('id') ?? 0;
        $nextId = $maxId + 1;

        // Module IDs
        $institutionModuleId = 1;  // Institutions
        $campusModuleId = 2;       // Campus
        $ecoleModuleId = 3;        // Écoles
        $parametrageModuleId = 23; // Paramétrage

        $features = [
            // ===== INSTITUTION MODULE =====
            [
                'id' => $nextId++,
                'libelle' => 'Institutions',
                'libelle_en' => 'Institutions',
                'module_id' => $institutionModuleId,
                'menu_url' => 'institutions',
                'icone' => 'fas fa-university',
                'ordre' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // ===== CAMPUS MODULE =====
            [
                'id' => $nextId++,
                'libelle' => 'Campus',
                'libelle_en' => 'Campus',
                'module_id' => $campusModuleId,
                'menu_url' => 'campuses',
                'icone' => 'fas fa-map-marker-alt',
                'ordre' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // ===== ÉCOLES MODULE =====
            [
                'id' => $nextId++,
                'libelle' => 'Écoles',
                'libelle_en' => 'Schools',
                'module_id' => $ecoleModuleId,
                'menu_url' => 'ecoles',
                'icone' => 'fas fa-school',
                'ordre' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => $nextId++,
                'libelle' => 'Niveaux',
                'libelle_en' => 'Levels',
                'module_id' => $ecoleModuleId,
                'menu_url' => 'niveaux',
                'icone' => 'fas fa-layer-group',
                'ordre' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => $nextId++,
                'libelle' => 'Classes',
                'libelle_en' => 'Classes',
                'module_id' => $ecoleModuleId,
                'menu_url' => 'classes',
                'icone' => 'fas fa-chalkboard',
                'ordre' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => $nextId++,
                'libelle' => 'Années Scolaires',
                'libelle_en' => 'School Years',
                'module_id' => $ecoleModuleId,
                'menu_url' => 'annees-scolaires',
                'icone' => 'fas fa-calendar-alt',
                'ordre' => 4,
                'created_at' => now(),
                'updated_at' => now()
            ],

            // ===== PARAMETRAGE MODULE =====
            [
                'id' => $nextId++,
                'libelle' => 'Cycles d\'Enseignement',
                'libelle_en' => 'Teaching Cycles',
                'module_id' => $parametrageModuleId,
                'menu_url' => 'cycles-enseignement',
                'icone' => 'fas fa-circle-notch',
                'ordre' => 10,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => $nextId++,
                'libelle' => 'Sections',
                'libelle_en' => 'Sections',
                'module_id' => $parametrageModuleId,
                'menu_url' => 'sections',
                'icone' => 'fas fa-th-list',
                'ordre' => 11,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ];

        // Insert features
        foreach ($features as $feature) {
            // Check if feature already exists
            $exists = DB::table('feature')
                ->where('module_id', $feature['module_id'])
                ->where('menu_url', $feature['menu_url'])
                ->exists();

            if (!$exists) {
                DB::table('feature')->insert($feature);
                echo "✅ Feature '{$feature['libelle']}' created\n";
            } else {
                echo "⏭️  Feature '{$feature['libelle']}' already exists\n";
            }
        }

        echo "\n✨ School management features seeding completed!\n";
    }
}
