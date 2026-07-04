<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Passe `enseignants.statut` d'ENUM restrictif à VARCHAR(50).
 * Permet d'ajouter dynamiquement de nouveaux statuts depuis le référentiel
 * `statuts_employes` sans devoir migrer la colonne à chaque fois.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('enseignants') && Schema::hasColumn('enseignants', 'statut')) {
            DB::statement("ALTER TABLE `enseignants` MODIFY `statut` VARCHAR(50) NOT NULL DEFAULT 'actif'");
        }
    }

    public function down(): void
    {
        // Ne pas revert : possibles valeurs custom introduites entre-temps.
    }
};
