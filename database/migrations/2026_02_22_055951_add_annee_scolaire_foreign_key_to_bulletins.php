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
        if (Schema::hasTable('bulletins')) if (Schema::hasTable('bulletins')) Schema::table('bulletins', function (Blueprint $table) {
            // Drop existing foreign key if it exists (it might point to wrong table)
            if ($this->foreignKeyExists('bulletins', 'bulletins_annee_scolaire_id_foreign')) {
                $table->dropForeign(['annee_scolaire_id']);
            }

            // Add or re-add the correct foreign key for annee_scolaire_id
            $table->foreign('annee_scolaire_id')
                ->references('id')
                ->on('annees_scolaires')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bulletins')) if (Schema::hasTable('bulletins')) Schema::table('bulletins', function (Blueprint $table) {
            // Drop foreign key if it exists
            if ($this->foreignKeyExists('bulletins', 'bulletins_annee_scolaire_id_foreign')) {
                $table->dropForeign(['annee_scolaire_id']);
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
