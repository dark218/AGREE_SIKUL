<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // idempotence guard
        Schema::table('absences_apprenants', function (Blueprint $table) {
            // Drop foreign key constraint on justificatif_id
            if (Schema::hasColumn('absences_apprenants', 'justificatif_id')) {
                $table->dropForeign(['justificatif_id']);
                $table->dropColumn('justificatif_id');
            }

            // Modify nombre_heures from int to decimal if it exists
            if (Schema::hasColumn('absences_apprenants', 'nombre_heures')) {
                $table->decimal('nombre_heures', 8, 2)->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absences_apprenants', function (Blueprint $table) {
            // Revert to original date format
            $table->date('date_debut')->change();
            $table->date('date_fin')->change();

            // Rename back
            $table->renameColumn('nombre_heures', 'nombre_jours');

            // Revert justificatif
            $table->dropColumn('justificatif_path');
            $table->integer('justificatif_id')->nullable();
            $table->foreign('justificatif_id')->references('id')->on('justificatifs');
        });
    }
};
