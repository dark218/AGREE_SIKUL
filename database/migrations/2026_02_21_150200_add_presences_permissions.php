<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // idempotence guard
        // Create permissions for presences
        $actions = ['list', 'create', 'read', 'update', 'delete', 'activate'];

        foreach ($actions as $action) {
            if (!DB::table('permissions')->where('name', "presences-{$action}")->exists()) {
                DB::table('permissions')->insert([
                    'name' => "presences-{$action}",
                    'libelle' => match($action) {
                        'list' => 'Voir la liste',
                        'create' => 'Créer',
                        'read' => 'Voir détails',
                        'update' => 'Modifier',
                        'delete' => 'Supprimer',
                        'activate' => 'Activer/Désactiver',
                    },
                    'guard_name' => 'web',
                    'feature_id' => 123,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $actions = ['list', 'create', 'read', 'update', 'delete', 'activate'];

        foreach ($actions as $action) {
            Permission::where('name', "presences-{$action}")->delete();
        }
    }
};
