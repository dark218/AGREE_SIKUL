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
        Schema::hasTable('affectations_agents') ? null : Schema::create('affectations_agents', function (Blueprint $table) {
            /**
             * Identifiant technique
             */
            $table->id();

            /**
             * Agent concerné (utilisateur ayant un rôle d’agent terrain)
             */
            $table->foreignId('agent_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Agent terrain affecté à une zone');

            /**
             * Zone géographique ou commerciale
             */
            $table->foreignId('zone_id')
                ->constrained('zones')
                ->cascadeOnDelete()
                ->comment('Zone géographique ou commerciale assignée à l’agent');

            /**
             * Date effective de début d’affectation
             */
            $table->timestamp('date_affectation')
                ->comment('Date de début effectif de l’affectation');

            /**
             * Date de fin d’affectation
             * NULL = affectation toujours active
             */
            $table->timestamp('date_desaffectation')
                ->nullable()
                ->comment('Date de fin de l’affectation (NULL si active)');

            /**
             * Statut rapide de l’affectation
             */
            $table->boolean('actif')
                ->default(true)
                ->comment('Indique si l’affectation est actuellement active');

            /**
             * Rôle métier de l’agent dans la zone
             * Exemples :
             * - agent_commercial
             * - superviseur_zone
             * - controleur
             * - support_terrain
             */
            $table->enum('role_affectation', [
                'agent_commercial',
                'superviseur_zone',
                'controleur',
                'support_terrain',
            ])->comment('Rôle métier de l’agent dans la zone');
            /**
             * Métadonnées et audit standard
             */
            $table->timestamps();
            $table->softDeletes();

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
             * Contraintes métier
             *
             * Un agent ne peut avoir qu’une seule affectation
             * active par zone à un instant donné.
             */
            $table->unique(
                ['agent_id', 'zone_id'],
                'unique_affectation_agent_zone'
            );

            /**
             * Index utiles pour le filtrage
             */
            $table->index('agent_id');
            $table->index('zone_id');
            $table->index('actif');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affectations_agents');
    }
};
