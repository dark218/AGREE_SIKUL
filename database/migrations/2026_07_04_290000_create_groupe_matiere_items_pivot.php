<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3.2 — Remplace les 10 colonnes `matiere1_id..matiere10_id` de
 * `groupes_matieres` par une vraie pivot `groupe_matiere_items`.
 *
 * Table vide → refactor pur, pas de data migration réelle mais on migre
 * quand même par sécurité si des lignes apparaissent d'ici la MAJ prod.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('groupes_matieres')) {
            return;
        }

        // 1. Pivot.
        if (!Schema::hasTable('groupe_matiere_items')) {
            Schema::create('groupe_matiere_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('groupe_matiere_id')
                    ->constrained('groupes_matieres')
                    ->cascadeOnDelete();
                $table->foreignId('matiere_id')
                    ->constrained('matieres_unites')
                    ->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['groupe_matiere_id', 'matiere_id'], 'gm_items_unique');
                $table->index('groupe_matiere_id', 'gm_items_grp_idx');
                $table->index('matiere_id', 'gm_items_mat_idx');
            });
        }

        // 2. Migre les données existantes.
        $rows = DB::table('groupes_matieres')->get();
        foreach ($rows as $row) {
            for ($i = 1; $i <= 10; $i++) {
                $col = "matiere{$i}_id";
                if (!Schema::hasColumn('groupes_matieres', $col)) continue;
                $val = $row->{$col} ?? null;
                if ($val === null) continue;
                DB::table('groupe_matiere_items')->updateOrInsert(
                    [
                        'groupe_matiere_id' => $row->id,
                        'matiere_id'        => $val,
                    ],
                    [
                        'updated_at' => now(),
                        'created_at' => $row->created_at ?? now(),
                    ]
                );
            }
        }

        // 3. Drop les 10 colonnes matiereN_id de groupes_matieres.
        Schema::table('groupes_matieres', function (Blueprint $table) {
            for ($i = 1; $i <= 10; $i++) {
                $col = "matiere{$i}_id";
                if (Schema::hasColumn('groupes_matieres', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        // Rollback non trivial.
    }
};
