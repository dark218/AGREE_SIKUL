<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * §UX : la feature « Période Colaire » avait une typo dans le libellé visible
 * au user (label sidebar + entrée `feature` en base). Le nom de la table
 * `periodes_colaires` et de l'entité `PeriodeColaire` restent inchangés pour
 * éviter un renommage massif (migrations, FK, code applicatif).
 *
 * Cette migration corrige UNIQUEMENT les libellés user-facing :
 *   - feature.libelle : "Période Colaire" → "Période Scolaire"
 *   - feature.libelle : "Périodes Colaires" → "Périodes Scolaires"
 *
 * Idempotente : n'affecte que les lignes typo, ne fait rien si déjà propre.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('feature')) return;

        DB::table('feature')
            ->where('libelle', 'Période Colaire')
            ->update(['libelle' => 'Période Scolaire', 'updated_at' => now()]);

        DB::table('feature')
            ->where('libelle', 'Périodes Colaires')
            ->update(['libelle' => 'Périodes Scolaires', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Restauration explicite d'une typo — non.
    }
};
