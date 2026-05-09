<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('niveaux')) Schema::create('niveaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecole_id')->constrained('ecoles')->cascadeOnDelete();
            $table->string('code', 100)->unique();
            $table->string('libelle', 255);
            $table->unsignedInteger('ordre')->nullable();
            $table->unsignedInteger('age_min')->nullable();
            $table->unsignedInteger('age_max')->nullable();
            $table->string('cycle', 50)->nullable();
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
            $table->index('code');
            $table->index('ecole_id');
            $table->index('ordre');
            $table->index(['external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('niveaux');
    }
};
