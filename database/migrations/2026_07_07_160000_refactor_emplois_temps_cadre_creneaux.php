<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Emploi du temps — refonte "propre" :
 *   emplois_temps = le CADRE (1 ligne = 1 emploi du temps d'une classe pour une période)
 *   emploi_temps_creneaux = les CRÉNEAUX (grille jour × heure : matière + enseignant + salle)
 *
 * On ajoute au cadre : periode_id, niveau_id, etat (actif/inactif).
 * On assouplit les colonnes NOT NULL héritées pour ne pas bloquer un cadre sans
 * ancien champ "slot". Les colonnes slot héritées (jour/matiere_id/…) restent
 * en place (legacy) mais ne sont plus utilisées par le nouveau formulaire.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('emplois_temps')) {
            Schema::table('emplois_temps', function (Blueprint $table) {
                if (!Schema::hasColumn('emplois_temps', 'periode_id')) {
                    $table->unsignedBigInteger('periode_id')->nullable()->after('annee_scolaire_id');
                }
                if (!Schema::hasColumn('emplois_temps', 'niveau_id')) {
                    $table->unsignedBigInteger('niveau_id')->nullable()->after('classe_id');
                }
                if (!Schema::hasColumn('emplois_temps', 'etat')) {
                    $table->enum('etat', ['actif', 'inactif'])->default('actif')->after('statut');
                }
            });

            // Assouplir les NOT NULL hérités (SQL brut pour éviter doctrine/dbal).
            try { DB::statement("ALTER TABLE `emplois_temps` MODIFY `date_debut` DATETIME NULL"); } catch (\Throwable $e) {}
            try { DB::statement("ALTER TABLE `emplois_temps` MODIFY `date_fin` DATETIME NULL"); } catch (\Throwable $e) {}
            try { DB::statement("ALTER TABLE `emplois_temps` MODIFY `statut` VARCHAR(30) NOT NULL DEFAULT 'valide'"); } catch (\Throwable $e) {}
            try { DB::statement("ALTER TABLE `emplois_temps` MODIFY `est_valide` TINYINT(1) NOT NULL DEFAULT 0"); } catch (\Throwable $e) {}

            // FKs (défensif)
            try {
                Schema::table('emplois_temps', function (Blueprint $table) {
                    if (Schema::hasTable('periodes_colaires')) {
                        $table->foreign('periode_id')->references('id')->on('periodes_colaires')->nullOnDelete();
                    }
                });
            } catch (\Throwable $e) {}
            try {
                Schema::table('emplois_temps', function (Blueprint $table) {
                    if (Schema::hasTable('niveaux_etudes')) {
                        $table->foreign('niveau_id')->references('id')->on('niveaux_etudes')->nullOnDelete();
                    }
                });
            } catch (\Throwable $e) {}
        }

        if (!Schema::hasTable('emploi_temps_creneaux')) {
            Schema::create('emploi_temps_creneaux', function (Blueprint $table) {
                $table->id();
                $table->foreignId('emploi_temps_id')->constrained('emplois_temps')->cascadeOnDelete();
                $table->string('jour', 20)->nullable();            // lundi..dimanche
                $table->time('heure_debut')->nullable();
                $table->time('heure_fin')->nullable();
                $table->unsignedBigInteger('matiere_id')->nullable();
                $table->unsignedBigInteger('enseignant_id')->nullable();
                $table->string('salle', 125)->nullable();
                $table->unsignedInteger('ordre')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['emploi_temps_id', 'jour']);
                $table->index('enseignant_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('emploi_temps_creneaux');

        if (Schema::hasTable('emplois_temps')) {
            Schema::table('emplois_temps', function (Blueprint $table) {
                foreach (['periode_id', 'niveau_id', 'etat'] as $col) {
                    if (Schema::hasColumn('emplois_temps', $col)) {
                        try { $table->dropForeign([$col]); } catch (\Throwable $e) {}
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
