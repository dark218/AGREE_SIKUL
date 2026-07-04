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
        if (Schema::hasTable('periodes_colaires')) if (Schema::hasTable('periodes_colaires')) Schema::table('periodes_colaires', function (Blueprint $table) {
            $table->unsignedBigInteger('annee_scolaire_id')->nullable()->after('id');
            $table->enum('type_periode', ['trimestre', 'semestre', 'quadrimestre', 'annuel'])->nullable()->after('annee_scolaire_id');
            $table->integer('numero_ordre')->nullable()->after('libelle');
            $table->unsignedBigInteger('ecole_id')->nullable()->after('type_periode');
            $table->date('date_debut')->nullable()->after('numero_ordre');
            $table->date('date_fin')->nullable()->after('date_debut');
            $table->boolean('est_periode_evaluation')->default(false)->after('date_fin');

            // Add foreign key constraints
            $table->foreign('annee_scolaire_id')->references('id')->on('annees_scolaires')->onDelete('cascade');
            $table->foreign('ecole_id')->references('id')->on('ecoles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('periodes_colaires')) if (Schema::hasTable('periodes_colaires')) Schema::table('periodes_colaires', function (Blueprint $table) {
            $table->dropForeign(['annee_scolaire_id']);
            $table->dropForeign(['ecole_id']);
            $table->dropColumn(['annee_scolaire_id', 'type_periode', 'numero_ordre', 'ecole_id', 'date_debut', 'date_fin', 'est_periode_evaluation']);
        });
    }
};
