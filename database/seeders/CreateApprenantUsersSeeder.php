<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CreateApprenantUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure apprenant role exists
        $role = Role::firstOrCreate(['name' => 'apprenant', 'guard_name' => 'web']);

        $apprenants = [
            ['prenoms' => 'Ahmed', 'nom' => 'Hassan', 'email' => 'ahmed.hassan@sikul.com'],
            ['prenoms' => 'Fatima', 'nom' => 'Diallo', 'email' => 'fatima.diallo@sikul.com'],
            ['prenoms' => 'Moussa', 'nom' => 'Kone', 'email' => 'moussa.kone@sikul.com'],
            ['prenoms' => 'Aisha', 'nom' => 'Ibrahim', 'email' => 'aisha.ibrahim@sikul.com'],
            ['prenoms' => 'Kofi', 'nom' => 'Mensah', 'email' => 'kofi.mensah@sikul.com'],
        ];

        foreach ($apprenants as $data) {
            $login = strtolower($data['prenoms'] . '.' . $data['nom']);

            // Check if user already exists
            $existingUser = User::where('login', $login)->first();
            if ($existingUser) {
                // Ensure they have the apprenant role
                if (!$existingUser->hasRole('apprenant')) {
                    $existingUser->assignRole('apprenant');
                }
                echo "ℹ️  Apprenant existant: {$existingUser->prenoms} {$existingUser->nom}\n";
                continue;
            }

            $user = User::create([
                'prenoms' => $data['prenoms'],
                'nom' => $data['nom'],
                'email' => $data['email'],
                'login' => $login,
                'full_login' => $login,
                'uuid' => Str::uuid(),
                'qr_data' => Str::uuid(),
                'code_owner' => 'SIKUL',
                'password' => bcrypt('password'),
                'statut' => 'actif',
            ]);

            $user->assignRole('apprenant');
            echo "✅ Créé apprenant: {$user->prenoms} {$user->nom}\n";
        }
    }
}
