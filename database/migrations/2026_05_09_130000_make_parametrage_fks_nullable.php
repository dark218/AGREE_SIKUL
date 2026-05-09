<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rend nullables les FK des tables de paramétrage référentiel pour aligner
 * avec les forms simplifiés (Code/Libellé/Statut + FK directe).
 *
 * Tables impactées :
 *  - matieres_unites      : ecole_id, niveau_id, section_id, cycle_id → nullable
 *  - natures_examens      : section_id, niveau_id, cycle_id, ecole_id, pays_id → nullable
 *  - type_etablissement   : annee_scolaire_id → nullable
 *  - jours_feries         : pays_id, ecole_id → nullable
 */
return new class extends Migration
{
    /** [table, column, target_table, on_delete] */
    private array $fks = [
        ['matieres_unites', 'ecole_id', 'ecoles', 'set null'],
        ['matieres_unites', 'niveau_id', 'niveaux_etudes', 'set null'],
        ['matieres_unites', 'section_id', 'sections', 'set null'],
        ['matieres_unites', 'cycle_id', 'cycles_enseignement', 'set null'],
        ['natures_examens', 'section_id', 'sections', 'set null'],
        ['natures_examens', 'niveau_id', 'niveaux_etudes', 'set null'],
        ['natures_examens', 'cycle_id', 'cycles_enseignement', 'set null'],
        ['natures_examens', 'ecole_id', 'ecoles', 'set null'],
        ['natures_examens', 'pays_id', 'pays', 'set null'],
        ['type_etablissement', 'annee_scolaire_id', 'annees_scolaires', 'set null'],
        ['jours_feries', 'pays_id', 'pays', 'set null'],
        ['jours_feries', 'ecole_id', 'ecoles', 'set null'],
    ];

    public function up(): void
    {
        foreach ($this->fks as [$table, $column, $target, $onDelete]) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }

            // Drop la FK existante si présente
            try {
                Schema::table($table, fn (Blueprint $t) => $t->dropForeign([$column]));
            } catch (\Throwable $e) {
                // FK absente, on continue
            }

            // Rendre la colonne nullable
            try {
                Schema::table($table, fn (Blueprint $t) => $t->unsignedBigInteger($column)->nullable()->change());
            } catch (\Throwable $e) {
                // Colonne déjà du bon type
            }

            // Re-créer la FK avec onDelete approprié
            if (Schema::hasTable($target)) {
                try {
                    Schema::table($table, fn (Blueprint $t) => $t->foreign($column)->references('id')->on($target)->onDelete($onDelete));
                } catch (\Throwable $e) {
                    // FK déjà recréée
                }
            }
        }
    }

    public function down(): void
    {
        // Pas de rollback : on garde nullable.
    }
};
