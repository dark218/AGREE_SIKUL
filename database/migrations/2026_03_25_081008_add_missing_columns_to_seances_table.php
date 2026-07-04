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
        if (Schema::hasTable('seances')) if (Schema::hasTable('seances')) Schema::table('seances', function (Blueprint $table) {
            // Ajouter les colonnes manquantes
            $table->string('titre')->nullable()->after('cours_id');
            $table->text('sujet')->nullable()->after('titre');
            $table->decimal('duree', 5, 2)->nullable()->after('heure_fin');
            $table->unsignedBigInteger('salle_id')->nullable()->after('duree');

            // Ajouter la foreign key pour salle_id
            $table->foreign('salle_id')->references('id')->on('salles')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('seances')) if (Schema::hasTable('seances')) Schema::table('seances', function (Blueprint $table) {
            // Supprimer la foreign key
            $table->dropForeign(['salle_id']);
            // Supprimer les colonnes
            $table->dropColumn(['titre', 'sujet', 'duree', 'salle_id']);
        });
    }
};
