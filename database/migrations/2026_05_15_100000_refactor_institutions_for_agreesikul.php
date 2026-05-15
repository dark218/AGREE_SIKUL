<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('institutions')) {
            return;
        }

        Schema::table('institutions', function (Blueprint $table) {
            // Localisation FK (nouveau modèle — les anciennes colonnes string restent intactes en legacy)
            if (!Schema::hasColumn('institutions', 'quartier_id')) {
                $table->unsignedBigInteger('quartier_id')->nullable()->after('quartier');
            }
            if (!Schema::hasColumn('institutions', 'commune_id')) {
                $table->unsignedBigInteger('commune_id')->nullable()->after('commune');
            }
            if (!Schema::hasColumn('institutions', 'departement_id')) {
                $table->unsignedBigInteger('departement_id')->nullable()->after('departement');
            }
            if (!Schema::hasColumn('institutions', 'region_id')) {
                $table->unsignedBigInteger('region_id')->nullable()->after('region');
            }

            // Numéros d'agrément supplémentaires
            if (!Schema::hasColumn('institutions', 'numero_agrement_2')) {
                $table->string('numero_agrement_2', 100)->nullable()->after('numero_autorisation');
            }
            if (!Schema::hasColumn('institutions', 'numero_agrement_3')) {
                $table->string('numero_agrement_3', 100)->nullable()->after('numero_agrement_2');
            }
            if (!Schema::hasColumn('institutions', 'numero_agrement_4')) {
                $table->string('numero_agrement_4', 100)->nullable()->after('numero_agrement_3');
            }

            // Bloc Dirigeants — promoteur / gerant (champs libres)
            if (!Schema::hasColumn('institutions', 'promoteur')) {
                $table->string('promoteur', 255)->nullable()->after('directeur_general_id');
            }
            if (!Schema::hasColumn('institutions', 'gerant')) {
                $table->string('gerant', 255)->nullable()->after('promoteur');
            }

            // Devise slogan libre (ex: "Discipline - Travail - Succès")
            // L'ancienne colonne devise_principale (varchar 3 USD/EUR/XOF...) reste en legacy.
            if (!Schema::hasColumn('institutions', 'devise_slogan')) {
                $table->string('devise_slogan', 255)->nullable()->after('devise_principale');
            }

            // Devise de tenue de la comptabilité (FK vers devises)
            if (!Schema::hasColumn('institutions', 'devise_comptabilite_id')) {
                $table->unsignedBigInteger('devise_comptabilite_id')->nullable()->after('devise_slogan');
            }
        });

        // Foreign keys séparées pour éviter conflits
        Schema::table('institutions', function (Blueprint $table) {
            $this->safeForeign($table, 'quartier_id', 'quartiers');
            $this->safeForeign($table, 'commune_id', 'communes');
            $this->safeForeign($table, 'departement_id', 'departements');
            $this->safeForeign($table, 'region_id', 'regions');
            $this->safeForeign($table, 'devise_comptabilite_id', 'devises');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('institutions')) {
            return;
        }

        Schema::table('institutions', function (Blueprint $table) {
            foreach (['quartier_id', 'commune_id', 'departement_id', 'region_id', 'devise_comptabilite_id'] as $col) {
                try { $table->dropForeign([$col]); } catch (\Throwable $e) {}
            }
            $cols = ['quartier_id','commune_id','departement_id','region_id','numero_agrement_2','numero_agrement_3','numero_agrement_4','promoteur','gerant','devise_slogan','devise_comptabilite_id'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('institutions', $col)) {
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
        } catch (\Throwable $e) {
            // FK déjà existante ou conflit, on ignore
        }
    }
};
