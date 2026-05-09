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
        Schema::create('comptes_bancaires_marchand', function (Blueprint $table) {
            $table->id();
            // Audit
            $table->timestamps();
            $table->softDeletes();
            // Marchand propriétaire du compte
            $table->foreignId('marchand_id')
                ->constrained('marchands')
                ->cascadeOnDelete()
                ->comment('Marchand propriétaire du compte bancaire');

            // Banque
            $table->foreignId('banque_id')
                ->constrained('banques')
                ->restrictOnDelete()
                ->comment('Banque du compte');

            // Informations du compte
            $table->string('nom_compte', 255)
                ->comment('Nom du titulaire du compte');

            $table->string('numero_compte', 50)
                ->comment('Numéro de compte bancaire');

            $table->string('iban', 255)
                ->nullable()
                ->comment('IBAN du compte');

            $table->string('bic_swift', 50)
                ->nullable()
                ->comment('Code BIC/SWIFT spécifique au compte');

            $table->boolean('is_principal')
                ->default(false)
                ->comment('Compte bancaire principal du marchand');

            $table->boolean('is_active')
                ->default(true)
                ->comment('Compte actif ou non');

            // Métadonnées
            $table->json('meta_json')
                ->nullable()
                ->comment('Informations supplémentaires (preuves, validations…)');
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
            // Contraintes
            $table->unique(
                ['marchand_id', 'numero_compte'],
                'unique_compte_par_marchand'
            );

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comptes_bancaires_marchand');
    }
};
