<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (!Schema::hasTable('campuses')) Schema::hasTable('campuses') ? null : Schema::create('campuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('code', 100)->unique();
            $table->string('nom', 255);
            $table->text('adresse')->nullable();
            $table->string('ville', 100)->nullable();
            $table->string('code_postal', 20)->nullable();
            $table->double('longitude', 10, 8)->nullable();
            $table->double('latitude', 10, 8)->nullable();
            $table->string('telephone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->onDelete('cascade');
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
            $table->index('institution_id');
            $table->index('statut');
            $table->index(['external_id']);
            $table->index(['source_system']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campuses');
    }
};
