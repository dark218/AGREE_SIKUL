<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les fonctionnalités Ouvrages, Exemplaires, Emprunts et Réservations doivent
 * apparaître dans le menu « Bibliothèque » et non « Ressources Logistique ».
 * On les rattache au module Bibliothèque.
 */
return new class extends Migration
{
    private array $urls = ['ouvrages', 'exemplaires', 'emprunts', 'reservations'];

    public function up(): void
    {
        if (!Schema::hasTable('feature') || !Schema::hasTable('module')) {
            return;
        }

        $bibliotheque = DB::table('module')->where('libelle', 'Bibliothèque')->value('id');
        if (!$bibliotheque) {
            return;
        }

        DB::table('feature')->whereIn('menu_url', $this->urls)->update(['module_id' => $bibliotheque]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('feature') || !Schema::hasTable('module')) {
            return;
        }

        $ressources = DB::table('module')->where('libelle', 'Ressources Logistique')->value('id');
        if (!$ressources) {
            return;
        }

        DB::table('feature')->whereIn('menu_url', $this->urls)->update(['module_id' => $ressources]);
    }
};
