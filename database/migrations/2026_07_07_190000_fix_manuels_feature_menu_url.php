<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La fonctionnalité « Liste des manuels » portait menu_url = `manuels`, alors que
 * ses routes sont `academique.listes-manuels.*` (et le mapping front attend la clé
 * `listes-manuels`). Résultat : le lien du menu pointait vers /academique/manuels
 * (404). On aligne menu_url sur `listes-manuels`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('feature')) {
            return;
        }

        DB::table('feature')
            ->where('menu_url', 'manuels')
            ->update(['menu_url' => 'listes-manuels']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('feature')) {
            return;
        }

        DB::table('feature')
            ->where('menu_url', 'listes-manuels')
            ->update(['menu_url' => 'manuels']);
    }
};
