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
        if (!Schema::hasTable('facturation_apprenants')) Schema::hasTable('facturation_apprenants') ? null : Schema::create('facturation_apprenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->onDelete('set null');
            $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('set null');
            $table->foreignId('ecole_id')->nullable()->constrained('ecoles')->onDelete('set null');
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->onDelete('set null');
            $table->foreignId('cycle_id')->nullable()->constrained('cycles_enseignement')->onDelete('set null');
            $table->foreignId('niveau_id')->nullable()->constrained('niveaux')->onDelete('set null');
            $table->string('code')->nullable();
            $table->string('libelle')->nullable();
            $table->string('ligne_recette')->nullable();
            $table->string('unite_facturation')->nullable();
            $table->decimal('quantite', 15, 2)->nullable();
            $table->decimal('montant', 15, 2)->nullable();
            $table->date('date_debut_exigibilite')->nullable();
            $table->date('date_fin_exigibilite')->nullable();
            $table->string('compte_comptable')->nullable();
            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturation_apprenants');
    }
};
