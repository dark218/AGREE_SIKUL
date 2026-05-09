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
        Schema::table('devoirs', function (Blueprint $table) {
            // Rename date_donnee to date_debut if exists
            if (Schema::hasColumn('devoirs', 'date_donnee')) {
                $table->renameColumn('date_donnee', 'date_debut');
            }

            // Rename date_limite to date_fin if exists
            if (Schema::hasColumn('devoirs', 'date_limite')) {
                $table->renameColumn('date_limite', 'date_fin');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
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
