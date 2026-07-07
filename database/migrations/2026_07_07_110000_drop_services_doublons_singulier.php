<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drop les 4 tables doublons SINGULIER du module Services (Phase 4.6c fix).
 *
 * Contexte : Modules/Services/Database/Migrations crée des tables au singulier
 * (`consultations_infirmerie`, `passages_cantine`, `inscriptions_cantine`,
 * `inscriptions_transport`) avec un schéma DIFFÉRENT des tables canoniques
 * PLURIEL créées dans database/migrations/2026_02_09_15**_create_*_table.php.
 *
 * Résultat : chaque entité pointe sur l'une des deux, avec un fillable qui
 * ne matche que le schéma pluriel. Toutes les entités ont été repointées sur
 * les tables pluriel dans les fixes précédents.
 *
 * Ces 4 tables singulier sont vides en prod (0 rows vérifiés) — drop safe.
 *
 * Idempotente : `dropIfExists` no-op si absente.
 */
return new class extends Migration
{
    private const DOUBLONS_A_DROP = [
        'consultations_infirmerie',
        'passages_cantine',
        'inscriptions_cantine',
        'inscriptions_transport',
    ];

    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach (self::DOUBLONS_A_DROP as $table) {
            if (Schema::hasTable($table)) {
                Schema::dropIfExists($table);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // Aucune restauration : les tables singulier ne doivent jamais réapparaître.
        // Les entités Services ont été repointées définitivement vers les PLURIEL.
    }
};
