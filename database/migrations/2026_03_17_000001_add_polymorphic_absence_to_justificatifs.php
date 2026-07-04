<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (Schema::hasTable('justificatifs_absences')) if (Schema::hasTable('justificatifs_absences')) Schema::table('justificatifs_absences', function (Blueprint $table) {
            // Add polymorphic columns for supporting both AbsenceApprenant and AbsenceEnseignant
            $table->string('absence_type')->default('apprenant')->after('absence_id');

            // Drop the old foreign key constraint to absences_apprenants
            $table->dropForeign('justificatifs_absences_absence_id_foreign');

            // Make absence_id unsigned and nullable (for polymorphic)
            $table->unsignedBigInteger('absence_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('justificatifs_absences')) if (Schema::hasTable('justificatifs_absences')) Schema::table('justificatifs_absences', function (Blueprint $table) {
            // Restore original state
            $table->dropColumn('absence_type');
            $table->foreign('absence_id')->references('id')->on('absences_apprenants')->cascadeOnDelete();
            $table->unsignedBigInteger('absence_id')->nullable(false)->change();
        });
    }
};
