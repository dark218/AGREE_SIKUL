<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot Parent ↔ Apprenants (relation N-N).
 *
 * Un parent peut désormais être rattaché à plusieurs apprenants d'une même
 * école (fratrie). Le champ `est_principal` marque celui qui remonte en tête
 * dans les listes/PDF (par défaut le 1er saisi). `lien_parente` précise si
 * cette personne est père / mère / autre pour CET apprenant précisément
 * (utile si un même parent est père d'un enfant et beau-père d'un autre).
 *
 * Migration data : recopie l'ancien lien 1-1 (`parents.apprenant_id`) dans
 * le pivot pour ne rien perdre. La colonne `parents.apprenant_id` reste
 * (nullable) en dépréciation ; une migration ultérieure la supprimera une
 * fois tout le code porté sur le pivot.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('apprenant_parent')) {
            Schema::create('apprenant_parent', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('parent_id');
                $table->unsignedBigInteger('apprenant_id');
                $table->enum('lien_parente', ['pere', 'mere', 'tuteur_legal', 'autre'])->nullable();
                $table->boolean('est_principal')->default(false);
                $table->timestamps();

                $table->unique(['parent_id', 'apprenant_id'], 'apprenant_parent_unique');
                $table->foreign('parent_id')->references('id')->on('parents')->onDelete('cascade');
                $table->foreign('apprenant_id')->references('id')->on('apprenants')->onDelete('cascade');
            });
        }

        // Data migration : recopie parents.apprenant_id → pivot (idempotent via insertOrIgnore)
        if (Schema::hasTable('parents') && Schema::hasColumn('parents', 'apprenant_id')) {
            DB::table('parents')
                ->whereNotNull('apprenant_id')
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->chunkById(500, function ($rows) {
                    $now = now();
                    $insert = [];
                    foreach ($rows as $p) {
                        $insert[] = [
                            'parent_id' => $p->id,
                            'apprenant_id' => $p->apprenant_id,
                            'lien_parente' => null,
                            'est_principal' => true,
                            'created_at' => $p->created_at ?? $now,
                            'updated_at' => $p->updated_at ?? $now,
                        ];
                    }
                    if (!empty($insert)) {
                        DB::table('apprenant_parent')->insertOrIgnore($insert);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('apprenant_parent');
    }
};
