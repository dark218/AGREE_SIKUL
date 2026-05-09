<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscriptions_transports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_transport_id');
            $table->unsignedBigInteger('apprenant_id');
            $table->unsignedBigInteger('annee_scolaire_id');
            $table->string('point_ramassage')->nullable();
            $table->enum('statut', ['active', 'suspendue', 'annulee'])->default('active');

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

            $table->foreign('service_transport_id')->references('id')->on('services_transports')->cascadeOnDelete();
            $table->foreign('apprenant_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('annee_scolaire_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscriptions_transports');
    }
};
