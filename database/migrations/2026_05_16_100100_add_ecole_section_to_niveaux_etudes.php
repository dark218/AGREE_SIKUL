<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('niveaux_etudes')) {
            return;
        }

        // 1) Ajout colonnes nullable (sans FK)
        Schema::table('niveaux_etudes', function (Blueprint $table) {
            if (!Schema::hasColumn('niveaux_etudes', 'ecole_id')) {
                $table->unsignedBigInteger('ecole_id')->nullable()->after('libelle');
            }
            if (!Schema::hasColumn('niveaux_etudes', 'section_id')) {
                $table->unsignedBigInteger('section_id')->nullable()->after('ecole_id');
            }
        });

        // 2) Nettoie les FK orphelines existantes (notamment pays_id)
        $this->cleanOrphans('niveaux_etudes', [
            'pays_id' => 'pays',
            'cycle_id' => 'cycles_enseignement',
            'annee_scolaire_id' => 'annees_scolaires',
        ]);

        // 3) Ajoute les FK une par une, seulement si manquantes
        $this->addForeignIfMissing('niveaux_etudes', 'ecole_id', 'ecoles');
        $this->addForeignIfMissing('niveaux_etudes', 'section_id', 'sections');
    }

    public function down(): void
    {
        if (!Schema::hasTable('niveaux_etudes')) {
            return;
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            Schema::table('niveaux_etudes', function (Blueprint $table) {
                try { $table->dropForeign(['ecole_id']); } catch (\Throwable $e) {}
                try { $table->dropForeign(['section_id']); } catch (\Throwable $e) {}
                foreach (['ecole_id', 'section_id'] as $col) {
                    if (Schema::hasColumn('niveaux_etudes', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

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
            // ignore
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

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
