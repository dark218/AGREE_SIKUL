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
            $table->enum('etat', ['actif', 'inactif'])->default('actif')->after('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('absences_apprenants')) if (Schema::hasTable('absences_apprenants')) Schema::table('absences_apprenants', function (Blueprint $table) {
            $table->dropColumn('etat');
        });
    }
};
