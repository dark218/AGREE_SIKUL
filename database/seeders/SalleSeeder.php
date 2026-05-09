<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salles = [
            [
                'code' => 'SALLE-101',
                'libelle' => 'Salle 101',
                'capacite' => 40,
                'statut' => 'actif',
                'description' => 'Salle de classe standard avec tableau blanc',
            ],
            [
                'code' => 'SALLE-102',
                'libelle' => 'Salle 102',
                'capacite' => 35,
                'statut' => 'actif',
                'description' => 'Salle de classe avec projecteur',
            ],
            [
                'code' => 'SALLE-103',
                'libelle' => 'Salle 103',
                'capacite' => 30,
                'statut' => 'actif',
                'description' => 'Petite salle de classe',
            ],
            [
                'code' => 'SALLE-201',
                'libelle' => 'Salle 201',
                'capacite' => 50,
                'statut' => 'actif',
                'description' => 'Salle de classe spacieuse',
            ],
            [
                'code' => 'LABO-001',
                'libelle' => 'Laboratoire Informatique',
                'capacite' => 25,
                'statut' => 'actif',
                'description' => 'Salle informatique avec 25 ordinateurs',
            ],
            [
                'code' => 'LABO-002',
                'libelle' => 'Laboratoire Sciences',
                'capacite' => 30,
                'statut' => 'actif',
                'description' => 'Salle de sciences avec équipement',
            ],
            [
                'code' => 'AUDI-001',
                'libelle' => 'Amphithéâtre 1',
                'capacite' => 100,
                'statut' => 'actif',
                'description' => 'Grand amphithéâtre pour conférences',
            ],
            [
                'code' => 'BIBO-001',
                'libelle' => 'Bibliothèque',
                'capacite' => 60,
                'statut' => 'actif',
                'description' => 'Salle de bibliothèque et consultation',
            ],
        ];

        DB::table('salles')->insert($salles);
    }
}
