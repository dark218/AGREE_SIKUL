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
            // Add missing columns for EmploiTemps CRUD
            if (!Schema::hasColumn('emplois_temps', 'titre')) {
                $table->string('titre', 255)->nullable()->after('annee_scolaire_id');
            }
            if (!Schema::hasColumn('emplois_temps', 'libelle')) {
                $table->string('libelle', 255)->nullable()->after('titre');
            }
            if (!Schema::hasColumn('emplois_temps', 'date_debut')) {
                $table->date('date_debut')->nullable()->after('libelle');
            }
            if (!Schema::hasColumn('emplois_temps', 'date_fin')) {
                $table->date('date_fin')->nullable()->after('date_debut');
            }
            if (!Schema::hasColumn('emplois_temps', 'fichier_url')) {
                $table->string('fichier_url', 500)->nullable()->after('date_fin');
            }
            // Update statut enum to match form options
            if (Schema::hasColumn('emplois_temps', 'statut')) {
                $table->enum('statut', ['brouillon', 'valide', 'publie', 'archive'])->default('brouillon')->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emplois_temps', function (Blueprint $table) {
            if (Schema::hasColumn('emplois_temps', 'titre')) {
                $table->dropColumn('titre');
            }
            if (Schema::hasColumn('emplois_temps', 'libelle')) {
                $table->dropColumn('libelle');
            }
            if (Schema::hasColumn('emplois_temps', 'date_debut')) {
                $table->dropColumn('date_debut');
            }
            if (Schema::hasColumn('emplois_temps', 'date_fin')) {
                $table->dropColumn('date_fin');
            }
            if (Schema::hasColumn('emplois_temps', 'fichier_url')) {
                $table->dropColumn('fichier_url');
            }
        });
    }
};
