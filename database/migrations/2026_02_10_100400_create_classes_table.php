<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (!Schema::hasTable('classes')) Schema::hasTable('classes') ? null : Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('niveau_id')->constrained('niveaux')->cascadeOnDelete();
            $table->string('nom', 100);
            $table->unsignedInteger('capacite_max')->nullable();
            $table->string('salle', 50)->nullable();
            $table->enum('statut', ['non_actif', 'actif', 'suspendu'])->default('actif');

            // Audit
            $table->string('checksum')->nullable();
            $table->string('external_id')->nullable();
            $table->string('source_system')->default('agree_sikul');
            $table->string('creation_hostname')->nullable();
            $table->string('modification_hostname')->nullable();
            $table->string('deletion_hostname')->nullable();
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->string('deletion_username')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index('ecole_id');
            $table->index('niveau_id');
            $table->index(['external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
