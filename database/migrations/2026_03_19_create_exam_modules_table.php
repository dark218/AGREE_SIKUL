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
        // idempotence guard
        // Table Planification des examens
        if (!Schema::hasTable('planification_examens')) Schema::hasTable('planification_examens') ? null : Schema::create('planification_examens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nature_examen_id')->nullable()->constrained('natures_examens')->onDelete('set null');
            $table->foreignId('type_examen_id')->nullable()->constrained('type_examens')->onDelete('set null');
            $table->foreignId('classe_id')->nullable()->constrained('classes')->onDelete('set null');
            $table->foreignId('matiere_id')->nullable()->constrained('matieres')->onDelete('set null');
            $table->string('jour')->nullable();
            $table->date('date')->nullable();
            $table->time('heure_debut')->nullable();
            $table->time('heure_fin')->nullable();
            $table->decimal('duree', 5, 2)->nullable();
            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['classe_id', 'matiere_id']);
            $table->index('etat');
        });

        // Table Examens en ligne
        if (!Schema::hasTable('examens_en_ligne')) Schema::hasTable('examens_en_ligne') ? null : Schema::create('examens_en_ligne', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planification_examen_id')->nullable()->constrained('planification_examens')->onDelete('cascade');
            $table->foreignId('classe_id')->nullable()->constrained('classes')->onDelete('set null');
            $table->foreignId('matiere_id')->nullable()->constrained('matieres')->onDelete('set null');
            $table->string('titre')->nullable();
            $table->text('description')->nullable();
            $table->dateTime('date_debut')->nullable();
            $table->dateTime('date_fin')->nullable();
            $table->integer('nombre_questions')->nullable();
            $table->decimal('duree_minutes', 5, 2)->nullable();
            $table->decimal('note_maximum', 5, 2)->nullable();
            $table->decimal('note_minimum_passage', 5, 2)->nullable();
            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['classe_id', 'matiere_id']);
            $table->index('etat');
        });

        // Table Résultats des examens
        if (!Schema::hasTable('resultats_examens')) Schema::hasTable('resultats_examens') ? null : Schema::create('resultats_examens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examen_en_ligne_id')->nullable()->constrained('examens_en_ligne')->onDelete('cascade');
            $table->foreignId('apprenant_id')->nullable()->constrained('apprenants')->onDelete('cascade');
            $table->foreignId('classe_id')->nullable()->constrained('classes')->onDelete('set null');
            $table->decimal('note_obtenue', 5, 2)->nullable();
            $table->decimal('pourcentage', 5, 2)->nullable();
            $table->string('statut')->nullable(); // Réussi, Échoué, En attente
            $table->dateTime('date_composition')->nullable();
            $table->integer('nombre_questions_repondues')->nullable();
            $table->integer('nombre_questions_correctes')->nullable();
            $table->decimal('temps_passe_minutes', 5, 2)->nullable();
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['apprenant_id', 'classe_id']);
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resultats_examens');
        Schema::dropIfExists('examens_en_ligne');
        Schema::dropIfExists('planification_examens');
    }
};
