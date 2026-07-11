<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §UX : ajoute au formulaire Enseignant les champs demandés par le user :
 *   - Titre de civilité (M., Mme, Dr, Pr, …) — FK vers `titres_civilites`
 *   - Téléphone 2 (contact secondaire)
 *
 * Idempotente : chaque colonne est protégée par `Schema::hasColumn`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('enseignants')) return;

        Schema::table('enseignants', function (Blueprint $t) {
            if (!Schema::hasColumn('enseignants', 'titre_civilite_id')) {
                $t->unsignedBigInteger('titre_civilite_id')->nullable()->after('nom_jeune_fille');
                if (Schema::hasTable('titres_civilites')) {
                    $t->foreign('titre_civilite_id')
                      ->references('id')->on('titres_civilites')
                      ->nullOnDelete();
                }
            }
            if (!Schema::hasColumn('enseignants', 'telephone2')) {
                $t->string('telephone2', 20)->nullable()->after('telephone');
            }
        });
    }

    public function down(): void
    {
        // Non — on ne rollback pas les colonnes utilisateur.
    }
};
