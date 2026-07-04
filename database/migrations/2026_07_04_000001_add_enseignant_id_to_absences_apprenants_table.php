<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('absences_apprenants') && !Schema::hasColumn('absences_apprenants', 'enseignant_id')) {
            Schema::table('absences_apprenants', function (Blueprint $table) {
                $table->foreignId('enseignant_id')
                    ->nullable()
                    ->after('classe_id')
                    ->constrained('enseignants')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('absences_apprenants') && Schema::hasColumn('absences_apprenants', 'enseignant_id')) {
            Schema::table('absences_apprenants', function (Blueprint $table) {
                $table->dropConstrainedForeignId('enseignant_id');
            });
        }
    }
};
