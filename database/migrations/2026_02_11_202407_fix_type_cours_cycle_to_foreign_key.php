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
        // Check if table exists and needs migration
        if (Schema::hasTable('type_cours')) {
            Schema::table('type_cours', function (Blueprint $table) {
                // Drop existing cycle column if it's a string
                if (Schema::hasColumn('type_cours', 'cycle')) {
                    $table->dropColumn('cycle');
                }
                // Add cycle_id as foreign key if it doesn't exist
                if (!Schema::hasColumn('type_cours', 'cycle_id')) {
                    $table->foreignId('cycle_id')->nullable()->after('libelle')->constrained('cycles_enseignement')->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('type_cours')) {
            Schema::table('type_cours', function (Blueprint $table) {
                if (Schema::hasColumn('type_cours', 'cycle_id')) {
                    $table->dropForeign(['cycle_id']);
                    $table->dropColumn('cycle_id');
                }
                if (!Schema::hasColumn('type_cours', 'cycle')) {
                    $table->string('cycle')->after('libelle');
                }
            });
        }
    }
};
