<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * §UX Phase 2 :
 *   1. Recrée la table `categorie_apprenants` (retirée en Phase 1 §10.2
 *      considérée comme doublon TypeApprenant+StatutApprenant, mais le user
 *      confirme qu'il s'agit d'un concept métier DISTINCT — statut de
 *      participation : Régulier, Irrégulier, Libre, Auditeur, etc.).
 *   2. Ajoute `categorie_apprenant_id` sur `apprenants` (nullable + FK
 *      nullOnDelete).
 *   3. Ajoute `commentaire` (TEXT nullable) sur `apprenants` — champ libre
 *      demandé sur l'onglet Hébergement & Suivi.
 *   4. Seed 3 catégories par défaut (Régulier, Irrégulier, Libre) pour que
 *      le dropdown ne soit pas vide au premier chargement.
 *
 * Idempotente : chaque bloc est protégé par `Schema::hasTable/hasColumn`.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Recrée la table.
        if (!Schema::hasTable('categorie_apprenants')) {
            Schema::create('categorie_apprenants', function (Blueprint $t) {
                $t->id();
                $t->string('code', 50)->nullable();
                $t->string('libelle', 125);
                $t->text('description')->nullable();
                $t->enum('etat', ['actif', 'inactif'])->default('actif');
                $t->timestamps();
                $t->softDeletes();
                $t->unique(['code'], 'categorie_apprenants_code_unique');
                $t->index('etat');
            });
        }

        // 2. Ajoute categorie_apprenant_id sur apprenants.
        if (Schema::hasTable('apprenants') && !Schema::hasColumn('apprenants', 'categorie_apprenant_id')) {
            Schema::table('apprenants', function (Blueprint $t) {
                $t->unsignedBigInteger('categorie_apprenant_id')->nullable()->after('type_apprenant_id');
                $t->foreign('categorie_apprenant_id')
                  ->references('id')->on('categorie_apprenants')
                  ->nullOnDelete();
            });
        }

        // 3. Ajoute commentaire (TEXT) sur apprenants.
        if (Schema::hasTable('apprenants') && !Schema::hasColumn('apprenants', 'commentaire')) {
            Schema::table('apprenants', function (Blueprint $t) {
                // Placé volontairement après motif_depart_ecole pour cohérence
                // avec l'ordre du formulaire (Hébergement & Suivi).
                $t->text('commentaire')->nullable()->after('motif_depart_ecole');
            });
        }

        // 4. Seed catégories par défaut (idempotent).
        if (Schema::hasTable('categorie_apprenants')
            && DB::table('categorie_apprenants')->count() === 0) {
            $now = now();
            DB::table('categorie_apprenants')->insert([
                ['code' => 'REG',   'libelle' => 'Régulier',   'description' => 'Apprenant à temps plein, présence régulière', 'etat' => 'actif', 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'IRREG', 'libelle' => 'Irrégulier', 'description' => 'Apprenant à présence irrégulière',              'etat' => 'actif', 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'LIBRE', 'libelle' => 'Libre',      'description' => 'Auditeur libre, hors cursus normal',            'etat' => 'actif', 'created_at' => $now, 'updated_at' => $now],
            ]);
        }
    }

    public function down(): void
    {
        // On ne rollback pas — la colonne categorie_apprenant_id peut contenir
        // des données référencées. En cas de vrai besoin, opérer manuellement.
    }
};
