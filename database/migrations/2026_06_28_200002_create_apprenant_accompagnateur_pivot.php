<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot Accompagnateur ↔ Apprenants.
 *
 * Nouveau lien : jusqu'ici `accompagnateurs` n'était rattaché qu'à
 * école/campus/institution, sans mention des apprenants qu'il transporte
 * ou surveille. Ce pivot corrige ça et permet une saisie N apprenants
 * dans le formulaire d'accompagnateur.
 *
 * Pas de data migration : l'ancien modèle n'avait aucune donnée à
 * recopier (colonne absente).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('apprenant_accompagnateur')) {
            Schema::create('apprenant_accompagnateur', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('accompagnateur_id');
                $table->unsignedBigInteger('apprenant_id');
                $table->boolean('est_principal')->default(false);
                $table->timestamps();

                $table->unique(['accompagnateur_id', 'apprenant_id'], 'apprenant_accompagnateur_unique');
                $table->foreign('accompagnateur_id')->references('id')->on('accompagnateurs')->onDelete('cascade');
                $table->foreign('apprenant_id')->references('id')->on('apprenants')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('apprenant_accompagnateur');
    }
};
