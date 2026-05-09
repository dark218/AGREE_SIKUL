<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get Dakar region (or any region that exists)
        $dakarRegion = \DB::table('regions')->where('code', 'DAKAR')->first();
        $regionId = $dakarRegion?->id ?? 1; // Default to 1 if no region found

        // Get Senegal pays
        $senegal = \DB::table('pays')->where('code', 'SEN')->first();
        $paysId = $senegal?->id ?? 1; // Default to 1 if no country found

        if (\DB::table('departements')->count() == 0) {
            $departements = [
                // Dakar departments (example)
                [
                    'code' => 'DAKAR_CENTRE',
                    'libelle' => 'Dakar Centre',
                    'region_id' => $regionId,
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'DAKAR_BANLIEUE',
                    'libelle' => 'Dakar Banlieue',
                    'region_id' => $regionId,
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'PIKINE',
                    'libelle' => 'Pikine',
                    'region_id' => $regionId,
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'RUFISQUE',
                    'libelle' => 'Rufisque',
                    'region_id' => $regionId,
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'GUEDIAWAYE',
                    'libelle' => 'Guédiawaye',
                    'region_id' => $regionId,
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            \DB::table('departements')->insert($departements);
            $this->command->info('✅ Departements seeded: ' . count($departements) . ' departements created');
        } else {
            $this->command->info('⚠️ Departements table already has data, skipping seed');
        }
    }
}
