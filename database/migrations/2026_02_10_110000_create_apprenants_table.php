<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('apprenants')) Schema::create('apprenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('matricule', 100)->unique();
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance', 255)->nullable();
            $table->enum('sexe', ['M', 'F'])->nullable();
            $table->string('nationalite', 100)->nullable();
            $table->string('groupe_sanguin', 10)->nullable();
            $table->foreignId('photo_id')->nullable()->constrained('fichier')->onDelete('cascade');
            $table->enum('statut', ['actif', 'suspendu', 'exclu', 'diplome', 'abandonne'])->default('actif');

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
            $table->index('matricule');
            $table->index('statut');
            $table->index(['external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apprenants');
    }
};
