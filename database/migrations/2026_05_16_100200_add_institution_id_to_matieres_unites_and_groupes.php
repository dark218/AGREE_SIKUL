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

            $this->cleanOrphans('matieres_unites', ['ecole_id' => 'ecoles', 'niveau_id' => 'niveaux_etudes', 'section_id' => 'sections', 'cycle_id' => 'cycles_enseignement']);

            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            try {
                Schema::table('matieres_unites', function (Blueprint $table) {
                    if (Schema::hasTable('institutions')) {
                        try { $table->foreign('institution_id')->references('id')->on('institutions')->nullOnDelete(); } catch (\Throwable $e) {}
                    }
                });
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
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

            $this->cleanOrphans('groupes_matieres', ['niveau_id' => 'niveaux_etudes', 'cycle_id' => 'cycles_enseignement', 'pays_id' => 'pays', 'annee_scolaire_id' => 'annees_scolaires']);

            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            try {
                Schema::table('groupes_matieres', function (Blueprint $table) {
                    if (Schema::hasTable('institutions')) {
                        try { $table->foreign('institution_id')->references('id')->on('institutions')->nullOnDelete(); } catch (\Throwable $e) {}
                    }
                    if (Schema::hasTable('ecoles')) {
                        try { $table->foreign('ecole_id')->references('id')->on('ecoles')->nullOnDelete(); } catch (\Throwable $e) {}
                    }
                    if (Schema::hasTable('sections')) {
                        try { $table->foreign('section_id')->references('id')->on('sections')->nullOnDelete(); } catch (\Throwable $e) {}
                    }
                });
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
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
                // ignore : si ça plante, FOREIGN_KEY_CHECKS=0 prendra le relai
            }
        }
    }
};
