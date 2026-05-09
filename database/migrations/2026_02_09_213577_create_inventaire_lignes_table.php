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
        Schema::create('inventaire_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventaire_id')
                ->constrained('inventaires')
                ->cascadeOnDelete()
                ->comment('Inventaire parent');

            // Article inventorié
            $table->foreignId('article_id')
                ->constrained('articles')
                ->comment('Article inventorié');

            // Quantité au moment de l’inventaire (système)
            $table->integer('quantite_systeme')
                ->comment('Quantité théorique dans le système');

            // Quantité réellement comptée
            $table->integer('quantite_comptabilisee')
                ->comment('Quantité réellement comptée');

            // Écart (calculable mais stocké pour audit)
            $table->integer('ecart')
                ->comment('Différence entre système et comptabilisé');

            $table->text('commentaire')
                ->nullable()
                ->comment('Commentaire ligne inventaire');
            $table->timestamps();
            $table->string('checksum')->nullable();
            $table->string('external_id')->nullable();
            $table->string('source_system')->default('smilpay');
            $table->string('creation_hostname')->nullable();
            $table->string('modification_hostname')->nullable();
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->string('deletion_username')->nullable();
            $table->index(['external_id']);
            $table->index(['source_system']);
            $table->softDeletes();

            // Un article ne peut apparaître qu’une seule fois par inventaire
            $table->unique(['inventaire_id', 'article_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventaire_lignes');
    }
};
