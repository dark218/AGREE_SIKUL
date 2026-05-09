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
        // Supprimer la table si elle existe
        Schema::dropIfExists('wallet_mouvements');
        Schema::create('wallet_mouvements', function (Blueprint $table) {
            $table->id();
            /**
             * Wallet concerné
             */
            $table->foreignId('wallet_id')
                ->constrained('wallets')
                ->cascadeOnDelete()
                ->comment('Wallet concerné');

            /**
             * Type de mouvement
             */
            $table->enum('type_mouvement', [
                'credit',
                'debit',
                'blocage',
                'deblocage',
                'commission',
                'remboursement',
                'ajustement',
            ])->comment('Type de mouvement financier');

            /**
             * Montant (signé)
             */
            $table->bigInteger('montant_cents')
                ->comment('Montant du mouvement (+/-)');

            /**
             * Audit des soldes
             */
            $table->unsignedBigInteger('solde_avant_cents')
                ->comment('Solde avant mouvement');

            $table->unsignedBigInteger('solde_apres_cents')
                ->comment('Solde après mouvement');

            $table->unsignedBigInteger('emplacement_id')
                ->nullable()
                ->comment("Point de vente ou emplacement à l'origine du flux");

            $table->unsignedBigInteger('users_id')
                ->nullable()
                ->comment("Utilisateur à l'origine du flux");

            /**
             * Référence métier
             */
            $table->string('reference', 100)
                ->nullable()
                ->comment('Référence métier');

            /**
             * Source métier (transaction, remboursement, etc.)
             */
            $table->string('source_type', 50)
                ->nullable()
                ->comment('Type de source métier');

            $table->unsignedBigInteger('source_id')
                ->nullable()
                ->comment('ID de la source métier');

            /**
             * Métadonnées
             */
            $table->json('meta_json')->nullable();

            $table->timestamps();
            $table->softDeletes();
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

            /**
             * Index
             */
            $table->index('wallet_id');
            $table->index('reference');
            $table->index('emplacement_id');
            $table->index('users_id');
            $table->index(['source_type', 'source_id']);

            /**
             * Clés étrangères
             */
            $table->foreign('emplacement_id')
                ->references('id')
                ->on('points_vente')
                ->nullOnDelete();

            $table->foreign('users_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_mouvements');
    }
};
