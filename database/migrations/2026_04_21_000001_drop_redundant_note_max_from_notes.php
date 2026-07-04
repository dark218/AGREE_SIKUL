<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `notes.note_max` dupliquait `evaluations.note_sur` (accessible via
 * la relation `note.evaluation`). Les consommateurs doivent lire
 * `$note->evaluation?->note_sur` à la place.
 */
return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (Schema::hasTable('notes') && Schema::hasColumn('notes', 'note_max')) {
            Schema::table('notes', function (Blueprint $table) {
                $table->dropColumn('note_max');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notes') && !Schema::hasColumn('notes', 'note_max')) {
            Schema::table('notes', function (Blueprint $table) {
                $table->decimal('note_max', 5, 2)->nullable()->after('note');
            });
        }
    }
};
