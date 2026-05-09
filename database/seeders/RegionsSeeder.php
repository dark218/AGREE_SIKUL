<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RegionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get pays (countries) - using example Senegal if it exists
        $pays = \DB::table('pays')->where('code', 'SEN')->first();
        $paysId = $pays?->id ?? 1; // Default to 1 if no country found

        if (\DB::table('regions')->count() == 0) {
            $regions = [
                // Senegal regions (example)
                [
                    'code' => 'DAKAR',
                    'libelle' => 'Dakar',
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'KAOLACK',
                    'libelle' => 'Kaolack',
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'KOLDA',
                    'libelle' => 'Kolda',
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'LOUGA',
                    'libelle' => 'Louga',
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'MATAM',
                    'libelle' => 'Matam',
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'SAINT_LOUIS',
                    'libelle' => 'Saint-Louis',
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'TAMBACOUNDA',
                    'libelle' => 'Tambacounda',
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'THIES',
                    'libelle' => 'Thiès',
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'ZIGUINCHOR',
                    'libelle' => 'Ziguinchor',
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            \DB::table('regions')->insert($regions);
            $this->command->info('✅ Regions seeded: ' . count($regions) . ' regions created');
        } else {
            $this->command->info('⚠️ Regions table already has data, skipping seed');
        }
    }
}
