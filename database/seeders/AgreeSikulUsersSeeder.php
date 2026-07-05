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
                // Idempotent : on ne recrée pas un utilisateur déjà présent
                // (le seeder peut être relancé sans casser sur la contrainte unique).
                $existing = DB::table('users')
                    ->where('login', $userData['login'])
                    ->orWhere('email', $userData['email'])
                    ->first();

                if ($existing) {
                    $userId = $existing->id;
                    $this->command->info("↺ {$userData['email']} existe déjà (id {$userId}) — ignoré");
                } else {
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
                    $this->command->info("✅ {$userData['nom']} {$userData['prenoms']} créé (id {$userId})");
                }

                // Assigner le rôle (sans créer de doublon dans model_has_roles)
                $roleId = DB::table('roles')->where('name', $role)->first()?->id;
                if ($roleId) {
                    $hasRole = DB::table('model_has_roles')->where([
                        'role_id' => $roleId,
                        'model_type' => 'App\\Models\\User',
                        'model_id' => $userId,
                    ])->exists();

                    if (!$hasRole) {
                        DB::table('model_has_roles')->insert([
                            'role_id' => $roleId,
                            'model_type' => 'App\\Models\\User',
                            'model_id' => $userId,
                        ]);
                    }
                    $this->command->info("   → rôle {$role}");
                } else {
                    $this->command->warn("⚠️  Rôle '{$role}' introuvable pour {$userData['email']}");
                }
            } catch (\Throwable $e) {
                // On log mais on NE stoppe PAS tout le seeding pour un seul utilisateur.
                $this->command->error("❌ {$userData['email']}: " . $e->getMessage());
                continue;
            }
        }

        $this->command->info("\n✅ Seeding utilisateurs terminé!");
    }
}
