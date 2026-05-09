<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cours', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('matiere_id');
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('enseignant_id');
            $table->unsignedBigInteger('annee_scolaire_id');
            $table->integer('volume_horaire')->default(0);
            $table->enum('statut', ['actif', 'inactif', 'suspendu'])->default('actif');

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

            $table->foreign('matiere_id')->references('id')->on('matieres')->cascadeOnDelete();
            $table->foreign('enseignant_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('annee_scolaire_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cours');
    }
};
