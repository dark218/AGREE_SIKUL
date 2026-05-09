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
        if (Schema::hasTable('sections')) if (Schema::hasTable('sections')) Schema::table('sections', function (Blueprint $table) {
            $table->unsignedBigInteger('annee_scolaire_id')->nullable()->after('libelle');
            $table->foreign('annee_scolaire_id')->references('id')->on('annees_scolaires')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sections')) if (Schema::hasTable('sections')) Schema::table('sections', function (Blueprint $table) {
            $table->dropForeign(['annee_scolaire_id']);
            $table->dropColumn('annee_scolaire_id');
        });
    }
};
