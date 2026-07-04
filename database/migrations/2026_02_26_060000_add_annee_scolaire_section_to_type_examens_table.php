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
        if (Schema::hasTable('type_examens')) if (Schema::hasTable('type_examens')) Schema::table('type_examens', function (Blueprint $table) {
            $table->unsignedBigInteger('annee_scolaire_id')->nullable()->after('pays_id');
            $table->foreign('annee_scolaire_id')->references('id')->on('annees_scolaires')->onDelete('set null');
            $table->unsignedBigInteger('section_id')->nullable()->after('annee_scolaire_id');
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('type_examens')) if (Schema::hasTable('type_examens')) Schema::table('type_examens', function (Blueprint $table) {
            $table->dropForeign(['section_id']);
            $table->dropColumn('section_id');
            $table->dropForeign(['annee_scolaire_id']);
            $table->dropColumn('annee_scolaire_id');
        });
    }
};
