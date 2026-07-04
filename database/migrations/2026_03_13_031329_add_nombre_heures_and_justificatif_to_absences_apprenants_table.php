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
            // Add nombre_heures column (for hours tracking)
            if (!Schema::hasColumn('absences_apprenants', 'nombre_heures')) {
                $table->decimal('nombre_heures', 8, 2)->nullable()->after('date_fin');
            }

            // Add justificatif_path column (for absence justification documents)
            if (!Schema::hasColumn('absences_apprenants', 'justificatif_path')) {
                $table->string('justificatif_path')->nullable()->after('nombre_heures');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absences_apprenants', function (Blueprint $table) {
            $table->dropColumnIfExists('nombre_heures');
            $table->dropColumnIfExists('justificatif_path');
        });
    }
};
