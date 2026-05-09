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
        if (Schema::hasTable('emplois_temps')) Schema::table('emplois_temps', function (Blueprint $table) {
            // Affectation scolaire étendue
            $table->foreignId('section_id')->nullable()->after('classe_id')->nullOnDelete();
            $table->foreignId('cycle_id')->nullable()->after('section_id')->nullOnDelete();
            $table->foreignId('ecole_id')->nullable()->after('cycle_id')->nullOnDelete();
            $table->foreignId('campus_id')->nullable()->after('ecole_id')->nullOnDelete();

            // Jour et matière
            $table->string('jour')->nullable()->after('campus_id');
            $table->foreignId('matiere_id')->nullable()->after('jour')->nullOnDelete();
            $table->foreignId('enseignant_id')->nullable()->after('matiere_id')->nullOnDelete();

            // Durée (en minutes)
            $table->integer('duree')->nullable()->after('enseignant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback not needed
    }
};
