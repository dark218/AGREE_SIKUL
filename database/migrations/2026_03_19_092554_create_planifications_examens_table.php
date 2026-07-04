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
        if (!Schema::hasTable('planifications_examens')) {
            Schema::hasTable('planifications_examens') ? null : Schema::create('planifications_examens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('nature_examen_id')->nullable()->constrained('natures_examens')->onDelete('set null');
                $table->foreignId('type_examen_id')->nullable()->constrained('type_examens')->onDelete('set null');
                $table->foreignId('classe_id')->nullable()->constrained('classes')->onDelete('set null');
                $table->foreignId('matiere_id')->nullable()->constrained('matieres')->onDelete('set null');
                $table->foreignId('enseignant_id')->nullable()->constrained('enseignants')->onDelete('set null');
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
                $table->index('nature_examen_id');
                $table->index('classe_id');
                $table->index('matiere_id');
                $table->index('enseignant_id');
                $table->index('etat');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planifications_examens');
    }
};
