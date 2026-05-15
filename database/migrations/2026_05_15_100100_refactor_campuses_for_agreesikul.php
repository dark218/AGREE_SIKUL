<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('campuses')) {
            return;
        }

        Schema::table('campuses', function (Blueprint $table) {
            // Localisation FK (les anciennes colonnes string restent intactes)
            if (!Schema::hasColumn('campuses', 'quartier_id')) {
                $table->unsignedBigInteger('quartier_id')->nullable()->after('quartier');
            }
            if (!Schema::hasColumn('campuses', 'commune_id')) {
                $table->unsignedBigInteger('commune_id')->nullable()->after('commune');
            }
            if (!Schema::hasColumn('campuses', 'departement_id')) {
                $table->unsignedBigInteger('departement_id')->nullable()->after('departement');
            }
            if (!Schema::hasColumn('campuses', 'region_id')) {
                $table->unsignedBigInteger('region_id')->nullable()->after('region');
            }
            // Statut de disponibilité (différent du statut actif/non_actif)
            if (!Schema::hasColumn('campuses', 'statut_disponibilite')) {
                $table->string('statut_disponibilite', 50)->nullable()->after('statut');
            }
        });

        Schema::table('campuses', function (Blueprint $table) {
            $this->safeForeign($table, 'quartier_id', 'quartiers');
            $this->safeForeign($table, 'commune_id', 'communes');
            $this->safeForeign($table, 'departement_id', 'departements');
            $this->safeForeign($table, 'region_id', 'regions');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('campuses')) {
            return;
        }

        Schema::table('campuses', function (Blueprint $table) {
            foreach (['quartier_id', 'commune_id', 'departement_id', 'region_id'] as $col) {
                try { $table->dropForeign([$col]); } catch (\Throwable $e) {}
            }
            foreach (['quartier_id','commune_id','departement_id','region_id','statut_disponibilite'] as $col) {
                if (Schema::hasColumn('campuses', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function safeForeign(Blueprint $table, string $col, string $refTable): void
    {
        if (!Schema::hasTable($refTable)) return;
        try {
            $table->foreign($col)->references('id')->on($refTable)->nullOnDelete();
        } catch (\Throwable $e) {}
    }
};
