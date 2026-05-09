<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cette table contient les moyens de paiement "personnels" d'un utilisateur.
     * - identifiant_chiffre : numéro/IBAN chiffré (stockage TEXT)
     * - external_id : id renvoyé/tokenisé par le provider (pour éviter de stocker le numéro en clair)
     * - fournisseur_id : FK vers fournisseurs_paiement (optionnel)
     */
    public function up(): void
    {
        Schema::create('moyen_paiement', function (Blueprint $table) {
            $table->id();

            // Propriétaire du moyen (utilisateur)
            $table->foreignId('users_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Référence au fournisseur (MTN, ORANGE, BANK_X, etc.)
            $table->foreignId('fournisseur_id')
                ->nullable()
                ->constrained('fournisseurs_paiement')
                ->nullOnDelete();

            // Type du moyen : mobile money, IBAN, carte, etc.
            $table->enum('type', ['mm', 'iban', 'card', 'wallet'])->default('mm');

            // Label lisible (ex: "MTN - 0701****1234" ou "Compte courant X")
            $table->string('label')->nullable();

            // Identifiant externe/token fourni par l'opérateur (tokenization)
            $table->string('external_id')->nullable();

            // Identifiant chiffré (numéro mobile money, IBAN, PAN partiel, etc.)
            // Stocker en texte (chiffré coté application) : utiliser les casts chiffrés dans le model.
            $table->text('identifiant_chiffre')->nullable();

            // Token/access credentials (si fournis par le provider)
            $table->text('token_provider')->nullable();

            // Drapeau pour savoir si c'est le moyen par défaut du user
            $table->boolean('is_defaut')->default(false);

            // Statut du moyen (actif/desactivé)
            $table->enum('statut', ['actif', 'desactive'])->default('actif');

            // Métadonnées optionnelles (JSON) : ex. country_code, masked, bin, etc.
            $table->json('metadata')->nullable();

            // Pour audit et recherche
            $table->timestamps();

            // Index pour requêtes fréquentes
            $table->index(['users_id', 'is_defaut']);
            $table->index(['fournisseur_id']);
            $table->index(['external_id']);
            $table->index(['type', 'statut']);

            // Optionnel : empêcher doublons exacts external_id par utilisateur
            $table->unique(['users_id', 'external_id'], 'mp_utilisateur_external_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moyen_paiement');
    }
};
