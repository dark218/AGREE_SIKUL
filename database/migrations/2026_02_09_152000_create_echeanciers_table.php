<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('echeanciers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('frais_id');
            $table->integer('numero_echeance');
            $table->unsignedBigInteger('montant_cents')->default(0);
            $table->date('date_echeance');
            $table->date('date_paiement')->nullable();
            $table->enum('statut', ['en_attente', 'paye', 'retard'])->default('en_attente');

            $table->timestamps();
            $table->softDeletes();

            $table->string('checksum')->nullable();
            $table->string('external_id')->nullable();
            $table->string('source_system')->nullable();
            $table->string('creation_hostname')->nullable();
            $table->string('modification_hostname')->nullable();
            $table->string('deletion_hostname')->nullable();
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->string('deletion_username')->nullable();

            $table->index(['external_id']);
            $table->index(['source_system']);

            $table->foreign('frais_id')->references('id')->on('frais')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('echeanciers');
    }
};
