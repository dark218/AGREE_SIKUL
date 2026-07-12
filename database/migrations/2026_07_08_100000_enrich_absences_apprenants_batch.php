<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refonte « Absence Apprenant » en saisie par lot : un contexte commun
 * (année, classe, école, campus, matière, enseignant) appliqué à plusieurs
 * apprenants, + un commentaire par apprenant (onglet Justificatifs).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('absences_apprenants')) {
            return;
        }

        Schema::table('absences_apprenants', function (Blueprint $table) {
            if (!Schema::hasColumn('absences_apprenants', 'annee_scolaire_id')) {
                $table->foreignId('annee_scolaire_id')->nullable()->after('id')->constrained('annees_scolaires')->nullOnDelete();
            }
            if (!Schema::hasColumn('absences_apprenants', 'ecole_id')) {
                $table->foreignId('ecole_id')->nullable()->after('classe_id')->constrained('ecoles')->nullOnDelete();
            }
            if (!Schema::hasColumn('absences_apprenants', 'campus_id')) {
                $table->foreignId('campus_id')->nullable()->after('ecole_id')->constrained('campuses')->nullOnDelete();
            }
            if (!Schema::hasColumn('absences_apprenants', 'enseignant_id')) {
                $table->foreignId('enseignant_id')->nullable()->after('matiere_id')->constrained('enseignants')->nullOnDelete();
            }
            if (!Schema::hasColumn('absences_apprenants', 'commentaire')) {
                $table->text('commentaire')->nullable()->after('motif');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('absences_apprenants')) {
            return;
        }

        Schema::table('absences_apprenants', function (Blueprint $table) {
            foreach (['annee_scolaire_id', 'ecole_id', 'campus_id', 'enseignant_id'] as $col) {
                if (Schema::hasColumn('absences_apprenants', $col)) {
                    $table->dropConstrainedForeignId($col);
                }
            }
            if (Schema::hasColumn('absences_apprenants', 'commentaire')) {
                $table->dropColumn('commentaire');
            }
        });
    }
};
