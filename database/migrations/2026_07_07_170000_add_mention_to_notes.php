<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute la colonne `mention` aux notes (Onglet Résultat de la spec :
 * Note / Mention / Observation). L'observation est stockée dans `appreciation`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notes') && !Schema::hasColumn('notes', 'mention')) {
            Schema::table('notes', function (Blueprint $table) {
                $table->string('mention', 50)->nullable()->after('note_sur');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notes') && Schema::hasColumn('notes', 'mention')) {
            Schema::table('notes', fn (Blueprint $table) => $table->dropColumn('mention'));
        }
    }
};
