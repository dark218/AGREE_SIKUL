<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AgreeSikulUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['nom' => 'ADMIN', 'prenoms' => 'Super', 'login' => '0700000001', 'email' => 'admin@agreesikul.com', 'role' => 'super_admin'],
            ['nom' => 'DIRECTEUR', 'prenoms' => 'Général', 'login' => '0700000002', 'email' => 'directeur@agreesikul.com', 'role' => 'directeur_general'],
            ['nom' => 'DIRECTEUR', 'prenoms' => 'Campus', 'login' => '0700000003', 'email' => 'campus@agreesikul.com', 'role' => 'directeur_campus'],
            ['nom' => 'DIRECTEUR', 'prenoms' => 'École', 'login' => '0700000004', 'email' => 'ecole@agreesikul.com', 'role' => 'directeur_ecole'],
            ['nom' => 'ENSEIGNANT', 'prenoms' => 'Test', 'login' => '0700000005', 'email' => 'prof@agreesikul.com', 'role' => 'enseignant'],
            ['nom' => 'PARENT', 'prenoms' => 'Test', 'login' => '0700000006', 'email' => 'parent@agreesikul.com', 'role' => 'parent'],
            ['nom' => 'ELEVE', 'prenoms' => 'Test', 'login' => '0700000007', 'email' => 'eleve@agreesikul.com', 'role' => 'eleve'],
            ['nom' => 'ADMIN', 'prenoms' => 'Personnel', 'login' => '0700000008', 'email' => 'personnel@agreesikul.com', 'role' => 'personnel_administratif'],
            ['nom' => 'BIBLIOTHECAIRE', 'prenoms' => 'Test', 'login' => '0700000009', 'email' => 'biblio@agreesikul.com', 'role' => 'bibliothecaire'],
            ['nom' => 'INFIRMIER', 'prenoms' => 'Test', 'login' => '0700000010', 'email' => 'infirmier@agreesikul.com', 'role' => 'infirmier'],
            ['nom' => 'SECURITE', 'prenoms' => 'Agent', 'login' => '0700000011', 'email' => 'securite@agreesikul.com', 'role' => 'agent_securite'],
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);

            try {
                // Créer l'utilisateur avec tous les champs requis
                $uuid = (string) Str::uuid();
                $userId = DB::table('users')->insertGetId(array_merge($userData, [
                    'uuid' => $uuid,
                    'full_login' => '+221' . $userData['login'], // Format: +221 (Sénégal) + numéro
                    'qr_data' => $uuid,
                    'alias_smil' => Str::slug($userData['nom'] . ' ' . $userData['prenoms']),
                    'code_owner' => Str::random(20),
                    'password' => Hash::make('password123'),
                    'statut' => 'actif', // Obligatoire: défaut DB = 'non_actif' sinon compte inutilisable
                    'kyc_status' => 'verifie',
                    'remember_token' => Str::random(10),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));

                // Assigner le rôle
                $roleId = DB::table('roles')->where('name', $role)->first()?->id;
                if ($roleId) {
                    DB::table('model_has_roles')->insert([
                        'role_id' => $roleId,
                        'model_type' => 'App\\Models\\User',
                        'model_id' => $userId,
                    ]);
                    $this->command->info("✅ {$userData['nom']} {$userData['prenoms']} → {$role}");
                } else {
                    $this->command->warn("⚠️  Rôle '{$role}' introuvable pour {$userData['email']}");
                }
            } catch (\Throwable $e) {
                // Log complet (pas de troncature) + relance pour ne pas masquer les bugs
                $this->command->error("❌ {$userData['email']}: " . $e->getMessage());
                throw $e;
            }
        }

        $this->command->info("\n✅ Seeding utilisateurs terminé!");
    }
}
