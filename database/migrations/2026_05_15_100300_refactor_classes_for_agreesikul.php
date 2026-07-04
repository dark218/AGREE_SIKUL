<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (!Schema::hasTable('classes')) {
            return;
        }

        Schema::table('classes', function (Blueprint $table) {
            // Bloc Informations de base
            if (!Schema::hasColumn('classes', 'code')) {
                $table->string('code', 100)->nullable()->after('id');
            }
            if (!Schema::hasColumn('classes', 'libelle')) {
                // On garde l'ancien `nom` en legacy mais on ajoute `libelle` qui sera le nouveau standard
                $table->string('libelle', 255)->nullable()->after('nom');
            }
            // libelle_affichage existe déjà (add_extended_fields_to_classes)
            if (!Schema::hasColumn('classes', 'batiment')) {
                $table->string('batiment', 100)->nullable()->after(Schema::hasColumn('classes', 'salle') ? 'salle' : 'nom');
            }

            // Capacité actuelle
            if (!Schema::hasColumn('classes', 'capacite_actuelle')) {
                $table->unsignedInteger('capacite_actuelle')->nullable()->after('capacite_max');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('classes')) {
            return;
        }

        Schema::table('classes', function (Blueprint $table) {
            foreach (['code','libelle','batiment','capacite_actuelle'] as $col) {
                if (Schema::hasColumn('classes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
