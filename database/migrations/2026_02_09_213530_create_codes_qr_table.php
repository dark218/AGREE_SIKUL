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
        Schema::create('codes_qr', function (Blueprint $table) {
            $table->id();
            $table->foreignId('points_vente_id')
                ->constrained('points_vente')
                ->comment("Emplacement (boutique, caisse, point de vente) lié au QR");
            $table->string('devise', 10)
                ->nullable()
                ->comment("Devise du montant (XOF, EUR, USD)");
            $table->enum('type', ['statique', 'dynamique'])
                ->comment("statique = permanent / dynamique = transactionnel");
            $table->uuid('uuid')
                ->unique()
                ->comment("Identifiant public du QR, exposé dans l'URL ou le QR");
            $table->json('payload_json')
                ->comment("Données embarquées dans le QR (alias, montant, transaction, etc.)");
            $table->unsignedBigInteger('montant_cents')
                ->nullable()
                ->comment("Montant imposé si QR dynamique (en cents)");
            $table->timestamp('expire_at')
                ->nullable()
                ->comment("Date d'expiration du QR dynamique");
            $table->integer("actif")->default(1)->comment("statut 1 = actif, 0 = inactif");

            $table->boolean('used')
                ->default(false)
                ->comment("Indique si le QR dynamique a déjà été utilisé");
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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('codes_qr');
    }
};
