<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * type_examens : niveau_id / cycle_id / pays_id étaient NOT NULL.
 * Le form simplifié n'envoie plus ces FK obligatoirement → INSERT plante.
 * On rend nullable + onDelete = nullOnDelete pour cohérence.
 */
return new class extends Migration
{
    private array $fks = [
        ['type_examens', 'niveau_id', 'niveaux_etudes'],
        ['type_examens', 'cycle_id', 'cycles_enseignement'],
        ['type_examens', 'pays_id', 'pays'],
    ];

    public function up(): void
    {
        // idempotence guard
        foreach ($this->fks as [$table, $column, $target]) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }

            try {
                Schema::table($table, fn (Blueprint $t) => $t->dropForeign([$column]));
            } catch (\Throwable $e) {}

            try {
                Schema::table($table, fn (Blueprint $t) => $t->unsignedBigInteger($column)->nullable()->change());
            } catch (\Throwable $e) {}

            if (Schema::hasTable($target)) {
                try {
                    Schema::table($table, fn (Blueprint $t) => $t->foreign($column)->references('id')->on($target)->nullOnDelete());
                } catch (\Throwable $e) {}
            }
        }
    }

    public function down(): void {}
};
