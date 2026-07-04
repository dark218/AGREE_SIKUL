<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rend `section_id` et `cycle_id` nullable sur `groupes_matieres`.
 * Ces valeurs sont désormais auto-remplies depuis le Niveau — si un niveau
 * n'a pas de section/cycle, l'insert doit tolérer le NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('groupes_matieres')) {
            return;
        }
        if (Schema::hasColumn('groupes_matieres', 'section_id')) {
            DB::statement('ALTER TABLE `groupes_matieres` MODIFY `section_id` BIGINT UNSIGNED NULL');
        }
        if (Schema::hasColumn('groupes_matieres', 'cycle_id')) {
            DB::statement('ALTER TABLE `groupes_matieres` MODIFY `cycle_id` BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        // Ne pas revert automatiquement : la table peut contenir des NULL désormais.
    }
};
