<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('moyennes_matieres')) if (Schema::hasTable('moyennes_matieres')) Schema::table('moyennes_matieres', function (Blueprint $table) {
            // Ajouter apprenant_id après matiere_id
            $table->unsignedBigInteger('apprenant_id')->nullable()->after('matiere_id');

            // Rendre bulletin_id nullable (créé d'abord les moyennes, puis le bulletin)
            $table->unsignedBigInteger('bulletin_id')->nullable()->change();

            // Index pour recherche rapide par apprenant
            $table->index(['apprenant_id']);
            $table->index(['apprenant_id', 'matiere_id']);

            // Foreign key
            $table->foreign('apprenant_id')->references('id')->on('apprenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('moyennes_matieres')) if (Schema::hasTable('moyennes_matieres')) Schema::table('moyennes_matieres', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['apprenant_id']);
            $table->dropIndexIfExists(['apprenant_id']);
            $table->dropIndexIfExists(['apprenant_id', 'matiere_id']);
            $table->dropColumn('apprenant_id');

            // Restaurer bulletin_id comme required
            $table->unsignedBigInteger('bulletin_id')->nullable(false)->change();
        });
    }
};
