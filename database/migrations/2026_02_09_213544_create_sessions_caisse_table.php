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
        Schema::create('sessions_caisse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caisse_id')
                ->constrained('caisses')
                ->comment('Caisse concernée par la session');
            $table->foreignId('caissier_id')
                ->constrained('employes')
                ->comment('Employé qui a ouverte la caisse');
            $table->foreignId('terminal_id')
                ->nullable()
                ->constrained('terminaux')
                ->comment('Terminal utilisé (si applicable)');
            $table->timestamp('opened_at')
                ->nullable()
                ->comment('Date et heure d’ouverture de la session');

            $table->timestamp('closed_at')
                ->nullable()
                ->comment('Date et heure de fermeture');

            $table->unsignedBigInteger('fond_ouverture_cents')
                ->default(0)
                ->comment('Fond de caisse à l’ouverture');

            $table->unsignedBigInteger('total_encaisse_cents')
                ->default(0)
                ->comment('Total encaissé théorique');

            $table->unsignedBigInteger('total_reel_cents')
                ->nullable()
                ->comment('Total réel compté à la clôture');

            $table->integer('ecart_cents')
                ->nullable()
                ->comment('Écart (réel - théorique)');

            $table->enum('statut', ['attente','ouverte', 'fermee', 'annulee'])
                ->default('ouverte')
                ->comment('Statut de la session');
            $table->string('raison_annulation',255)->nullable();
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
        Schema::dropIfExists('sessions_caisse');
    }
};
