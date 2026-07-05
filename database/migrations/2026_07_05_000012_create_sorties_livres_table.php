<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sorties de livres (prêt / vente / don) d'une bibliothèque.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sorties_livres')) {
            return;
        }

        Schema::create('sorties_livres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bibliotheque_id')->nullable()->constrained('bibliotheques')->nullOnDelete();
            $table->foreignId('bibliotheque_structure_id')->nullable()->constrained('bibliotheque_structures')->nullOnDelete();
            $table->enum('type_sortie', ['pret', 'vente', 'don'])->default('pret');
            $table->date('date_sortie')->nullable();
            $table->unsignedInteger('quantite')->default(1);
            $table->date('date_retour')->nullable();
            $table->string('tiers', 255)->nullable(); // Emprunteur / Acheteur / Donateur
            $table->string('etat_physique', 100)->nullable();
            $table->enum('etat', ['actif', 'inactif'])->default('actif');

            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->string('source_system')->default('agree_sikul');

            $table->timestamps();
            $table->softDeletes();

            $table->index('bibliotheque_id');
            $table->index('bibliotheque_structure_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sorties_livres');
    }
};
