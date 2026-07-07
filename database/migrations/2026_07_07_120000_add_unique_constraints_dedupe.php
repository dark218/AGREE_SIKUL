<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les contraintes d'unicité manquantes identifiées dans l'audit §10.1.
 *
 * Contexte :
 *   - `bulletins` : aucun UNIQUE, race conditions possibles → 2 bulletins
 *     pour le même apprenant/classe/période/année en saisie concurrente
 *   - `presences` : aucun UNIQUE, 5 présences pour la même séance possibles
 *   - `users` : email/login/numero_piece sans UNIQUE → duplicatas silencieux
 *
 * Idempotente : chaque index est ajouté sous condition d'absence, avec dédup
 * préalable pour ne pas crasher sur des doublons existants.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // ============================================================
        // 1. bulletins — UNIQUE (apprenant_id, classe_id, periode, annee_scolaire_id)
        // ============================================================
        if (Schema::hasTable('bulletins') && !$this->indexExists('bulletins', 'bulletins_unique_scolarite')) {
            // Dédup préalable : garde le premier de chaque groupe.
            DB::statement("
                DELETE b1 FROM bulletins b1
                INNER JOIN bulletins b2
                    ON b1.apprenant_id = b2.apprenant_id
                   AND b1.classe_id = b2.classe_id
                   AND b1.periode = b2.periode
                   AND b1.annee_scolaire_id = b2.annee_scolaire_id
                   AND b1.id > b2.id
            ");
            Schema::table('bulletins', function (Blueprint $t) {
                $t->unique(
                    ['apprenant_id', 'classe_id', 'periode', 'annee_scolaire_id'],
                    'bulletins_unique_scolarite'
                );
            });
        }

        // ============================================================
        // 2. presences — UNIQUE (apprenant_id, seance_id)
        // ============================================================
        if (Schema::hasTable('presences') && !$this->indexExists('presences', 'presences_unique_apprenant_seance')) {
            DB::statement("
                DELETE p1 FROM presences p1
                INNER JOIN presences p2
                    ON p1.apprenant_id = p2.apprenant_id
                   AND p1.seance_id = p2.seance_id
                   AND p1.id > p2.id
            ");
            Schema::table('presences', function (Blueprint $t) {
                $t->unique(['apprenant_id', 'seance_id'], 'presences_unique_apprenant_seance');
            });
        }

        // ============================================================
        // 3. users — UNIQUE email (partiel : NULL admis)
        // ============================================================
        if (Schema::hasTable('users')) {
            // email : ignoré si NULL/vide.
            if (!$this->indexExists('users', 'users_email_unique_notnull')) {
                DB::statement("
                    DELETE u1 FROM users u1
                    INNER JOIN users u2
                        ON u1.email = u2.email
                       AND u1.email IS NOT NULL AND u1.email != ''
                       AND u1.id > u2.id
                ");
                // Index conditionnel via une colonne générée n'est pas dispo
                // en MySQL <8.0 pour tous les setups → on met un index simple
                // et on filtre les NULL via l'application. Le UNIQUE MySQL
                // ignore par défaut les NULL (multi-null OK), ce qui suffit.
                try {
                    Schema::table('users', function (Blueprint $t) {
                        $t->unique('email', 'users_email_unique_notnull');
                    });
                } catch (\Throwable $e) {
                    // ignore si déjà existant sous un autre nom
                }
            }

            // numero_piece : ignoré si NULL.
            if (Schema::hasColumn('users', 'numero_piece')
                && !$this->indexExists('users', 'users_numero_piece_unique')) {
                DB::statement("
                    DELETE u1 FROM users u1
                    INNER JOIN users u2
                        ON u1.numero_piece = u2.numero_piece
                       AND u1.numero_piece IS NOT NULL AND u1.numero_piece != ''
                       AND u1.id > u2.id
                ");
                try {
                    Schema::table('users', function (Blueprint $t) {
                        $t->unique('numero_piece', 'users_numero_piece_unique');
                    });
                } catch (\Throwable $e) {}
            }
            // login est déjà UNIQUE (voir DESC users : login MUL/UNI).
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        Schema::table('bulletins', function (Blueprint $t) {
            if ($this->indexExists('bulletins', 'bulletins_unique_scolarite')) {
                $t->dropUnique('bulletins_unique_scolarite');
            }
        });
        Schema::table('presences', function (Blueprint $t) {
            if ($this->indexExists('presences', 'presences_unique_apprenant_seance')) {
                $t->dropUnique('presences_unique_apprenant_seance');
            }
        });
        Schema::table('users', function (Blueprint $t) {
            if ($this->indexExists('users', 'users_email_unique_notnull')) {
                $t->dropUnique('users_email_unique_notnull');
            }
            if ($this->indexExists('users', 'users_numero_piece_unique')) {
                $t->dropUnique('users_numero_piece_unique');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = DB::connection()->getDatabaseName();
        $rows = DB::select(
            "SELECT COUNT(*) AS c
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND INDEX_NAME = ?",
            [$connection, $table, $indexName]
        );
        return (int) ($rows[0]->c ?? 0) > 0;
    }
};
