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
        Schema::hasTable('vente_pos_lignes') ? null : Schema::create('vente_pos_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ventes_pos_id')
                ->constrained('ventes_pos')
                ->cascadeOnDelete();

            $table->foreignId('article_id')
                ->nullable()
                ->constrained('articles');

            $table->enum('type_ligne', ['article', 'remise', 'frais'])
                ->default('article')
                ->comment(
                    "Nature de la ligne de vente :
                        - article : produit ou service vendu,
                        - remise : réduction (globale ou spécifique),
                        - frais : frais additionnels (livraison, service, sac, etc.)"
                );

            $table->string('libelle', 255);

            $table->integer('quantite');

            $table->unsignedBigInteger('prix_unitaire_cents')
                ->comment(
                    "Prix unitaire de base de la ligne, stocké en centimes.
                            - Pour un article : prix de vente unitaire
                            - Pour un frais : montant du frais
                            - Pour une remise : généralement 0 (la remise est portée par remise_cents)"
                );

            $table->unsignedBigInteger('remise_cents')
                ->default(0)
                ->comment(
                    "Montant de la remise appliquée à cette ligne, en centimes.
                            Valeur toujours positive, soustraite lors du calcul du total.
                            Peut représenter une remise article ou une remise globale."
                );
            $table->unsignedBigInteger('taxe_cents')
                ->default(0)
                ->comment(
                    "Montant total de la taxe appliquée à cette ligne, en centimes.
                            La taxe est calculée au moment de la vente et figée pour éviter tout recalcul ultérieur."
                );

            $table->unsignedBigInteger('total_ligne_cents')
                ->comment(
                    "Montant final de la ligne en centimes, après calcul.
                        Formule logique :
                        - article : (prix_unitaire × quantité) - remise + taxe
                        - frais   : prix_unitaire
                        - remise  : remise (sera soustraite du total de la vente)"
                );
            $table->string('devise', 10);
            $table->foreignId('deleted_by')
                ->nullable() // Rendre la clé étrangère nullable si besoin
                ->constrained('users')
                ->onDelete('cascade');
            $table->unsignedBigInteger('rembourse_cents')->default(0)
                ->comment('Montant remboursé sur cette ligne');
            $table->timestamp('rembourse_at')->nullable();
            $table->foreignId('rembourse_by')->nullable()->constrained('users');
            $table->integer('quantite_remboursee')->default(0)
                ->comment('Quantité déjà remboursée sur cette ligne');
            $table->json('taxes_json')->nullable();
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
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vente_pos_lignes');
    }
};
