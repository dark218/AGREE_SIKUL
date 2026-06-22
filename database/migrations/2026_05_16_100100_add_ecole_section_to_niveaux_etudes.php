<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('niveaux_etudes')) {
            return;
        }
        Schema::table('niveaux_etudes', function (Blueprint $table) {
            if (!Schema::hasColumn('niveaux_etudes', 'ecole_id')) {
                $table->unsignedBigInteger('ecole_id')->nullable()->after('libelle');
            }
            if (!Schema::hasColumn('niveaux_etudes', 'section_id')) {
                $table->unsignedBigInteger('section_id')->nullable()->after('ecole_id');
            }
        });
        Schema::table('niveaux_etudes', function (Blueprint $table) {
            if (Schema::hasTable('ecoles')) {
                try { $table->foreign('ecole_id')->references('id')->on('ecoles')->nullOnDelete(); } catch (\Throwable $e) {}
            }
            if (Schema::hasTable('sections')) {
                try { $table->foreign('section_id')->references('id')->on('sections')->nullOnDelete(); } catch (\Throwable $e) {}
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('niveaux_etudes')) {
            return;
        }
        Schema::table('niveaux_etudes', function (Blueprint $table) {
            try { $table->dropForeign(['ecole_id']); } catch (\Throwable $e) {}
            try { $table->dropForeign(['section_id']); } catch (\Throwable $e) {}
            foreach (['ecole_id', 'section_id'] as $col) {
                if (Schema::hasColumn('niveaux_etudes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
