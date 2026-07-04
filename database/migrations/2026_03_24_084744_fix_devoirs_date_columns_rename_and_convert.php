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
        Schema::table('devoirs', function (Blueprint $table) {
            // Rename date_donnee to date_debut and convert to dateTime
            if (Schema::hasColumn('devoirs', 'date_donnee')) {
                $table->renameColumn('date_donnee', 'date_debut');
            }

            // Rename date_limite to date_fin and convert to dateTime
            if (Schema::hasColumn('devoirs', 'date_limite')) {
                $table->renameColumn('date_limite', 'date_fin');
            }
        });

        // Now convert the columns from date to dateTime
        // We need to use raw SQL because Laravel's change() doesn't work reliably for MySQL
        \DB::statement('ALTER TABLE devoirs MODIFY date_debut DATETIME NULL');
        \DB::statement('ALTER TABLE devoirs MODIFY date_fin DATETIME NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert back to date type
        \DB::statement('ALTER TABLE devoirs MODIFY date_debut DATE NULL');
        \DB::statement('ALTER TABLE devoirs MODIFY date_fin DATE NULL');

        Schema::table('devoirs', function (Blueprint $table) {
            // Rename back to original names
            if (Schema::hasColumn('devoirs', 'date_debut')) {
                $table->renameColumn('date_debut', 'date_donnee');
            }

            if (Schema::hasColumn('devoirs', 'date_fin')) {
                $table->renameColumn('date_fin', 'date_limite');
            }
        });
    }
};
