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
        Schema::create('caisses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('points_vente_id')
                ->constrained('points_vente')
                ->comment('Emplacement (enfant) représentant la caisse');
            $table->string('code', 50)
                ->unique()
                ->comment('Code unique de la caisse (ex: CAISSE-001)');

            $table->string('nom', 255)
                ->comment('Nom de la caisse (ex: Caisse principale)');
            $table->enum('type', ['physique', 'mobile', 'virtuelle'])
                ->default('physique')
                ->comment('Type de caisse');

            $table->enum('statut', ['ouverte', 'fermee', 'bloquee'])
                ->default('fermee')
                ->comment('Statut actuel de la caisse');
            $table->json('parametres_json')
                ->nullable()
                ->comment('Configuration POS spécifique à la caisse');
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
        Schema::dropIfExists('caisses');
    }
};
