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
        Schema::create('mouvements_stock', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('article_id')
                ->constrained('articles')
                ->comment('Article concerné par le mouvement de stock');

            $table->enum('type_mouvement', [
                'vente',            // Sortie suite à une vente POS
                'remboursement',    // Retour client
                'entree_stock',     // Approvisionnement fournisseur
                'sortie_stock',     // Perte, casse, vol
                'transfert',        // Déplacement entre emplacements
                'inventaire',       // Ajustement inventaire
                'correction',       // Correction administrative
            ])->comment('Nature du mouvement de stock');
            $table->integer('quantite')
                ->comment('Variation de stock (+ entrée / - sortie)');
            $table->integer('quantite_stock_avant')
                ->comment('Quantité stock avant le mouvement');
            $table->integer('quantite_stock_apres')
                ->comment('Quantité stock après le mouvement');
            $table->foreignId('emplacement_source_id')
                ->nullable()
                ->constrained('points_vente')
                ->comment('Emplacement source du stock');
            $table->foreignId('emplacement_destination_id')
                ->nullable()
                ->constrained('points_vente')
                ->comment('Emplacement de destination du stock');
            $table->foreignId('employe_id')
                ->nullable()
                ->constrained('employes')
                ->comment('Employé à l’origine du mouvement');
            $table->string('reference', 255)
                ->nullable()
                ->comment('Référence métier liée au mouvement');
            $table->text('commentaire')
                ->nullable()
                ->comment('Commentaire explicatif du mouvement');
            $table->string('checksum')->nullable();
            $table->string('external_id')->nullable();
            $table->string('source_system')->default('smilpay');
            $table->string('creation_hostname')->nullable();
            $table->string('modification_hostname')->nullable();
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->string('deletion_username')->nullable();
            $table->index(['external_id']);
            $table->index(['source_system']);
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mouvements_stock');
    }
};
