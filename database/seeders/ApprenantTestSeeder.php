<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ApprenantTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['nom' => 'Dupont', 'prenoms' => 'Jean', 'login' => 'jdupont', 'email' => 'jean.dupont@test.fr'],
            ['nom' => 'Martin', 'prenoms' => 'Marie', 'login' => 'mmartin', 'email' => 'marie.martin@test.fr'],
            ['nom' => 'Bernard', 'prenoms' => 'Pierre', 'login' => 'pbernard', 'email' => 'pierre.bernard@test.fr'],
            ['nom' => 'Leclerc', 'prenoms' => 'Sophie', 'login' => 'sleclerc', 'email' => 'sophie.leclerc@test.fr'],
            ['nom' => 'Moreau', 'prenoms' => 'Luc', 'login' => 'lmoreau', 'email' => 'luc.moreau@test.fr'],
        ];

        // Get a test classe for apprenants
        $classe = \Modules\Parametrage\Entities\Classe::first();

        $counter = 0;
        foreach ($users as $userData) {
            $user = \App\Models\User::create([
                'nom' => $userData['nom'],
                'prenoms' => $userData['prenoms'],
                'login' => $userData['login'],
                'email' => $userData['email'],
                'password' => bcrypt('password123'),
                'full_login' => $userData['prenoms'] . ' ' . $userData['nom'],
                'uuid' => \Illuminate\Support\Str::uuid(),
                'code_owner' => 'TEST',
                'qr_data' => 'test-' . rand(1000, 9999),
            ]);

            // Créer l'apprenant lié à l'utilisateur et assigné à une classe
            \Modules\Academique\Entities\Apprenant::create([
                'user_id' => $user->id,
                'matricule' => 'APR-' . str_pad($user->id, 5, '0', STR_PAD_LEFT),
                'sexe' => rand(0, 1) ? 'M' : 'F',
                'date_naissance' => now()->subYears(rand(15, 18)),
                'lieu_naissance' => 'Test City',
                'classe_id' => $classe?->id,  // Assigner à la classe
                'statut' => 'actif',
            ]);
            $counter++;
        }

        echo "✅ 5 apprenants de test créés!\n";
    }
}
