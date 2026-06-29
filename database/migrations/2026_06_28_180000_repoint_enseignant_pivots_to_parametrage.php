<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les pivots enseignant_matieres et enseignant_niveaux référençaient les
 * anciennes tables `matieres` et `niveaux`. Or l'application utilise
 * maintenant `matieres_unites` (Paramétrage > Matière Unité) et
 * `niveaux_etudes` (Paramétrage > Niveau Etude) pour alimenter les listes.
 *
 * Cette migration :
 *  1. supprime les FK obsolètes vers `matieres` et `niveaux`
 *  2. ajoute les nouvelles FK vers `matieres_unites` et `niveaux_etudes`
 *     (sans CASCADE pour éviter de tout perdre si on supprime une matière)
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->dropForeignIfExists('enseignant_matieres', 'enseignant_matieres_matiere_id_foreign');
        $this->dropForeignIfExists('enseignant_niveaux', 'enseignant_niveaux_niveau_id_foreign');

        if (Schema::hasTable('enseignant_matieres') && Schema::hasTable('matieres_unites')) {
            // Nettoyer les orphelins avant d'ajouter la nouvelle FK
            DB::statement('
                DELETE em FROM enseignant_matieres em
                LEFT JOIN matieres_unites mu ON em.matiere_id = mu.id
                WHERE mu.id IS NULL
            ');

            if (!$this->foreignExists('enseignant_matieres', 'enseignant_matieres_matiere_id_foreign')) {
                Schema::table('enseignant_matieres', function (Blueprint $table) {
                    $table->foreign('matiere_id', 'enseignant_matieres_matiere_id_foreign')
                        ->references('id')->on('matieres_unites')
                        ->onDelete('cascade');
                });
            }
        }

        if (Schema::hasTable('enseignant_niveaux') && Schema::hasTable('niveaux_etudes')) {
            DB::statement('
                DELETE en FROM enseignant_niveaux en
                LEFT JOIN niveaux_etudes ne ON en.niveau_id = ne.id
                WHERE ne.id IS NULL
            ');

            if (!$this->foreignExists('enseignant_niveaux', 'enseignant_niveaux_niveau_id_foreign')) {
                Schema::table('enseignant_niveaux', function (Blueprint $table) {
                    $table->foreign('niveau_id', 'enseignant_niveaux_niveau_id_foreign')
                        ->references('id')->on('niveaux_etudes')
                        ->onDelete('cascade');
                });
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        // Rollback : on remet les FK d'origine vers matieres / niveaux
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->dropForeignIfExists('enseignant_matieres', 'enseignant_matieres_matiere_id_foreign');
        $this->dropForeignIfExists('enseignant_niveaux', 'enseignant_niveaux_niveau_id_foreign');

        if (Schema::hasTable('enseignant_matieres') && Schema::hasTable('matieres')) {
            Schema::table('enseignant_matieres', function (Blueprint $table) {
                $table->foreign('matiere_id', 'enseignant_matieres_matiere_id_foreign')
                    ->references('id')->on('matieres')->onDelete('cascade');
            });
        }
        if (Schema::hasTable('enseignant_niveaux') && Schema::hasTable('niveaux')) {
            Schema::table('enseignant_niveaux', function (Blueprint $table) {
                $table->foreign('niveau_id', 'enseignant_niveaux_niveau_id_foreign')
                    ->references('id')->on('niveaux')->onDelete('cascade');
            });
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function dropForeignIfExists(string $table, string $constraint): void
    {
        if ($this->foreignExists($table, $constraint)) {
            Schema::table($table, function (Blueprint $t) use ($constraint) {
                $t->dropForeign($constraint);
            });
        }
    }

    private function foreignExists(string $table, string $constraint): bool
    {
        $db = DB::getDatabaseName();
        return (bool) DB::selectOne('
            SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = "FOREIGN KEY"
            LIMIT 1
        ', [$db, $table, $constraint]);
    }
};
