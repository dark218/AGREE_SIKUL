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
        if (!Schema::hasTable('achats_depenses')) Schema::create('achats_depenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->onDelete('set null');
            $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('set null');
            $table->foreignId('ecole_id')->nullable()->constrained('ecoles')->onDelete('set null');
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->onDelete('set null');
            $table->date('date_depense')->nullable();
            $table->string('nature_depense')->nullable();
            $table->string('tiers_fournisseur')->nullable();
            $table->string('numero_identifiant')->nullable();
            $table->string('type_piece')->nullable();
            $table->string('reference_piece')->nullable();
            $table->string('intitule_operation')->nullable();
            $table->decimal('montant', 15, 2)->nullable();
            $table->string('mode_paiement')->nullable();
            $table->date('date_paiement_1')->nullable();
            $table->decimal('montant_paiement_1', 15, 2)->nullable();
            $table->date('date_paiement_2')->nullable();
            $table->decimal('montant_paiement_2', 15, 2)->nullable();
            $table->date('date_paiement_3')->nullable();
            $table->decimal('montant_paiement_3', 15, 2)->nullable();
            $table->date('date_paiement_4')->nullable();
            $table->decimal('montant_paiement_4', 15, 2)->nullable();
            $table->date('date_paiement_5')->nullable();
            $table->decimal('montant_paiement_5', 15, 2)->nullable();
            $table->date('date_paiement_6')->nullable();
            $table->decimal('montant_paiement_6', 15, 2)->nullable();
            $table->decimal('montant_total_paye', 15, 2)->nullable();
            $table->decimal('restant_a_payer', 15, 2)->nullable();
            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('ecole_id');
            $table->index('annee_scolaire_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achats_depenses');
    }
};
