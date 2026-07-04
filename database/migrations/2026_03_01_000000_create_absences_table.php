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
        if (!Schema::hasTable('absences')) Schema::hasTable('absences') ? null : Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apprenant_id')->nullable()->constrained('apprenants')->cascadeOnDelete();
            $table->foreignId('classe_id')->nullable()->constrained('classes')->cascadeOnDelete();
            $table->integer('week_number')->default(1); // Semaine 1-5
            $table->date('date_absence'); // Date spécifique du jour absent
            $table->enum('day_of_week', ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'])->default('lundi');
            $table->time('time_from')->nullable(); // Heure de début
            $table->time('time_to')->nullable(); // Heure de fin
            $table->text('motif')->nullable(); // Raison de l'absence
            $table->enum('statut', ['en_attente', 'justifiee', 'non_justifiee', 'partiellement_justifiee'])->default('en_attente');
            $table->enum('etat', ['actif', 'inactif'])->default('actif');

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
            $table->index(['apprenant_id', 'week_number', 'date_absence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absences');
    }
};
