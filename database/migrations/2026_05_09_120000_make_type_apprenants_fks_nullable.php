<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le formulaire de paramétrage TypeApprenant est volontairement simplifié à
 * code/libellé/etat. Les FK niveau_id/section_id/cycle_id/pays_id (NOT NULL
 * dans la migration d'origine) doivent donc devenir nullables sinon les
 * INSERT depuis le form simplifié plantent avec "Field 'xxx' doesn't have a
 * default value".
 */
return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (!Schema::hasTable('type_apprenants')) {
            return;
        }

        Schema::table('type_apprenants', function (Blueprint $table) {
            // Drop FK constraints d'abord pour pouvoir modifier les colonnes
            try { $table->dropForeign(['niveau_id']); } catch (\Throwable $e) {}
            try { $table->dropForeign(['section_id']); } catch (\Throwable $e) {}
            try { $table->dropForeign(['cycle_id']); } catch (\Throwable $e) {}
            try { $table->dropForeign(['pays_id']); } catch (\Throwable $e) {}
        });

        Schema::table('type_apprenants', function (Blueprint $table) {
            $table->unsignedBigInteger('niveau_id')->nullable()->change();
            $table->unsignedBigInteger('section_id')->nullable()->change();
            $table->unsignedBigInteger('cycle_id')->nullable()->change();
            $table->unsignedBigInteger('pays_id')->nullable()->change();
        });

        Schema::table('type_apprenants', function (Blueprint $table) {
            // Re-créer les FK en nullOnDelete (cohérent avec nullable)
            try {
                $table->foreign('niveau_id')->references('id')->on('niveaux_etudes')->nullOnDelete();
            } catch (\Throwable $e) {}
            try {
                $table->foreign('section_id')->references('id')->on('sections')->nullOnDelete();
            } catch (\Throwable $e) {}
            try {
                $table->foreign('cycle_id')->references('id')->on('cycles_enseignement')->nullOnDelete();
            } catch (\Throwable $e) {}
            try {
                $table->foreign('pays_id')->references('id')->on('pays')->nullOnDelete();
            } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        // Pas de rollback : on garde nullable pour ne pas casser les données existantes.
    }
};
