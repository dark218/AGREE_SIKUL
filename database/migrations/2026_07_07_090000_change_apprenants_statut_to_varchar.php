<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Passe `apprenants.statut` d'ENUM restrictif à VARCHAR(50).
 * Le formulaire Apprenant enregistre désormais un code du référentiel
 * `statuts_apprenants` (ex: 'STAP_01'), que l'ancien ENUM
 * ('actif','inactif','suspendu','exclus') refusait -> "Data truncated for
 * column 'statut'".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('apprenants') && Schema::hasColumn('apprenants', 'statut')) {
            DB::statement("ALTER TABLE `apprenants` MODIFY `statut` VARCHAR(50) NOT NULL DEFAULT 'actif'");
        }
    }

    public function down(): void
    {
        // Ne pas revert : possibles valeurs custom (codes référentiel) introduites entre-temps.
    }
};
