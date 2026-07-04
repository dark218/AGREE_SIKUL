<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (!Schema::hasTable('ecoles')) Schema::hasTable('ecoles') ? null : Schema::create('ecoles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained('campuses')->cascadeOnDelete();
            $table->string('code', 100)->unique();
            $table->string('nom', 255);
            $table->string('type_enseignement', 100)->nullable();
            $table->foreignId('directeur_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->unsignedInteger('capacite_totale')->nullable();
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
            $table->index('campus_id');
            $table->index('statut');
            $table->index(['external_id']);
            $table->index(['source_system']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecoles');
    }
};
