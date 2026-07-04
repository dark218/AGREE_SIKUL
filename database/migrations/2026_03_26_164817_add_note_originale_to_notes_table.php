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
        if (Schema::hasTable('notes')) if (Schema::hasTable('notes')) Schema::table('notes', function (Blueprint $table) {
            // Colonne pour stocker la note originale (avant normalisation à /20)
            // Exemple: 8 (pour une interrogation sur 10)
            $table->decimal('note_originale', 5, 2)->nullable()->after('note');

            // Colonne pour stocker la valeur "sur" de l'évaluation
            // Exemple: 10 (pour une interrogation sur 10)
            $table->decimal('note_sur', 5, 2)->nullable()->after('note_originale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('notes')) if (Schema::hasTable('notes')) Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn(['note_originale', 'note_sur']);
        });
    }
};
