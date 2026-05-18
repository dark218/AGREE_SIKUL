<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Recentrage AGREE SIKUL sur les fonctionnalités scolaires UNIQUEMENT.
 *
 * Suppression des tables liées aux modules commerciaux :
 *   - Business (marchands, points_vente, terminaux, caisses, comptes_bancaires_marchand)
 *   - Pos (ventes_pos, vente_pos_lignes, vente_pos_refunds, sessions_caisse)
 *   - Wallet (wallets, wallet_mouvements)
 *   - GestionStock (articles, mouvements_stock, transferts_stock, transferts_stock_lignes, inventaires, inventaire_lignes)
 *   - Personnel commercial (employes, affectations_agents, missions_agents)
 *   - Parametrage commercial (banques, fournisseurs_paiement, moyens_paiement, devises_pays)
 *   - Transactions globales (transactions, vue_transactions_par_zone_mensuelle)
 *
 * Les tables conservées : users, jwt_tokens, jwt_blacklist, permissions, roles, sessions,
 * et tout l'écosystème scolaire (apprenants, classes, ecoles, campuses, institutions,
 * bulletins, notes, absences, presences, cours, seances, examens, ecolage, frais, paiements,
 * salaires, depenses, cantine, transport, bibliotheque, etc).
 *
 * ORDRE DE DROP : tables enfants d'abord, tables parentes ensuite (FK cascade safe).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Désactiver les contraintes FK temporairement (MySQL/MariaDB)
        Schema::disableForeignKeyConstraints();

        // ── 1. POS (enfants) ──
        $this->dropIfExists([
            'vente_pos_refunds',
            'vente_pos_lignes',
            'ventes_pos',
        ]);

        // ── 2. Stock ──
        $this->dropIfExists([
            'inventaire_lignes',
            'inventaires',
            'transferts_stock_lignes',
            'transferts_stock',
            'mouvements_stock',
            'articles',
        ]);

        // ── 3. Wallet & Transactions ──
        $this->dropIfExists([
            'wallet_mouvements',
            'wallets',
            'transactions',
        ]);

        // Vue (s'il s'agit d'une vue MySQL, pas une table)
        try {
            DB::statement('DROP VIEW IF EXISTS vue_transactions_par_zone_mensuelle');
        } catch (\Throwable $e) {
            // si c'est une table, on tente le drop normal
            if (Schema::hasTable('vue_transactions_par_zone_mensuelle')) {
                Schema::drop('vue_transactions_par_zone_mensuelle');
            }
        }

        // ── 4. Caisses & Sessions caisse ──
        $this->dropIfExists([
            'sessions_caisse',
            'caisses',
        ]);

        // ── 5. Comptes bancaires marchand ──
        $this->dropIfExists([
            'comptes_bancaires_marchand',
        ]);

        // ── 6. Personnel commercial ──
        $this->dropIfExists([
            'missions_agents',
            'affectations_agents',
            'employes',
        ]);

        // ── 7. Terminaux & Points de vente ──
        $this->dropIfExists([
            'terminaux',
            'points_vente',
        ]);

        // ── 8. Marchands (top of commercial hierarchy) ──
        $this->dropIfExists([
            'marchands',
        ]);

        // ── 9. Parametrage commercial ──
        $this->dropIfExists([
            'moyen_paiement',
            'moyens_paiement',
            'fournisseurs_paiement',
            'fournisseur_paiement',
            'devises_pays',
            'devise_pays',
            'banques',
        ]);

        // ── 10. ServiceClient / Client commercial ──
        $this->dropIfExists([
            'clients',
            'services_clients',
            'services_validateurs',
            'virtual_cards',
        ]);

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Pas de rollback : la suppression de ces modules est définitive
        // dans le recentrage sur AGREE SIKUL (école uniquement).
        // Pour les restaurer : retourner aux migrations originales correspondantes.
    }

    private function dropIfExists(array $tables): void
    {
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
            }
        }
    }
};
