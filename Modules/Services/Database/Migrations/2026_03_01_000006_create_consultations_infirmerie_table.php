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
        if (!Schema::hasTable('consultations_infirmerie')) Schema::create('consultations_infirmerie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apprenant_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('date_consultation');
            $table->string('motif')->nullable();
            $table->text('observations')->nullable();
            $table->string('traitement_prescrit')->nullable();
            $table->string('statut')->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['apprenant_id', 'date_consultation']);
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations_infirmerie');
    }
};
