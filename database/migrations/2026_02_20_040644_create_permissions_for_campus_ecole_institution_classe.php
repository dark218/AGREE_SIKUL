<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Désactiver temporairement les contraintes de clé étrangère
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Permissions for Institution (feature_id = 16)
        DB::table('permissions')->insert([
            ['name' => 'institution-list', 'libelle' => 'Liste', 'guard_name' => 'web', 'feature_id' => 16, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'institution-create', 'libelle' => 'Nouveau', 'guard_name' => 'web', 'feature_id' => 16, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'institution-edit', 'libelle' => 'Modifier', 'guard_name' => 'web', 'feature_id' => 16, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'institution-delete', 'libelle' => 'Supprimer', 'guard_name' => 'web', 'feature_id' => 16, 'created_at' => now(), 'updated_at' => now()],

            // Permissions for Campus (feature_id = 17)
            ['name' => 'campus-list', 'libelle' => 'Liste', 'guard_name' => 'web', 'feature_id' => 17, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'campus-create', 'libelle' => 'Nouveau', 'guard_name' => 'web', 'feature_id' => 17, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'campus-edit', 'libelle' => 'Modifier', 'guard_name' => 'web', 'feature_id' => 17, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'campus-delete', 'libelle' => 'Supprimer', 'guard_name' => 'web', 'feature_id' => 17, 'created_at' => now(), 'updated_at' => now()],

            // Permissions for École (feature_id = 18)
            ['name' => 'ecole-list', 'libelle' => 'Liste', 'guard_name' => 'web', 'feature_id' => 18, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ecole-create', 'libelle' => 'Nouveau', 'guard_name' => 'web', 'feature_id' => 18, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ecole-edit', 'libelle' => 'Modifier', 'guard_name' => 'web', 'feature_id' => 18, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ecole-delete', 'libelle' => 'Supprimer', 'guard_name' => 'web', 'feature_id' => 18, 'created_at' => now(), 'updated_at' => now()],

            // Permissions for Classe (feature_id = 20)
            ['name' => 'classe-list', 'libelle' => 'Liste', 'guard_name' => 'web', 'feature_id' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'classe-create', 'libelle' => 'Nouveau', 'guard_name' => 'web', 'feature_id' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'classe-edit', 'libelle' => 'Modifier', 'guard_name' => 'web', 'feature_id' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'classe-delete', 'libelle' => 'Supprimer', 'guard_name' => 'web', 'feature_id' => 20, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Réactiver les contraintes de clé étrangère
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permissions')->whereIn('name', [
            'institution-list', 'institution-create', 'institution-edit', 'institution-delete',
            'campus-list', 'campus-create', 'campus-edit', 'campus-delete',
            'ecole-list', 'ecole-create', 'ecole-edit', 'ecole-delete',
            'classe-list', 'classe-create', 'classe-edit', 'classe-delete',
        ])->delete();
    }
};
