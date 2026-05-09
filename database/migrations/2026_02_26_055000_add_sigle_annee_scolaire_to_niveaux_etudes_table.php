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
        if (Schema::hasTable('niveaux_etudes')) if (Schema::hasTable('niveaux_etudes')) Schema::table('niveaux_etudes', function (Blueprint $table) {
            $table->string('sigle')->nullable()->after('code')->comment('Abbreviation/Code for the education level');
            $table->unsignedBigInteger('annee_scolaire_id')->nullable()->after('pays_id');
            $table->foreign('annee_scolaire_id')->references('id')->on('annees_scolaires')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('niveaux_etudes')) if (Schema::hasTable('niveaux_etudes')) Schema::table('niveaux_etudes', function (Blueprint $table) {
            $table->dropForeign(['annee_scolaire_id']);
            $table->dropColumn('annee_scolaire_id');
            $table->dropColumn('sigle');
        });
    }
};
