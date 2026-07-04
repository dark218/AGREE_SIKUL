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
        if (!Schema::hasTable('autres_revenus')) Schema::hasTable('autres_revenus') ? null : Schema::create('autres_revenus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->onDelete('set null');
            $table->foreignId('niveau_id')->nullable()->constrained('niveaux_etudes')->onDelete('set null');
            $table->foreignId('ecole_id')->nullable()->constrained('ecoles')->onDelete('set null');
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->onDelete('set null');
            $table->decimal('uniforme', 15, 2)->nullable();
            $table->decimal('tenue_mercredi', 15, 2)->nullable();
            $table->decimal('tenue_sport', 15, 2)->nullable();
            $table->decimal('autre1', 15, 2)->nullable();
            $table->decimal('autre2', 15, 2)->nullable();
            $table->decimal('autre3', 15, 2)->nullable();
            $table->decimal('autre4', 15, 2)->nullable();
            $table->decimal('autre5', 15, 2)->nullable();
            $table->decimal('autre6', 15, 2)->nullable();
            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['annee_scolaire_id', 'ecole_id']);
            $table->index('etat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('autres_revenus');
    }
};
