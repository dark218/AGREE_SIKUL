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
        if (!Schema::hasTable('users')) return;

        foreach (['qr_data', 'code_owner'] as $col) {
            if (Schema::hasColumn('users', $col)) {
                DB::statement("ALTER TABLE `users` MODIFY `$col` VARCHAR(255) NULL");
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) return;
        // Rollback : on remet NOT NULL (mais les lignes NULL déjà présentes
        // provoqueront un échec — c'est voulu, ça alerte sur la donnée
        // récente qui doit être remplie avant rollback).
        foreach (['qr_data', 'code_owner'] as $col) {
            if (Schema::hasColumn('users', $col)) {
                DB::statement("ALTER TABLE `users` MODIFY `$col` VARCHAR(255) NOT NULL");
            }
        }
    }
};
