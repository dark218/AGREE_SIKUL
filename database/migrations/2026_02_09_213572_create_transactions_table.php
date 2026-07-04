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
        Schema::hasTable('transactions') ? null : Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            /**
             * Identité
             */
            $table->uuid('uuid')->unique()
                ->comment('Identifiant global transaction');

            /**
             * Acteurs
             */
            $table->foreignId('payer_id')
                ->constrained('users')
                ->comment('Utilisateur payeur');

            $table->foreignId('marchand_id')
                ->nullable()
                ->constrained('marchands')
                ->comment('Marchand bénéficiaire');

            $table->foreignId('points_vente_id')
                ->nullable()
                ->constrained('points_vente')
                ->comment('Point de vente concerné');

            /**
             * Lien métier (vente, remboursement, lien, abonnement…)
             */
            $table->string('source_type', 50)
                ->nullable()
                ->comment('vente_pos | remboursement | lien | abonnement');

            $table->unsignedBigInteger('source_id')
                ->nullable()
                ->comment('ID ressource métier');
            /**
             * Fournisseur de paiement (PSP)
             */
            $table->foreignId('fournisseur_paiement_id')
                ->nullable()
                ->constrained('fournisseurs_paiement')
                ->comment('PSP utilisé pour la transaction');
            /**
             * Montant & devise
             */
            $table->unsignedBigInteger('montant_cents')
                ->comment('Montant total');
            $table->string('devise', 3)
                ->comment('Devise utilisée');
            /**
             * Statut transactionnel
             */
            $table->enum('statut', [
                'initiee',
                'en_attente',
                'reussie',
                'echouee',
                'annulee',
                'remboursee',
            ])->default('initiee');

            /**
             * Références PSP
             */
            $table->string('reference_externe', 255)->nullable();
            $table->json('meta_json')->nullable();

            /**
             * Dates métier
             */
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            /**
             * Audit
             */
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

            $table->timestamps();
            $table->softDeletes();

            /**
             * Index
             */
            $table->index(['source_type', 'source_id']);
            $table->index(['statut']);
            $table->index(['payer_id']);
            $table->index(['marchand_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
