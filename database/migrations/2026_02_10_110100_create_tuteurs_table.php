<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tuteurs')) Schema::create('tuteurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apprenant_id')->constrained('apprenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('relation', ['pere', 'mere', 'tuteur_legal', 'grand_parent', 'autre'])->nullable();
            $table->string('profession', 255)->nullable();
            $table->string('employeur', 255)->nullable();
            $table->string('numero_urgence', 20)->nullable();

            // Audit
            $table->string('checksum')->nullable();
            $table->string('external_id')->nullable();
            $table->string('source_system')->default('agree_sikul');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->string('deletion_username')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index('apprenant_id');
            $table->index(['external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tuteurs');
    }
};
