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
        if (Schema::hasTable('absences_enseignants')) if (Schema::hasTable('absences_enseignants')) Schema::table('absences_enseignants', function (Blueprint $table) {
            // Change justificatif_path from string to json to support multiple files
            $table->json('justificatif_path')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('absences_enseignants')) if (Schema::hasTable('absences_enseignants')) Schema::table('absences_enseignants', function (Blueprint $table) {
            // Revert to string format
            $table->string('justificatif_path')->nullable()->change();
        });
    }
};
