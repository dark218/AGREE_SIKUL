<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ecoles')) {
            return;
        }

        Schema::table('ecoles', function (Blueprint $table) {
            // Informations de base supplémentaires
            if (!Schema::hasColumn('ecoles', 'sigle')) {
                $table->string('sigle', 50)->nullable()->after('nom');
            }
            if (!Schema::hasColumn('ecoles', 'devise_slogan')) {
                $table->string('devise_slogan', 255)->nullable()->after('sigle');
            }
            if (!Schema::hasColumn('ecoles', 'type_etablissement_id')) {
                $table->unsignedBigInteger('type_etablissement_id')->nullable()->after('devise_slogan');
            }
            if (!Schema::hasColumn('ecoles', 'type_enseignement_id')) {
                $table->unsignedBigInteger('type_enseignement_id')->nullable()->after('type_etablissement_id');
            }
            if (!Schema::hasColumn('ecoles', 'type_cours_id')) {
                $table->unsignedBigInteger('type_cours_id')->nullable()->after('type_enseignement_id');
            }
            if (!Schema::hasColumn('ecoles', 'capacite_maximale')) {
                $table->unsignedInteger('capacite_maximale')->nullable()->after('capacite_totale');
            }

            // Localisation FK (les colonnes string créées par add_contact_fields_to_ecoles restent)
            if (!Schema::hasColumn('ecoles', 'quartier_id')) {
                $table->unsignedBigInteger('quartier_id')->nullable()->after('quartier');
            }
            if (!Schema::hasColumn('ecoles', 'commune_id')) {
                $table->unsignedBigInteger('commune_id')->nullable()->after('commune');
            }
            if (!Schema::hasColumn('ecoles', 'departement_id')) {
                $table->unsignedBigInteger('departement_id')->nullable()->after('departement');
            }
            if (!Schema::hasColumn('ecoles', 'region_id')) {
                $table->unsignedBigInteger('region_id')->nullable()->after('region');
            }

            // Informations complémentaires
            if (!Schema::hasColumn('ecoles', 'date_creation')) {
                $table->date('date_creation')->nullable();
            }
            if (!Schema::hasColumn('ecoles', 'numero_agrement')) {
                $table->string('numero_agrement', 100)->nullable();
            }
            if (!Schema::hasColumn('ecoles', 'ministere_tutelle')) {
                $table->string('ministere_tutelle', 255)->nullable();
            }
            if (!Schema::hasColumn('ecoles', 'section_id')) {
                $table->unsignedBigInteger('section_id')->nullable();
            }
            if (!Schema::hasColumn('ecoles', 'devise_comptabilite_id')) {
                $table->unsignedBigInteger('devise_comptabilite_id')->nullable();
            }
            if (!Schema::hasColumn('ecoles', 'logo_id')) {
                $table->unsignedBigInteger('logo_id')->nullable();
            }
        });

        Schema::table('ecoles', function (Blueprint $table) {
            $this->safeForeign($table, 'quartier_id', 'quartiers');
            $this->safeForeign($table, 'commune_id', 'communes');
            $this->safeForeign($table, 'departement_id', 'departements');
            $this->safeForeign($table, 'region_id', 'regions');
            $this->safeForeign($table, 'section_id', 'sections');
            $this->safeForeign($table, 'devise_comptabilite_id', 'devises');
            $this->safeForeign($table, 'type_etablissement_id', 'type_etablissement');
            $this->safeForeign($table, 'type_enseignement_id', 'type_enseignement');
            $this->safeForeign($table, 'type_cours_id', 'type_cours');
            // logo_id : essaie fichiers (au pluriel) puis fichier (au singulier)
            if (Schema::hasTable('fichiers')) {
                try { $table->foreign('logo_id')->references('id')->on('fichiers')->nullOnDelete(); } catch (\Throwable $e) {}
            } elseif (Schema::hasTable('fichier')) {
                try { $table->foreign('logo_id')->references('id')->on('fichier')->nullOnDelete(); } catch (\Throwable $e) {}
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ecoles')) {
            return;
        }

        Schema::table('ecoles', function (Blueprint $table) {
            $fks = ['quartier_id','commune_id','departement_id','region_id','section_id','devise_comptabilite_id','type_etablissement_id','type_enseignement_id','type_cours_id','logo_id'];
            foreach ($fks as $col) {
                try { $table->dropForeign([$col]); } catch (\Throwable $e) {}
            }
            $cols = ['sigle','devise_slogan','type_etablissement_id','type_enseignement_id','type_cours_id','capacite_maximale','quartier_id','commune_id','departement_id','region_id','date_creation','numero_agrement','ministere_tutelle','section_id','devise_comptabilite_id','logo_id'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('ecoles', $col)) {
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
