<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('affectations_enseignants')) Schema::create('affectations_enseignants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->onDelete('set null');
            $table->foreignId('enseignant_id')->constrained('enseignants')->cascadeOnDelete();
            $table->foreignId('classe_id')->nullable()->constrained('classes')->onDelete('set null');
            $table->foreignId('ecole_id')->nullable()->constrained('ecoles')->onDelete('set null');
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->onDelete('set null');
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->onDelete('set null');

            // 21 Matières FK columns
            $table->foreignId('matiere_1_id')->nullable()->constrained('matieres')->onDelete('set null');
            $table->foreignId('matiere_2_id')->nullable()->constrained('matieres')->onDelete('set null');
            $table->foreignId('matiere_3_id')->nullable()->constrained('matieres')->onDelete('set null');
            $table->foreignId('matiere_4_id')->nullable()->constrained('matieres')->onDelete('set null');
            $table->foreignId('matiere_5_id')->nullable()->constrained('matieres')->onDelete('set null');
            $table->foreignId('matiere_6_id')->nullable()->constrained('matieres')->onDelete('set null');
            $table->foreignId('matiere_7_id')->nullable()->constrained('matieres')->onDelete('set null');
            $table->foreignId('matiere_8_id')->nullable()->constrained('matieres')->onDelete('set null');
            $table->foreignId('matiere_9_id')->nullable()->constrained('matieres')->onDelete('set null');
            $table->foreignId('matiere_10_id')->nullable()->constrained('matieres')->onDelete('set null');
            $table->foreignId('matiere_11_id')->nullable()->constrained('matieres')->onDelete('set null');
            $table->foreignId('matiere_12_id')->nullable()->constrained('matieres')->onDelete('set null');
            $table->foreignId('matiere_13_id')->nullable()->constrained('matieres')->onDelete('set null');
            $table->foreignId('matiere_14_id')->nullable()->constrained('matieres')->onDelete('set null');
            $table->foreignId('matiere_15_id')->nullable()->constrained('matieres')->onDelete('set null');
            $table->foreignId('matiere_16_id')->nullable()->constrained('matieres')->onDelete('set null');
            $table->foreignId('matiere_17_id')->nullable()->constrained('matieres')->onDelete('set null');
            $table->foreignId('matiere_18_id')->nullable()->constrained('matieres')->onDelete('set null');
            $table->foreignId('matiere_19_id')->nullable()->constrained('matieres')->onDelete('set null');
            $table->foreignId('matiere_20_id')->nullable()->constrained('matieres')->onDelete('set null');
            $table->foreignId('matiere_21_id')->nullable()->constrained('matieres')->onDelete('set null');

            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['enseignant_id', 'annee_scolaire_id']);
            $table->index('ecole_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affectations_enseignants');
    }
};
