<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statistiques_classes', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn(['nombre_enseignants', 'nombre_enseignants_permanent', 'nombre_enseignants_vacataires', 'produits_ecole', 'services_offerts']);
            
            // Add annee_scolaire_id
            if (!Schema::hasColumn('statistiques_classes', 'annee_scolaire_id')) {
                $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->onDelete('set null');
            }
            
            // Rename columns
            if (Schema::hasColumn('statistiques_classes', 'nombre_inscrits')) {
                $table->renameColumn('nombre_inscrits', 'effectif_total');
            }
            
            // Add new columns for Classes
            if (!Schema::hasColumn('statistiques_classes', 'total_frais_scolarite')) {
                $table->decimal('total_frais_scolarite', 15, 2)->nullable();
                $table->decimal('total_scolarite_paye', 15, 2)->nullable();
                $table->decimal('total_scolarite_non_paye', 15, 2)->nullable();
                $table->decimal('total_achats_depenses', 15, 2)->nullable();
                $table->decimal('total_salaires', 15, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('statistiques_classes', function (Blueprint $table) {
            if (Schema::hasColumn('statistiques_classes', 'annee_scolaire_id')) {
                $table->dropForeign(['annee_scolaire_id']);
                $table->dropColumn('annee_scolaire_id');
            }
            if (Schema::hasColumn('statistiques_classes', 'effectif_total')) {
                $table->renameColumn('effectif_total', 'nombre_inscrits');
            }
            if (Schema::hasColumn('statistiques_classes', 'total_frais_scolarite')) {
                $table->dropColumn(['total_frais_scolarite', 'total_scolarite_paye', 'total_scolarite_non_paye', 'total_achats_depenses', 'total_salaires']);
            }
            $table->integer('nombre_enseignants')->nullable();
            $table->integer('nombre_enseignants_permanent')->nullable();
            $table->integer('nombre_enseignants_vacataires')->nullable();
            $table->text('produits_ecole')->nullable();
            $table->text('services_offerts')->nullable();
        });
    }
};
