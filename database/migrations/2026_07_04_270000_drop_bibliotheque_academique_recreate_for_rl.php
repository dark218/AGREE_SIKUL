<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1.5 — Résout la collision de table `bibliotheques` entre les modules
 * Academique (schéma catalogue : sujet/langue/niveau_id/type_manuel/…) et
 * RessourcesLogistique (schéma lieu : ecole_id/nom/adresse/capacite/responsable_id).
 *
 * La table portait le schéma "catalogue" (Académique) → RL était cassé au runtime.
 * Tout est vide (0 lignes en prod) → pas de perte de données.
 *
 * Idempotente :
 *   - Si `bibliotheques` a déjà le bon schéma (colonne `nom`), skip la recréation.
 *   - Si les tables `bibliotheque_structures/entrees_livres/sorties_livres`
 *     ont été recréées par les migrations 2026_07_05_000010..12 (batch 15
 *     joué avant celle-ci), skip leur drop.
 *   - Drop les FK entrantes avant chaque drop pour éviter MySQL 3730.
 *
 * Purge aussi les permissions RBAC des features Académique.
 */
return new class extends Migration
{
    private const FEATURES_TO_DROP = [
        'bibliotheques',            // catalogue Académique
        'bibliotheque-structures',
        'entrees-livres',
        'sorties-livres',
        'inventaire-livres',
    ];

    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // 1. Si `bibliotheques` existe avec l'ancien schéma Académique
        //    (détection par la colonne `sujet` propre au catalogue), on la drop.
        //    Sinon on la laisse (déjà bon schéma RL).
        if (Schema::hasTable('bibliotheques')) {
            $isAcademicSchema = Schema::hasColumn('bibliotheques', 'sujet')
                || Schema::hasColumn('bibliotheques', 'type_manuel');
            if ($isAcademicSchema) {
                $this->dropIncomingForeignKeys('bibliotheques');
                Schema::drop('bibliotheques');
            }
        }

        // 2. Drop les tables satellites de l'ancien schéma catalogue si présentes.
        //    Attention : les migrations 2026_07_05_000010..12 (batch 15) recréent
        //    `bibliotheque_structures`, `entrees_livres`, `sorties_livres` avec
        //    de nouveaux schémas — dans ce cas on ne les touche PAS. On identifie
        //    l'ancien schéma via des colonnes qui n'existent qu'en catalogue.
        $this->dropIfAncienSchema('bibliotheque_structures', 'niveau_id_apprenant');
        $this->dropIfAncienSchema('entrees_livres',          'niveau_id_recepteur');
        $this->dropIfAncienSchema('sorties_livres',          'niveau_id_apprenant');

        // 3. Recrée `bibliotheques` avec le schéma RL uniquement si absente.
        if (!Schema::hasTable('bibliotheques')) {
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
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // 4. Purge features RBAC Académique + permissions associées.
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

    /**
     * Drop la table si elle a l'ancien schéma catalogue Académique (détecté via
     * une colonne unique à ce schéma). Sinon skip — évite de casser le nouveau
     * schéma RL recréé entre-temps.
     */
    private function dropIfAncienSchema(string $table, string $ancienSchemaMarkerCol): void
    {
        if (!Schema::hasTable($table)) return;
        if (!Schema::hasColumn($table, $ancienSchemaMarkerCol)) return;
        $this->dropIncomingForeignKeys($table);
        Schema::drop($table);
    }

    /**
     * Drop toutes les foreign key qui pointent VERS la table cible.
     */
    private function dropIncomingForeignKeys(string $targetTable): void
    {
        $connection = DB::connection()->getDatabaseName();
        $rows = DB::select(
            "SELECT TABLE_NAME AS source_table, CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND REFERENCED_TABLE_SCHEMA = ?
               AND REFERENCED_TABLE_NAME = ?
               AND CONSTRAINT_NAME != 'PRIMARY'",
            [$connection, $connection, $targetTable]
        );
        foreach ($rows as $r) {
            try {
                DB::statement("ALTER TABLE `{$r->source_table}` DROP FOREIGN KEY `{$r->CONSTRAINT_NAME}`");
            } catch (\Throwable $e) {}
        }
    }
};
