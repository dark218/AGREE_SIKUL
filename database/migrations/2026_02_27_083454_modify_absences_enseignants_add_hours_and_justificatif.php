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
        Schema::table('absences_enseignants', function (Blueprint $table) {
            // Drop foreign key constraint on justificatif_id if it exists
            if (Schema::hasColumn('absences_enseignants', 'justificatif_id')) {
                try {
                    $table->dropForeign(['justificatif_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
                $table->dropColumn('justificatif_id');
            }

            // Modify date_debut and date_fin to datetime
            $table->dateTime('date_debut')->change();
            $table->dateTime('date_fin')->change();

            // Add nombre_heures field if it doesn't exist
            if (!Schema::hasColumn('absences_enseignants', 'nombre_heures')) {
                $table->decimal('nombre_heures', 8, 2)->nullable()->after('motif');
            }

            // Add justificatif_path if it doesn't exist
            if (!Schema::hasColumn('absences_enseignants', 'justificatif_path')) {
                $table->string('justificatif_path')->nullable()->after('nombre_heures');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absences_enseignants', function (Blueprint $table) {
            // Revert to original date format
            $table->date('date_debut')->change();
            $table->date('date_fin')->change();

            // Drop new columns
            $table->dropColumn('nombre_heures');
            $table->dropColumn('justificatif_path');

            // Revert justificatif
            $table->integer('justificatif_id')->nullable();
            $table->foreign('justificatif_id')->references('id')->on('justificatifs');
        });
    }
};
