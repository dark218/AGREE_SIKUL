<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frais', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('apprenant_id');
            $table->unsignedBigInteger('annee_scolaire_id');
            $table->unsignedBigInteger('type_frais_id');
            $table->unsignedBigInteger('montant_cents')->default(0);
            $table->unsignedBigInteger('montant_paye_cents')->default(0);
            $table->enum('statut', ['non_paye', 'partiellement_paye', 'paye'])->default('non_paye');

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

            $table->foreign('apprenant_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('annee_scolaire_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('type_frais_id')->references('id')->on('types_frais')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frais');
    }
};
