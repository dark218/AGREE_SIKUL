<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * §UX : les utilisateurs se connectent par TÉLÉPHONE (colonne `login` /
 * `full_login`), pas par email. Or la contrainte UNIQUE sur `email` bloquait
 * la création d'un profil (Parent, Tuteur, Accompagnateur, Enseignant) dès
 * que l'email était déjà utilisé par un autre profil — cas fréquent :
 * père = enseignant = tuteur d'un cousin. Le user a explicitement demandé
 * de retirer cette contrainte.
 *
 * Cette migration :
 *   - Drop l'index UNIQUE sur `users.email` (nommé `users_email_unique_notnull`)
 *   - Ajoute un index NON-UNIQUE simple sur `users.email` pour préserver les
 *     performances des `WHERE email = ?` (login form, forgot password).
 *   - N'affecte PAS les contraintes UNIQUE sur `login` et `full_login` qui
 *     restent la source d'identification.
 *
 * Idempotente : ne fait rien si l'index n'existe pas / si non-unique est déjà en place.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) return;

        // 1. Drop l'index UNIQUE sur email s'il existe (nom historique).
        $existing = $this->indexesOn('users', 'email');
        foreach ($existing as $idx) {
            // On vise tout index UNIQUE porté par la colonne email — quel que soit son nom.
            if ($idx->NON_UNIQUE == 0) {
                try {
                    DB::statement("ALTER TABLE `users` DROP INDEX `{$idx->INDEX_NAME}`");
                } catch (\Throwable $e) {
                    logger()->warning("drop_users_email_unique: DROP INDEX `{$idx->INDEX_NAME}` failed — " . $e->getMessage());
                }
            }
        }

        // 2. Ajoute un index NON-UNIQUE pour préserver les recherches par email
        //    (login form fallback, forgot password) — si pas déjà présent.
        $remaining = $this->indexesOn('users', 'email');
        $hasNonUnique = false;
        foreach ($remaining as $idx) {
            if ($idx->NON_UNIQUE == 1) { $hasNonUnique = true; break; }
        }
        if (!$hasNonUnique) {
            try {
                Schema::table('users', function ($t) {
                    $t->index('email', 'users_email_index');
                });
            } catch (\Throwable $e) {
                // Silencieux — l'index peut déjà exister sous un autre nom.
            }
        }
    }

    public function down(): void
    {
        // On ne re-recrée PAS la contrainte UNIQUE — c'est explicitement le
        // choix produit du user. Si vraiment nécessaire :
        //   ALTER TABLE `users` ADD UNIQUE `users_email_unique_notnull` (`email`);
    }

    /**
     * Retourne les index MySQL portés par une colonne donnée d'une table.
     */
    private function indexesOn(string $table, string $column): array
    {
        $db = DB::connection()->getDatabaseName();
        return DB::select(
            "SELECT INDEX_NAME, NON_UNIQUE
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
               AND INDEX_NAME != 'PRIMARY'",
            [$db, $table, $column]
        );
    }
};
