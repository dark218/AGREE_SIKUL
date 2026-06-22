<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('matieres_unites')) {
            Schema::table('matieres_unites', function (Blueprint $table) {
                if (!Schema::hasColumn('matieres_unites', 'institution_id')) {
                    $table->unsignedBigInteger('institution_id')->nullable()->after('ecole_id');
                }
            });
            Schema::table('matieres_unites', function (Blueprint $table) {
                if (Schema::hasTable('institutions')) {
                    try { $table->foreign('institution_id')->references('id')->on('institutions')->nullOnDelete(); } catch (\Throwable $e) {}
                }
            });
        }
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
        }
    }

    public function down(): void
    {
        foreach (['matieres_unites', 'groupes_matieres'] as $table) {
            if (!Schema::hasTable($table)) continue;
            Schema::table($table, function (Blueprint $t) use ($table) {
                foreach (['institution_id', 'ecole_id', 'section_id'] as $col) {
                    if (Schema::hasColumn($table, $col)) {
                        try { $t->dropForeign([$col]); } catch (\Throwable $e) {}
                        if ($col === 'institution_id') {
                            $t->dropColumn($col);
                        }
                    }
                }
            });
        }
    }
};
