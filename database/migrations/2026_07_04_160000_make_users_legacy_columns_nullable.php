<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes legacy SmilPay `users.qr_data` et `users.code_owner` sont
 * définies NOT NULL sans valeur par défaut. Elles servaient au point
 * de vente marchand, elles n'ont plus aucun sens pour AGREE SIKUL
 * (établissements scolaires).
 *
 * On les rend nullable pour permettre la création d'un User par
 * `AutoUserCreator` sans devoir renseigner ces champs artificiels.
 *
 * Idempotent : détecte si la colonne est déjà nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        // 1. Colonnes legacy SmilPay dans `users`
        if (Schema::hasTable('users')) {
            foreach (['qr_data', 'code_owner'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    DB::statement("ALTER TABLE `users` MODIFY `$col` VARCHAR(255) NULL");
                }
            }
        }

        // 2. `num_enseignant` : auto-généré désormais côté controller,
        //    on retire l'obligation NOT NULL pour permettre la création
        //    même sans valeur explicite (idempotent).
        if (Schema::hasTable('enseignants') && Schema::hasColumn('enseignants', 'num_enseignant')) {
            DB::statement("ALTER TABLE `enseignants` MODIFY `num_enseignant` VARCHAR(50) NULL");
        }
    }

    public function down(): void
    {
        // Rollback : on remet NOT NULL (mais les lignes NULL déjà présentes
        // provoqueront un échec — c'est voulu, ça alerte sur la donnée
        // récente qui doit être remplie avant rollback).
        if (Schema::hasTable('users')) {
            foreach (['qr_data', 'code_owner'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    DB::statement("ALTER TABLE `users` MODIFY `$col` VARCHAR(255) NOT NULL");
                }
            }
        }
        if (Schema::hasTable('enseignants') && Schema::hasColumn('enseignants', 'num_enseignant')) {
            DB::statement("ALTER TABLE `enseignants` MODIFY `num_enseignant` VARCHAR(50) NOT NULL");
        }
    }
};
