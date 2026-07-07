<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1.5 — Résout la collision de table `bibliotheques` entre les modules
 * Academique (schéma catalogue : sujet/langue/niveau_id/type_manuel/...) et
 * RessourcesLogistique (schéma lieu : ecole_id/nom/adresse/capacite/responsable_id).
 *
 * La table portait le schéma "catalogue" (Académique) → RL était cassé au runtime.
 * Tout est vide (0 lignes) donc pas de perte de données.
 *
 * Actions :
 *   1. Drop les 4 tables Académique : bibliotheques, bibliotheque_structures,
 *      entrees_livres, sorties_livres.
 *   2. Recrée `bibliotheques` avec le schéma RL (lieu physique).
 *   3. Purge les permissions RBAC des features supprimées.
 */
return new class extends Migration
{
    private const TABLES_TO_DROP = [
        'entrees_livres',           // dépend de bibliotheques
        'sorties_livres',           // dépend de bibliotheques
        'bibliotheque_structures',  // catalogue Académique
        'bibliotheques',            // schéma catalogue à remplacer
    ];

    private const FEATURES_TO_DROP = [
        'bibliotheques',            // catalogue Académique
        'bibliotheque-structures',
        'entrees-livres',
        'sorties-livres',
        'inventaire-livres',
    ];

    public function up(): void
    {
        // 1. Drop des tables Académique (dans l'ordre pour respecter d'éventuelles FK).
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (self::TABLES_TO_DROP as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
            }
        }

        // 2. Recrée `bibliotheques` avec le schéma RL (lieu physique).
        Schema::create('bibliotheques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecole_id')->nullable()->constrained('ecoles')->nullOnDelete();
            $table->string('nom', 125);
            $table->string('adresse', 255)->nullable();
            $table->integer('capacite')->nullable();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->softDeletes();
            $table->timestamps();
            $table->index('ecole_id');
            $table->index('etat');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // 3. Purge features RBAC Académique + permissions associées.
        $featureIds = DB::table('feature')
            ->whereIn('menu_url', self::FEATURES_TO_DROP)
            ->pluck('id')->all();
        if (!empty($featureIds)) {
            $permIds = DB::table('permissions')->whereIn('feature_id', $featureIds)->pluck('id')->all();
            if (!empty($permIds)) {
                DB::table('role_has_permissions')->whereIn('permission_id', $permIds)->delete();
                DB::table('permissions')->whereIn('id', $permIds)->delete();
            }
            DB::table('feature')->whereIn('id', $featureIds)->delete();
        }
    }

    public function down(): void
    {
        // Rollback non trivial.
    }
};
