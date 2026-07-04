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
        if (!Schema::hasTable('resultats_examens')) {
            Schema::hasTable('resultats_examens') ? null : Schema::create('resultats_examens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('matiere_id')->nullable()->constrained('matieres')->onDelete('set null');
                $table->foreignId('classe_id')->nullable()->constrained('classes')->onDelete('set null');
                $table->foreignId('apprenant_id')->nullable()->constrained('apprenants')->onDelete('set null');
                $table->date('date_debut')->nullable();
                $table->date('date_fin')->nullable();
                $table->decimal('note_maximale', 5, 2)->nullable();
                $table->integer('nombre_questions')->nullable();
                $table->decimal('duree', 8, 2)->nullable();
                $table->decimal('temps_effectue', 8, 2)->nullable();
                $table->decimal('points', 8, 2)->nullable();
                $table->integer('reponses_correctes')->nullable();
                $table->integer('reponses_fausses')->nullable();
                $table->integer('non_repondues')->nullable();
                $table->integer('reponses_douteuses')->nullable();
                $table->enum('etat', ['actif', 'inactif'])->default('actif');
                $table->string('creation_username')->nullable();
                $table->string('modification_username')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index('matiere_id');
                $table->index('apprenant_id');
                $table->index('classe_id');
                $table->index('etat');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resultats_examens');
    }
};
