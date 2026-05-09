<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absences_apprenants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('apprenant_id');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->integer('nombre_jours')->default(0);
            $table->text('motif')->nullable();
            $table->unsignedBigInteger('justificatif_id')->nullable();
            $table->enum('statut', ['en_attente', 'justifiee', 'non_justifiee', 'partiellement_justifiee'])->default('en_attente');

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
            $table->foreign('justificatif_id')->references('id')->on('fichier')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absences_apprenants');
    }
};
