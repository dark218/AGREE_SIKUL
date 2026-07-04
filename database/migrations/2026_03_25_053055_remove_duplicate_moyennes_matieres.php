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
        // Supprimer les doublons en gardant le plus récent pour chaque (bulletin_id, matiere_id)
        DB::statement('
            DELETE FROM moyennes_matieres
            WHERE id NOT IN (
                SELECT id FROM (
                    SELECT MAX(id) as id
                    FROM moyennes_matieres
                    WHERE deleted_at IS NULL
                    GROUP BY bulletin_id, matiere_id
                ) AS subquery
            )
            AND deleted_at IS NULL
        ');

        // Ajouter une unique constraint pour empêcher les futurs doublons
        if (Schema::hasTable('moyennes_matieres')) if (Schema::hasTable('moyennes_matieres')) Schema::table('moyennes_matieres', function (Blueprint $table) {
            $table->unique(['bulletin_id', 'matiere_id'], 'unique_bulletin_matiere');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('moyennes_matieres')) if (Schema::hasTable('moyennes_matieres')) Schema::table('moyennes_matieres', function (Blueprint $table) {
            $table->dropUnique('unique_bulletin_matiere');
        });
    }
};
