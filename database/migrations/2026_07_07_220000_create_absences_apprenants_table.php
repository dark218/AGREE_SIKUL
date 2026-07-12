<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fonctionnalité « Absence Apprenant » (menu ABSENCES & PRÉSENCES).
 * Table calquée sur absences_enseignants, adaptée à l'apprenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('absences_apprenants')) {
            Schema::create('absences_apprenants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('apprenant_id')->constrained('apprenants')->cascadeOnDelete();
                $table->foreignId('classe_id')->nullable()->constrained('classes')->nullOnDelete();
                $table->unsignedBigInteger('matiere_id')->nullable();
                $table->dateTime('date_debut');
                $table->dateTime('date_fin');
                $table->text('motif')->nullable();
                $table->decimal('nombre_heures', 8, 2)->nullable();
                $table->json('justificatif_path')->nullable();
                $table->enum('statut', ['en_attente', 'validee', 'rejetee'])->default('en_attente');
                $table->enum('etat', ['actif', 'inactif'])->default('actif');
                // Colonnes d'audit (BaseModel)
                $table->string('checksum')->nullable();
                $table->string('external_id')->nullable();
                $table->string('source_system')->nullable();
                $table->string('creation_hostname')->nullable();
                $table->string('creation_username')->nullable();
                $table->string('modification_username')->nullable();
                $table->string('deletion_username')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Permissions + attribution au super_admin.
        if (Schema::hasTable('permissions') && Schema::hasTable('roles')) {
            $superAdminId = DB::table('roles')->where('name', 'super_admin')->value('id');
            $featureId = Schema::hasTable('feature')
                ? DB::table('feature')->where('menu_url', 'absences-apprenants')->value('id')
                : null;

            foreach (['list', 'create', 'edit', 'delete', 'activate'] as $action) {
                $name = 'absences_apprenants-' . $action;
                $permId = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->value('id');
                if (!$permId) {
                    $permId = DB::table('permissions')->insertGetId([
                        'name' => $name,
                        'libelle' => 'Absence Apprenant - ' . ucfirst($action),
                        'guard_name' => 'web',
                        'feature_id' => $featureId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                if ($superAdminId && !DB::table('role_has_permissions')->where('role_id', $superAdminId)->where('permission_id', $permId)->exists()) {
                    DB::table('role_has_permissions')->insert(['role_id' => $superAdminId, 'permission_id' => $permId]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('absences_apprenants');

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->where('name', 'like', 'absences_apprenants-%')->delete();
        }
    }
};
