<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Corrige 21 FK héritées qui pointaient à tort sur `users` au lieu de
 * leur vraie table cible (ecoles, apprenants, enseignants, annees_scolaires).
 *
 * Le bug venait des migrations de création du 9 février : à cette date, les
 * tables `ecoles`, `apprenants`, `enseignants` n'existaient pas encore (créées
 * le 10 février), donc `->on('users')` a été utilisé comme placeholder et
 * jamais corrigé.
 *
 * `matieres.ecole_id` est déjà corrigée par 2026_03_24_000001 ; pas incluse ici.
 */
return new class extends Migration
{
    /**
     * [table, colonne, table_cible_correcte]
     */
    private array $fixes = [
        ['cours', 'enseignant_id', 'enseignants'],
        ['cours', 'annee_scolaire_id', 'annees_scolaires'],
        ['absences_apprenants', 'apprenant_id', 'apprenants'],
        ['notes', 'apprenant_id', 'apprenants'],
        ['bulletins', 'apprenant_id', 'apprenants'],
        ['bulletins', 'annee_scolaire_id', 'annees_scolaires'],
        ['rendus_devoirs', 'apprenant_id', 'apprenants'],
        ['presences_seances', 'apprenant_id', 'apprenants'],
        ['emplois_temps', 'annee_scolaire_id', 'annees_scolaires'],
        ['frais', 'apprenant_id', 'apprenants'],
        ['frais', 'annee_scolaire_id', 'annees_scolaires'],
        ['paiements', 'apprenant_id', 'apprenants'],
        ['depenses', 'ecole_id', 'ecoles'],
        ['services_cantines', 'ecole_id', 'ecoles'],
        ['inscriptions_cantines', 'apprenant_id', 'apprenants'],
        ['inscriptions_cantines', 'annee_scolaire_id', 'annees_scolaires'],
        ['services_transports', 'ecole_id', 'ecoles'],
        ['inscriptions_transports', 'apprenant_id', 'apprenants'],
        ['inscriptions_transports', 'annee_scolaire_id', 'annees_scolaires'],
        ['consultations_infirmeries', 'apprenant_id', 'apprenants'],
        ['equipements', 'ecole_id', 'ecoles'],
    ];

    public function up(): void
    {
        // idempotence guard
        foreach ($this->fixes as [$table, $column, $target]) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }
            if (!Schema::hasTable($target)) {
                continue;
            }

            $this->dropForeignIfExists($table, $column);

            Schema::table($table, function (Blueprint $t) use ($column) {
                $t->unsignedBigInteger($column)->nullable()->change();
            });

            DB::table($table)
                ->whereNotNull($column)
                ->whereNotIn($column, DB::table($target)->pluck('id'))
                ->update([$column => null]);

            Schema::table($table, function (Blueprint $t) use ($column, $target) {
                $t->foreign($column)->references('id')->on($target)->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Irréversible volontairement : restaurer les FK cassées vers `users`
        // n'a aucun intérêt métier et re-contaminerait le schéma.
    }

    private function dropForeignIfExists(string $table, string $column): void
    {
        $constraintName = "{$table}_{$column}_foreign";

        $exists = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1',
            [$table, $column]
        );

        if (!$exists) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($exists) {
            $t->dropForeign($exists->CONSTRAINT_NAME);
        });
    }
};
