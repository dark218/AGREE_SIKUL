<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inscriptions')) Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apprenant_id')->constrained('apprenants')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->date('date_inscription');
            $table->string('numero_inscription', 100)->unique();
            $table->enum('type_inscription', ['nouveau', 'redoublement', 'transfert', 'reprise'])->default('nouveau');
            $table->enum('statut', ['en_attente', 'validee', 'rejetee', 'suspendue'])->default('en_attente');

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
            $table->unique(['apprenant_id', 'classe_id', 'annee_scolaire_id'], 'unique_apprenant_classe_annee');
            $table->index('numero_inscription');
            $table->index('statut');
            $table->index(['external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
    }
};
