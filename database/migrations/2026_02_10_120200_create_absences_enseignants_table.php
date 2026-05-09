<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('absences_enseignants')) Schema::create('absences_enseignants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enseignant_id')->constrained('enseignants')->cascadeOnDelete();
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('motif', 255)->nullable();
            $table->foreignId('justificatif_id')->nullable()->constrained('fichier')->onDelete('cascade');
            $table->enum('statut', ['en_attente', 'validee', 'rejetee'])->default('en_attente');

            // Audit
            $table->string('checksum')->nullable();
            $table->string('external_id')->nullable();
            $table->string('source_system')->default('agree_sikul');
            $table->string('creation_hostname')->nullable();
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->string('deletion_username')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index('enseignant_id');
            $table->index('statut');
            $table->index(['external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absences_enseignants');
    }
};
