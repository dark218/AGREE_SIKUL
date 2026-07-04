<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // idempotence guard
        // Ajouter un INDEX composite pour optimiser les requêtes et détecter les doublons
        // Note: Une UNIQUE constraint échoue à cause des doublons existants dans la BD
        // Les doublons seront filtrés au niveau du contrôleur (voir MoyenneMatiereController)
        if (Schema::hasTable('bulletins')) if (Schema::hasTable('bulletins')) Schema::table('bulletins', function (Blueprint $table) {
            $table->index(['apprenant_id', 'classe_id', 'periode', 'annee_scolaire_id'], 'idx_bulletin_unique_combo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bulletins')) if (Schema::hasTable('bulletins')) Schema::table('bulletins', function (Blueprint $table) {
            $table->dropUnique('unique_apprenant_classe_periode');
        });
    }
};
