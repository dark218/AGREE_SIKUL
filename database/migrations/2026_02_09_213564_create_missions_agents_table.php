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
        Schema::create('missions_agents', function (Blueprint $table) {
            /**
             * =========================
             * Identité
             * =========================
             */
            $table->id();

            /**
             * =========================
             * Agent concerné
             * =========================
             * Agent terrain à qui la mission est confiée.
             * Référence la table utilisateurs (type agent).
             */
            $table->foreignId('agent_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Agent terrain en charge de la mission');

            /**
             * =========================
             * Zone concernée (optionnelle)
             * =========================
             * Peut être null pour :
             * - missions transverses
             * - missions nationales
             * - missions sans rattachement géographique
             */
            $table->foreignId('zone_id')
                ->nullable()
                ->constrained('zones')
                ->nullOnDelete()
                ->comment('Zone géographique concernée par la mission');

            /**
             * =========================
             * Informations mission
             * =========================
             */
            $table->string('titre', 255)
                ->comment('Intitulé court de la mission');

            $table->text('description')
                ->nullable()
                ->comment('Description détaillée et instructions opérationnelles');

            /**
             * =========================
             * Période de la mission
             * =========================
             */
            $table->date('date_debut')
                ->comment('Date de début de la mission');

            $table->date('date_fin')
                ->nullable()
                ->comment('Date de fin prévue de la mission');

            /**
             * =========================
             * Statut de la mission
             * =========================
             */
            $table->enum('statut', [
                'assigned',     // Mission assignée mais non démarrée
                'en_cours',     // Mission en cours d’exécution
                'terminee',     // Mission terminée
                'en_retard',    // Mission non terminée à la date prévue
                'annulee',      // Mission annulée
            ])->default('assigned')
                ->comment('Statut opérationnel de la mission');

            /**
             * =========================
             * Objectifs et indicateurs
             * =========================
             * JSON libre permettant de stocker :
             * - objectifs chiffrés
             * - KPI attendus
             * - critères de réussite
             */
            $table->json('objectif_json')
                ->nullable()
                ->comment('Objectifs et indicateurs de performance de la mission');

            /**
             * Métadonnées
             */
            $table->json('meta_json')->nullable();
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
             * =========================
             * Traçabilité
             * =========================
             */
            $table->timestamps();
            $table->softDeletes();

            /**
             * =========================
             * Index utiles
             * =========================
             */
            $table->index(['agent_id', 'statut']);
            $table->index(['zone_id', 'statut']);
            $table->index(['date_debut', 'date_fin']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('missions_agents');
    }
};
