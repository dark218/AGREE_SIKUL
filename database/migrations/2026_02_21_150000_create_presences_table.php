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
        if (!Schema::hasTable('presences')) Schema::hasTable('presences') ? null : Schema::create('presences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('apprenant_id')->nullable();
            $table->unsignedBigInteger('seance_id')->nullable();
            $table->boolean('present')->default(false);
            $table->boolean('retard')->default(false);
            $table->enum('statut', ['present', 'retard', 'absent', 'malade', 'permis'])->default('absent');
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('apprenant_id')->references('id')->on('apprenants')->onDelete('cascade');
            $table->foreign('seance_id')->references('id')->on('seances')->onDelete('cascade');

            // Indexes
            $table->index('apprenant_id');
            $table->index('seance_id');
            $table->unique(['apprenant_id', 'seance_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presences');
    }
};
