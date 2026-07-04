<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot Tuteur ↔ Apprenants.
 * Même logique que apprenant_parent — un tuteur peut suivre plusieurs
 * apprenants (fratrie confiée à un même oncle/grand-parent/etc.).
 *
 * `relation` reprend le champ enum existant sur `tuteurs` mais permet de
 * l'affiner par apprenant (utile pour des cas rares).
 */
return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (!Schema::hasTable('apprenant_tuteur')) {
            Schema::hasTable('apprenant_tuteur') ? null : Schema::create('apprenant_tuteur', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('tuteur_id');
                $table->unsignedBigInteger('apprenant_id');
                $table->string('relation', 100)->nullable();
                $table->boolean('est_principal')->default(false);
                $table->timestamps();

                $table->unique(['tuteur_id', 'apprenant_id'], 'apprenant_tuteur_unique');
                $table->foreign('tuteur_id')->references('id')->on('tuteurs')->onDelete('cascade');
                $table->foreign('apprenant_id')->references('id')->on('apprenants')->onDelete('cascade');
            });
        }

        // Data migration : recopie tuteurs.apprenant_id → pivot
        if (Schema::hasTable('tuteurs') && Schema::hasColumn('tuteurs', 'apprenant_id')) {
            $hasRelation = Schema::hasColumn('tuteurs', 'relation');
            DB::table('tuteurs')
                ->whereNotNull('apprenant_id')
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->chunkById(500, function ($rows) use ($hasRelation) {
                    $now = now();
                    $insert = [];
                    foreach ($rows as $t) {
                        $insert[] = [
                            'tuteur_id' => $t->id,
                            'apprenant_id' => $t->apprenant_id,
                            'relation' => $hasRelation ? ($t->relation ?? null) : null,
                            'est_principal' => true,
                            'created_at' => $t->created_at ?? $now,
                            'updated_at' => $t->updated_at ?? $now,
                        ];
                    }
                    if (!empty($insert)) {
                        DB::table('apprenant_tuteur')->insertOrIgnore($insert);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('apprenant_tuteur');
    }
};
