<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloc "cahiers" d'une liste de manuels et fournitures.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('listes_manuels_cahiers')) {
            return;
        }

        Schema::create('listes_manuels_cahiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liste_manuels_id')->constrained('listes_manuels')->cascadeOnDelete();
            $table->string('utilite', 255)->nullable();
            $table->string('type_cahier', 255)->nullable();
            $table->unsignedInteger('nombre_pages')->nullable();
            $table->unsignedInteger('quantite')->default(1);
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('liste_manuels_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listes_manuels_cahiers');
    }
};
