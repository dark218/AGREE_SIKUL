<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Purge les résidus SmilPay des tables `feature` et `module` (Phase 5).
 *
 * Contexte : le fork SmilPay a laissé des modules commerciaux (Business, POS,
 * Wallet, Service Client, Gestion Stock) et des tables non pertinentes pour
 * une école (Bibliothèque et Inventaire — recréés dans RessourcesLogistique).
 *
 * Cette migration :
 *   1. Supprime les features des 7 modules SmilPay identifiés
 *   2. Supprime les modules SmilPay eux-mêmes
 *   3. Purge les permissions Spatie associées (`{menu_url}-*`)
 *   4. Ajoute le module 27 (Paramétrage Généraux) s'il est absent — la feature
 *      166 y référait sans que le module existe → FK cassée au seed
 *
 * Idempotente : peut être rejouée sans effet sur une DB déjà nettoyée.
 */
return new class extends Migration
{
    /**
     * IDs des modules SmilPay à purger.
     * Voir DEFAULT_MENU_CONFIG dans TheSidebar.vue pour le nouveau menu.
     */
    private const SMILPAY_MODULE_IDS = [
        12, // Bibliothèque (remplacée par RessourcesLogistique/Bibliotheque)
        15, // Inventaire (fusionné dans RessourcesLogistique)
        17, // Business
        19, // Service Client
        20, // POS
        21, // Gestion Stock
        22, // Wallet
    ];

    public function up(): void
    {
        if (!Schema::hasTable('module') || !Schema::hasTable('feature')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // 1. Récupère les features des modules à purger — pour couper les
        //    permissions/rôles avant suppression.
        $featureMenuUrls = DB::table('feature')
            ->whereIn('module_id', self::SMILPAY_MODULE_IDS)
            ->pluck('menu_url')
            ->filter()
            ->values()
            ->all();

        // 2. Purge Spatie permissions liées (patterns `{menu_url}-{action}`).
        //    On coupe d'abord role_has_permissions pour éviter les orphelins.
        if (Schema::hasTable('permissions') && !empty($featureMenuUrls)) {
            foreach ($featureMenuUrls as $slug) {
                $permIds = DB::table('permissions')
                    ->where('name', 'like', $slug . '-%')
                    ->pluck('id')->all();
                if (!empty($permIds)) {
                    if (Schema::hasTable('role_has_permissions')) {
                        DB::table('role_has_permissions')->whereIn('permission_id', $permIds)->delete();
                    }
                    if (Schema::hasTable('model_has_permissions')) {
                        DB::table('model_has_permissions')->whereIn('permission_id', $permIds)->delete();
                    }
                    DB::table('permissions')->whereIn('id', $permIds)->delete();
                }
            }
        }

        // 3. Purge features SmilPay (cascade sur pivots via FK).
        DB::table('feature')
            ->whereIn('module_id', self::SMILPAY_MODULE_IDS)
            ->delete();

        // 4. Purge modules SmilPay.
        DB::table('module')
            ->whereIn('id', self::SMILPAY_MODULE_IDS)
            ->delete();

        // 5. Ajoute module 27 (Paramétrage Généraux) si absent — sinon la
        //    feature id 166 pointe dans le vide (FK cassée).
        $module27 = DB::table('module')->where('id', 27)->first();
        if (!$module27) {
            DB::table('module')->insert([
                'id'         => 27,
                'libelle'    => 'Paramétrage Généraux',
                'libelle_en' => 'General Settings',
                'menu_url'   => 'javascript:;',
                'icone'      => 'fas fa-sliders-h',
                'ordre'      => 19,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // Aucune restauration : les modules SmilPay ne doivent jamais réapparaître
        // dans Agree Sikul. Utilisez les seeders TModuleSeeder/TFeatureSeeder si
        // vous devez régénérer le RBAC depuis zéro.
    }
};
