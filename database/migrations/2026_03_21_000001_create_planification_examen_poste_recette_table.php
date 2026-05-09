<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('exam_poste_recette')) Schema::create('exam_poste_recette', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('exam_id')
                ->references('id')
                ->on('planifications_examens')
                ->onDelete('cascade');

            $table->foreignId('recette_id')
                ->references('id')
                ->on('postes_recettes')
                ->onDelete('cascade');

            // Financing Details
            $table->decimal('montant_finance', 15, 2)
                ->nullable()
                ->comment('Montant alloué au financement');

            $table->decimal('pourcentage_couverture', 5, 2)
                ->nullable()
                ->comment('Pourcentage du coût total couvert');

            // Dates
            $table->date('date_facturation')
                ->nullable()
                ->comment('Date de facturation');

            $table->date('date_limite_paiement')
                ->nullable()
                ->comment('Date limite de paiement');

            // Status
            $table->enum('etat_financement', ['actif', 'en-attente', 'clôturé'])
                ->default('en-attente')
                ->index()
                ->comment('État du financement');

            // Metadata
            $table->longText('notes')
                ->nullable()
                ->comment('Notes et observations');

            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['exam_id', 'recette_id']);
            $table->index(['etat_financement', 'date_facturation']);
            $table->index(['recette_id', 'etat_financement']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_poste_recette');
    }
};
