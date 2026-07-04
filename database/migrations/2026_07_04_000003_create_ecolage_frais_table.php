<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lignes de frais configurables d'un écolage (paramétrage écolage et frais).
 * Chaque ligne : Poste (recette), Compte comptable, Montant, Date limite de paiement.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ecolage_frais')) {
            return;
        }

        Schema::create('ecolage_frais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecolage_id')->constrained('ecolages')->cascadeOnDelete();
            $table->foreignId('poste_recette_id')->nullable()->constrained('postes_recettes')->nullOnDelete();
            $table->foreignId('plan_compte_id')->nullable()->constrained('plan_comptes')->nullOnDelete();
            $table->string('libelle', 255)->nullable();
            $table->decimal('montant', 15, 2)->default(0);
            $table->date('date_limite')->nullable();
            $table->unsignedInteger('ordre')->default(0);
            $table->enum('etat', ['actif', 'inactif'])->default('actif');

            // Audit (auto-rempli par BaseModel si présent)
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->string('deletion_username')->nullable();
            $table->string('source_system')->default('agree_sikul');

            $table->timestamps();
            $table->softDeletes();

            $table->index('ecolage_id');
            $table->index('poste_recette_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecolage_frais');
    }
};
