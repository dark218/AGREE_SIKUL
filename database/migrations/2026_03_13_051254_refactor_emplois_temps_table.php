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
        Schema::table('emplois_temps', function (Blueprint $table) {
            // Add correct columns for EmploiDuTemps if they don't exist
            if (!Schema::hasColumn('emplois_temps', 'jour_semaine')) {
                $table->string('jour_semaine')->nullable()->after('classe_id');
            }
            if (!Schema::hasColumn('emplois_temps', 'heure_debut')) {
                $table->time('heure_debut')->nullable()->after('jour_semaine');
            }
            if (!Schema::hasColumn('emplois_temps', 'heure_fin')) {
                $table->time('heure_fin')->nullable()->after('heure_debut');
            }
            if (!Schema::hasColumn('emplois_temps', 'salle')) {
                $table->string('salle')->nullable()->after('heure_fin');
            }
            if (!Schema::hasColumn('emplois_temps', 'week_start_date')) {
                $table->date('week_start_date')->nullable()->after('salle');
            }
            if (!Schema::hasColumn('emplois_temps', 'week_end_date')) {
                $table->date('week_end_date')->nullable()->after('week_start_date');
            }
            if (!Schema::hasColumn('emplois_temps', 'week_name')) {
                $table->string('week_name')->nullable()->after('week_end_date');
            }
            if (!Schema::hasColumn('emplois_temps', 'week_number')) {
                $table->integer('week_number')->nullable()->after('week_name');
            }
            if (!Schema::hasColumn('emplois_temps', 'year')) {
                $table->integer('year')->nullable()->after('week_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emplois_temps', function (Blueprint $table) {
            // Revert is complex - just restore the key columns if needed
        });
    }
};
