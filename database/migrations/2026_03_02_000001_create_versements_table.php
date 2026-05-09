<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('versements')) Schema::create('versements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->onDelete('set null');
            $table->foreignId('apprenant_id')->nullable()->constrained('apprenants')->onDelete('set null');
            $table->foreignId('niveau_id')->nullable()->constrained('niveaux')->onDelete('set null');
            $table->foreignId('classe_id')->nullable()->constrained('classes')->onDelete('set null');
            $table->foreignId('ecole_id')->nullable()->constrained('ecoles')->onDelete('set null');
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->onDelete('set null');

            // Frais
            $table->decimal('frais_dossier', 15, 2)->nullable();
            $table->decimal('frais_inscription', 15, 2)->nullable();
            $table->decimal('frais_scolarite', 15, 2)->nullable();
            $table->decimal('total_paye', 15, 2)->nullable();
            $table->decimal('restant_a_payer', 15, 2)->nullable();

            // 12 versements (nature + montant chacun)
            $table->string('nature_versement_1')->nullable();
            $table->decimal('montant_versement_1', 15, 2)->nullable();
            $table->string('nature_versement_2')->nullable();
            $table->decimal('montant_versement_2', 15, 2)->nullable();
            $table->string('nature_versement_3')->nullable();
            $table->decimal('montant_versement_3', 15, 2)->nullable();
            $table->string('nature_versement_4')->nullable();
            $table->decimal('montant_versement_4', 15, 2)->nullable();
            $table->string('nature_versement_5')->nullable();
            $table->decimal('montant_versement_5', 15, 2)->nullable();
            $table->string('nature_versement_6')->nullable();
            $table->decimal('montant_versement_6', 15, 2)->nullable();
            $table->string('nature_versement_7')->nullable();
            $table->decimal('montant_versement_7', 15, 2)->nullable();
            $table->string('nature_versement_8')->nullable();
            $table->decimal('montant_versement_8', 15, 2)->nullable();
            $table->string('nature_versement_9')->nullable();
            $table->decimal('montant_versement_9', 15, 2)->nullable();
            $table->string('nature_versement_10')->nullable();
            $table->decimal('montant_versement_10', 15, 2)->nullable();
            $table->string('nature_versement_11')->nullable();
            $table->decimal('montant_versement_11', 15, 2)->nullable();
            $table->string('nature_versement_12')->nullable();
            $table->decimal('montant_versement_12', 15, 2)->nullable();

            // Status & metadata
            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['apprenant_id', 'annee_scolaire_id']);
            $table->index('ecole_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('versements');
    }
};
