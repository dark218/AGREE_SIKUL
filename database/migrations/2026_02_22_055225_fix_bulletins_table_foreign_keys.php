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
        // Simply add the missing foreign keys
        // The statut column already exists
        // Just ensure the correct foreign key relationships are in place

        if (Schema::hasTable('bulletins')) if (Schema::hasTable('bulletins')) Schema::table('bulletins', function (Blueprint $table) {
            // Note: We need to check and add foreign keys for:
            // - apprenant_id -> apprenants.id
            // - classe_id -> classes.id
            // - annee_scolaire_id -> annees_scolaires.id

            // These might fail if they already exist, which is fine
            if (!$this->foreignKeyExists('bulletins', 'bulletins_classe_id_foreign')) {
                $table->foreign('classe_id')->references('id')->on('classes')->cascadeOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bulletins')) if (Schema::hasTable('bulletins')) Schema::table('bulletins', function (Blueprint $table) {
            // Only drop if it exists
            if ($this->foreignKeyExists('bulletins', 'bulletins_classe_id_foreign')) {
                $table->dropForeign(['classe_id']);
            }
        });
    }

    /**
     * Check if a foreign key exists
     */
    private function foreignKeyExists($table, $foreignKey)
    {
        $constraints = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = ? AND CONSTRAINT_NAME = ?", [$table, $foreignKey]);
        return count($constraints) > 0;
    }
};
