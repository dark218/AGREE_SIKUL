<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rend `pays_id` nullable sur `cycles_enseignement`.
 * Le formulaire Cycle ne pilote plus le pays (info portée par l'utilisateur),
 * mais la colonne reste pour préserver les données legacy SmilPay.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cycles_enseignement') || !Schema::hasColumn('cycles_enseignement', 'pays_id')) {
            return;
        }
        DB::statement('ALTER TABLE `cycles_enseignement` MODIFY `pays_id` BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('cycles_enseignement') || !Schema::hasColumn('cycles_enseignement', 'pays_id')) {
            return;
        }
        DB::statement('ALTER TABLE `cycles_enseignement` MODIFY `pays_id` BIGINT UNSIGNED NOT NULL');
    }
};
