<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le champ `users.role` était un ENUM legacy hérité de SmilPay :
 *   ('superadmin','admin','marchand','caissier','manager','client',
 *    'agent','service_client','service_validateur')
 *
 * Depuis la refonte AGREE SIKUL, on doit y stocker des rôles éducation
 * ('enseignant', 'parent', 'apprenant', 'tuteur', 'accompagnateur',
 * 'directeur', 'personnel_administratif', ...). MySQL en mode STRICT
 * tronque toute valeur hors enum → SQLSTATE[01000] warning fatal.
 *
 * Solution : passer `role` en `VARCHAR(50)` — les vrais rôles + permissions
 * sont gérés par la table `roles` de Spatie via `roles.name`.
 *
 * Cette migration est idempotente : si `role` est déjà string, ne fait rien.
 */
return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'role')) {
            return;
        }

        // Détection type actuel via information_schema
        $col = DB::selectOne("
            SELECT DATA_TYPE, COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'users'
              AND COLUMN_NAME = 'role'
        ");

        if ($col && strtolower($col->DATA_TYPE) === 'enum') {
            // On passe en VARCHAR(50) nullable — les valeurs existantes sont préservées
            DB::statement("ALTER TABLE `users` MODIFY `role` VARCHAR(50) NULL");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'role')) {
            return;
        }
        // Rollback : on remet l'ancien enum. Attention : les rôles récents
        // (enseignant, parent, etc.) seront perdus si présents.
        DB::statement("
            ALTER TABLE `users` MODIFY `role`
            ENUM('superadmin','admin','marchand','caissier','manager','client','agent','service_client','service_validateur')
            NULL
        ");
    }
};
