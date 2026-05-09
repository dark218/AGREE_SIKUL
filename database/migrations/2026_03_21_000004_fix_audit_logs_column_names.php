<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if the old column exists and rename it
        if (Schema::hasColumn('exam_finance_audit_logs', 'planification_examen_poste_recette_id')) {
            // Rename the column in audit logs table
            Schema::table('exam_finance_audit_logs', function (Blueprint $table) {
                $table->renameColumn('planification_examen_poste_recette_id', 'exam_poste_recette_id');
            });
        }

        // Add the foreign key constraint
        Schema::table('exam_finance_audit_logs', function (Blueprint $table) {
            if (!$this->hasForeignKey('exam_finance_audit_logs', 'exam_poste_recette_id', 'exam_poste_recette')) {
                $table->foreign('exam_poste_recette_id')
                    ->references('id')
                    ->on('exam_poste_recette')
                    ->onDelete('cascade');
            }
        });
    }

    private function hasForeignKey($table, $column, $references): bool
    {
        $constraints = \DB::select(
            "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME = ?",
            [$table, $column, $references]
        );
        return !empty($constraints);
    }

    public function down(): void
    {
        Schema::table('exam_finance_audit_logs', function (Blueprint $table) {
            $table->renameColumn('exam_poste_recette_id', 'planification_examen_poste_recette_id');
        });
    }
};
