<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloc "livres" d'une liste de manuels et fournitures.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('listes_manuels_livres')) {
            return;
        }

        Schema::create('listes_manuels_livres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liste_manuels_id')->constrained('listes_manuels')->cascadeOnDelete();
            $table->string('titre', 255);
            $table->string('sujet', 255)->nullable();
            $table->string('langue', 100)->nullable();
            $table->string('auteurs', 500)->nullable();
            $table->string('editeurs', 255)->nullable();
            $table->integer('annee_edition')->nullable();
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('liste_manuels_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listes_manuels_livres');
    }
};
