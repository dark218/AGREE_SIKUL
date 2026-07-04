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
        Schema::hasTable('inventaires') ? null : Schema::create('inventaires', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('emplacement_id')
                ->constrained('points_vente')
                ->comment('Emplacement concerné par l’inventaire');
            // Date de l’inventaire (date métier)
            $table->date('date_inventaire')
                ->comment('Date à laquelle l’inventaire est réalisé');

            // Statut global
            $table->enum('statut', [
                'brouillon',
                'valide',
                'annule',
            ])->default('brouillon')
                ->comment('Statut de l’inventaire');

            // Utilisateur ayant créé l’inventaire
            $table->foreignId('cree_par')
                ->nullable()
                ->constrained('employes')
                ->nullOnDelete()
                ->comment('Employe ayant créé l’inventaire');

            // Utilisateur ayant validé l’inventaire
            $table->foreignId('valide_par')
                ->nullable()
                ->constrained('employes')
                ->nullOnDelete()
                ->comment('Employe ayant validé l’inventaire');

            $table->timestamp('date_validation')
                ->nullable()
                ->comment('Date de validation de l’inventaire');

            $table->text('commentaire')
                ->nullable()
                ->comment('Commentaire global de l’inventaire');

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
            $table->unique(['emplacement_id', 'date_inventaire']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventaires_emplacement');
    }
};
