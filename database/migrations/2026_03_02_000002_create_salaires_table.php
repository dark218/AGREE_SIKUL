<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (!Schema::hasTable('salaires')) Schema::hasTable('salaires') ? null : Schema::create('salaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->onDelete('set null');
            $table->foreignId('ecole_id')->nullable()->constrained('ecoles')->onDelete('set null');
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->onDelete('set null');
            // Identification
            $table->string('nom')->nullable();
            $table->string('institution')->nullable();
            $table->string('mois_paie')->nullable();
            $table->string('intitule')->nullable();
            $table->string('noms')->nullable();
            $table->string('prenoms')->nullable();
            $table->string('nom_restituer')->nullable();
            $table->string('matricule_interne')->nullable();
            $table->string('matricule_cnps')->nullable();
            $table->string('numero_identifiant')->nullable();
            // Salaire
            $table->decimal('salaire_base', 15, 2)->nullable();
            $table->decimal('primes', 15, 2)->nullable();
            $table->decimal('indemnites', 15, 2)->nullable();
            $table->decimal('salaire_brut', 15, 2)->nullable();
            $table->decimal('retenues_fiscales', 15, 2)->nullable();
            $table->decimal('retenues_sociales', 15, 2)->nullable();
            $table->decimal('autres_retenues', 15, 2)->nullable();
            $table->decimal('saisies_oppositions', 15, 2)->nullable();
            $table->decimal('salaire_net', 15, 2)->nullable();
            // Paiements
            $table->decimal('paiement_integral', 15, 2)->nullable();
            $table->date('date_paiement_integral')->nullable();
            $table->decimal('avance1', 15, 2)->nullable();
            $table->date('date_avance1')->nullable();
            $table->decimal('avance2', 15, 2)->nullable();
            $table->date('date_avance2')->nullable();
            $table->decimal('avance3', 15, 2)->nullable();
            $table->date('date_avance3')->nullable();
            $table->decimal('avance4', 15, 2)->nullable();
            $table->date('date_avance4')->nullable();
            $table->decimal('total_paye', 15, 2)->nullable();
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

    public function down(): void
    {
        Schema::dropIfExists('salaires');
    }
};
