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
        // idempotence guard
        if (Schema::hasTable('emprunts')) if (Schema::hasTable('emprunts')) Schema::table('emprunts', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['emprunteur_id']);

            // Rename column
            $table->renameColumn('emprunteur_id', 'apprenant_id');

            // Add new foreign key constraint
            $table->foreign('apprenant_id')->references('id')->on('apprenants')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('emprunts')) if (Schema::hasTable('emprunts')) Schema::table('emprunts', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['apprenant_id']);

            // Rename column back
            $table->renameColumn('apprenant_id', 'emprunteur_id');

            // Add old foreign key constraint
            $table->foreign('emprunteur_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
