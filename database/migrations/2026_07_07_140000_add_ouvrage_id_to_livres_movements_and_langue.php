<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bibliothèque › Entrée/Sortie de livres :
 *  - on référence un Ouvrage existant (ouvrage_id) pour remonter automatiquement
 *    titre/auteur/éditeur/langue/année → aucune re-saisie (anti-redondance).
 *  - on ajoute `langue` au catalogue Ouvrage (champ demandé par la spec).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('entrees_livres') && !Schema::hasColumn('entrees_livres', 'ouvrage_id')) {
            Schema::table('entrees_livres', function (Blueprint $table) {
                $table->foreignId('ouvrage_id')->nullable()->after('bibliotheque_structure_id')
                    ->constrained('ouvrages')->nullOnDelete();
            });
        }

        if (Schema::hasTable('sorties_livres') && !Schema::hasColumn('sorties_livres', 'ouvrage_id')) {
            Schema::table('sorties_livres', function (Blueprint $table) {
                $table->foreignId('ouvrage_id')->nullable()->after('bibliotheque_structure_id')
                    ->constrained('ouvrages')->nullOnDelete();
            });
        }

        if (Schema::hasTable('ouvrages') && !Schema::hasColumn('ouvrages', 'langue')) {
            Schema::table('ouvrages', function (Blueprint $table) {
                $table->string('langue', 100)->nullable()->after('editeur');
            });
        }
    }

    public function down(): void
    {
        foreach (['entrees_livres', 'sorties_livres'] as $t) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, 'ouvrage_id')) {
                Schema::table($t, function (Blueprint $table) {
                    try { $table->dropForeign(['ouvrage_id']); } catch (\Throwable $e) {}
                    $table->dropColumn('ouvrage_id');
                });
            }
        }
        if (Schema::hasTable('ouvrages') && Schema::hasColumn('ouvrages', 'langue')) {
            Schema::table('ouvrages', fn (Blueprint $table) => $table->dropColumn('langue'));
        }
    }
};
