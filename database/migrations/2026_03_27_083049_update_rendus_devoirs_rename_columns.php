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
        if (Schema::hasTable('rendus_devoirs')) if (Schema::hasTable('rendus_devoirs')) Schema::table('rendus_devoirs', function (Blueprint $table) {
            // Rename old columns to match new schema
            $table->renameColumn('note', 'note_finale');
            $table->renameColumn('appreciation', 'notes_enseignant');
            $table->renameColumn('fichier_rendu_id', 'fichier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('rendus_devoirs')) if (Schema::hasTable('rendus_devoirs')) Schema::table('rendus_devoirs', function (Blueprint $table) {
            // Reverse the renames
            $table->renameColumn('note_finale', 'note');
            $table->renameColumn('notes_enseignant', 'appreciation');
            $table->renameColumn('fichier_id', 'fichier_rendu_id');
        });
    }
};
