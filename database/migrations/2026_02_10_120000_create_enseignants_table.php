<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (!Schema::hasTable('enseignants')) Schema::hasTable('enseignants') ? null : Schema::create('enseignants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('matricule', 100)->unique();
            $table->string('specialite', 255)->nullable();
            $table->string('diplome', 255)->nullable();
            $table->date('date_embauche')->nullable();
            $table->enum('type_contrat', ['cdi', 'cdd', 'vacataire', 'autre'])->nullable();
            $table->enum('statut', ['actif', 'suspendu', 'conge', 'retraite'])->default('actif');

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
            $table->index('matricule');
            $table->index('statut');
            $table->index(['external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enseignants');
    }
};
