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
        Schema::hasTable('transferts_stock') ? null : Schema::create('transferts_stock', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('emplacement_source_id')
                ->constrained('points_vente')
                ->comment('Emplacement source du stock');

            $table->foreignId('emplacement_destination_id')
                ->constrained('points_vente')
                ->comment('Emplacement destination du stock');
            $table->string('reference', 100)
                ->unique()
                ->comment('Référence unique du transfert');

            $table->enum('statut', [
                'en_cours',
                'partiel',
                'confirme',
                'annule'
            ])->default('en_cours')
                ->comment('Statut global du transfert');
            $table->date('date_demande')
                ->comment('Date de création de la demande');

            $table->date('date_confirmation')
                ->nullable()
                ->comment('Date de confirmation effective du transfert');
            $table->foreignId('demande_par')
                ->nullable()
                ->constrained('employes')
                ->comment('Employé ayant initié la demande');

            $table->foreignId('confirme_par')
                ->nullable()
                ->constrained('employes')
                ->comment('Employé ayant confirmé le transfert');

            $table->text('commentaire')
                ->nullable()
                ->comment('Commentaire global ou motif');

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

            $table->index(['emplacement_source_id']);
            $table->index(['emplacement_destination_id']);
            $table->index(['statut']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferts_stock');
    }
};
