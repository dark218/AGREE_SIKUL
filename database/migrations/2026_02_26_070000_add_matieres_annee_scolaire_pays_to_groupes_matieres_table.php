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
        if (Schema::hasTable('groupes_matieres')) if (Schema::hasTable('groupes_matieres')) Schema::table('groupes_matieres', function (Blueprint $table) {
            // Add remaining subjects (4-10)
            $table->unsignedBigInteger('matiere4_id')->nullable()->after('matiere3_id');
            $table->foreign('matiere4_id')->references('id')->on('matieres_unites')->onDelete('set null');

            $table->unsignedBigInteger('matiere5_id')->nullable()->after('matiere4_id');
            $table->foreign('matiere5_id')->references('id')->on('matieres_unites')->onDelete('set null');

            $table->unsignedBigInteger('matiere6_id')->nullable()->after('matiere5_id');
            $table->foreign('matiere6_id')->references('id')->on('matieres_unites')->onDelete('set null');

            $table->unsignedBigInteger('matiere7_id')->nullable()->after('matiere6_id');
            $table->foreign('matiere7_id')->references('id')->on('matieres_unites')->onDelete('set null');

            $table->unsignedBigInteger('matiere8_id')->nullable()->after('matiere7_id');
            $table->foreign('matiere8_id')->references('id')->on('matieres_unites')->onDelete('set null');

            $table->unsignedBigInteger('matiere9_id')->nullable()->after('matiere8_id');
            $table->foreign('matiere9_id')->references('id')->on('matieres_unites')->onDelete('set null');

            $table->unsignedBigInteger('matiere10_id')->nullable()->after('matiere9_id');
            $table->foreign('matiere10_id')->references('id')->on('matieres_unites')->onDelete('set null');

            // Add academic year and country
            $table->unsignedBigInteger('annee_scolaire_id')->nullable()->after('matiere10_id');
            $table->foreign('annee_scolaire_id')->references('id')->on('annees_scolaires')->onDelete('set null');

            $table->unsignedBigInteger('pays_id')->nullable()->after('annee_scolaire_id');
            $table->foreign('pays_id')->references('id')->on('pays')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('groupes_matieres')) if (Schema::hasTable('groupes_matieres')) Schema::table('groupes_matieres', function (Blueprint $table) {
            $table->dropForeign(['pays_id']);
            $table->dropColumn('pays_id');
            $table->dropForeign(['annee_scolaire_id']);
            $table->dropColumn('annee_scolaire_id');

            $table->dropForeign(['matiere10_id']);
            $table->dropColumn('matiere10_id');
            $table->dropForeign(['matiere9_id']);
            $table->dropColumn('matiere9_id');
            $table->dropForeign(['matiere8_id']);
            $table->dropColumn('matiere8_id');
            $table->dropForeign(['matiere7_id']);
            $table->dropColumn('matiere7_id');
            $table->dropForeign(['matiere6_id']);
            $table->dropColumn('matiere6_id');
            $table->dropForeign(['matiere5_id']);
            $table->dropColumn('matiere5_id');
            $table->dropForeign(['matiere4_id']);
            $table->dropColumn('matiere4_id');
        });
    }
};
