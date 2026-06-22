<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── matieres_unites ──
        if (Schema::hasTable('matieres_unites')) {
            Schema::table('matieres_unites', function (Blueprint $table) {
                if (!Schema::hasColumn('matieres_unites', 'institution_id')) {
                    $table->unsignedBigInteger('institution_id')->nullable()->after('ecole_id');
                }
            });

            $this->cleanOrphans('matieres_unites', [
                'ecole_id' => 'ecoles',
                'niveau_id' => 'niveaux_etudes',
                'section_id' => 'sections',
                'cycle_id' => 'cycles_enseignement',
            ]);

            $this->addForeignIfMissing('matieres_unites', 'institution_id', 'institutions');
        }

        // ── groupes_matieres ──
        if (Schema::hasTable('groupes_matieres')) {
            Schema::table('groupes_matieres', function (Blueprint $table) {
                if (!Schema::hasColumn('groupes_matieres', 'institution_id')) {
                    $table->unsignedBigInteger('institution_id')->nullable();
                }
                if (!Schema::hasColumn('groupes_matieres', 'ecole_id')) {
                    $table->unsignedBigInteger('ecole_id')->nullable();
                }
                if (!Schema::hasColumn('groupes_matieres', 'section_id')) {
                    $table->unsignedBigInteger('section_id')->nullable();
                }
            });

            $this->cleanOrphans('groupes_matieres', [
                'niveau_id' => 'niveaux_etudes',
                'cycle_id' => 'cycles_enseignement',
                'pays_id' => 'pays',
                'annee_scolaire_id' => 'annees_scolaires',
            ]);

            $this->addForeignIfMissing('groupes_matieres', 'institution_id', 'institutions');
            $this->addForeignIfMissing('groupes_matieres', 'ecole_id', 'ecoles');
            $this->addForeignIfMissing('groupes_matieres', 'section_id', 'sections');
        }
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach (['matieres_unites', 'groupes_matieres'] as $table) {
                if (!Schema::hasTable($table)) continue;
                Schema::table($table, function (Blueprint $t) use ($table) {
                    foreach (['institution_id', 'ecole_id', 'section_id'] as $col) {
                        if (Schema::hasColumn($table, $col)) {
                            try { $t->dropForeign([$col]); } catch (\Throwable $e) {}
                        }
                    }
                    if ($table === 'matieres_unites' && Schema::hasColumn($table, 'institution_id')) {
                        $t->dropColumn('institution_id');
                    }
                    if ($table === 'groupes_matieres') {
                        foreach (['institution_id', 'ecole_id', 'section_id'] as $col) {
                            if (Schema::hasColumn($table, $col)) {
                                $t->dropColumn($col);
                            }
                        }
                    }
                });
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Ajoute une FK uniquement si elle n'existe pas déjà (vérifié via information_schema).
     */
    private function addForeignIfMissing(string $table, string $column, string $refTable): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column) || !Schema::hasTable($refTable)) {
            return;
        }
        if ($this->fkExists($table, $column)) {
            return;
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            Schema::table($table, function (Blueprint $t) use ($column, $refTable) {
                $t->foreign($column)->references('id')->on($refTable)->nullOnDelete();
            });
        } catch (\Throwable $e) {
            // log silencieusement, on continue avec les autres FK
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Détecte si une FK existe déjà sur (table, colonne) dans la DB courante.
     */
    private function fkExists(string $table, string $column): bool
    {
        try {
            $database = DB::connection()->getDatabaseName();
            $count = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', $column)
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->count();
            return $count > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Met à NULL les colonnes FK pointant vers des lignes inexistantes,
     * pour éviter qu'un ALTER TABLE échoue lors du rebuild des contraintes.
     */
    private function cleanOrphans(string $table, array $fkMap): void
    {
        foreach ($fkMap as $column => $refTable) {
            if (!Schema::hasColumn($table, $column) || !Schema::hasTable($refTable)) {
                continue;
            }
            try {
                DB::statement("UPDATE {$table} t LEFT JOIN {$refTable} r ON r.id = t.{$column} SET t.{$column} = NULL WHERE r.id IS NULL AND t.{$column} IS NOT NULL");
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }
};
