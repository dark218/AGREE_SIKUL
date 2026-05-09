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
        if (Schema::hasTable('absences_apprenants')) if (Schema::hasTable('absences_apprenants')) Schema::table('absences_apprenants', function (Blueprint $table) {
            // Modify date_debut and date_fin from date to datetime
            $table->dateTime('date_debut')->change();
            $table->dateTime('date_fin')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('absences_apprenants')) if (Schema::hasTable('absences_apprenants')) Schema::table('absences_apprenants', function (Blueprint $table) {
            // Revert to date format
            $table->date('date_debut')->change();
            $table->date('date_fin')->change();
        });
    }
};
