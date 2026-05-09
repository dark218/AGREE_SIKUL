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
            $table->unsignedBigInteger('matiere_id')->nullable()->after('apprenant_id');
            $table->foreign('matiere_id')->references('id')->on('matieres')->onDelete('set null');

            $table->unsignedBigInteger('classe_id')->nullable()->after('matiere_id');
            $table->foreign('classe_id')->references('id')->on('classes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('absences_apprenants')) if (Schema::hasTable('absences_apprenants')) Schema::table('absences_apprenants', function (Blueprint $table) {
            $table->dropForeign(['classe_id']);
            $table->dropColumn('classe_id');
            $table->dropForeign(['matiere_id']);
            $table->dropColumn('matiere_id');
        });
    }
};
