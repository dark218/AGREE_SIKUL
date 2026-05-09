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
        if (!Schema::hasTable('inscriptions_transport')) Schema::create('inscriptions_transport', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_transport_id')->constrained('services_transport')->cascadeOnDelete();
            $table->foreignId('apprenant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->onDelete('set null');
            $table->string('statut')->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['service_transport_id', 'apprenant_id']);
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscriptions_transport');
    }
};
