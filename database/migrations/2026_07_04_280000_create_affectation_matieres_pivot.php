<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3.1 — Remplace les 21 colonnes `matiere_1_id..matiere_21_id` de
 * `affectations_enseignants` par une vraie pivot `affectation_matieres`.
 *
 * Table vide au moment de la migration → refactor pur, pas de data migration.
 * On garde `affectations_enseignants` mais on drop les 21 colonnes après
 * migration (donc le fillable de l'entité doit être mis à jour AVANT
 * d'exécuter cette migration).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('affectations_enseignants')) {
            return;
        }

        // 1. Crée la pivot.
        if (!Schema::hasTable('affectation_matieres')) {
            Schema::create('affectation_matieres', function (Blueprint $table) {
                $table->id();
                $table->foreignId('affectation_enseignant_id')
                    ->constrained('affectations_enseignants')
                    ->cascadeOnDelete();
                $table->foreignId('matiere_id')
                    ->constrained('matieres_unites')
                    ->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['affectation_enseignant_id', 'matiere_id'], 'affect_mat_unique');
                $table->index('affectation_enseignant_id', 'affect_mat_aff_idx');
                $table->index('matiere_id', 'affect_mat_mat_idx');
            });
        }

        // 2. Migre les données existantes (safety : boucle sur les 21 colonnes)
        //    Skip si affectations_enseignants est vide.
        if (Schema::hasTable('affectations_enseignants')) {
            $rows = DB::table('affectations_enseignants')->get();
            foreach ($rows as $row) {
                for ($i = 1; $i <= 21; $i++) {
                    $col = "matiere_{$i}_id";
                    if (!Schema::hasColumn('affectations_enseignants', $col)) continue;
                    $val = $row->{$col} ?? null;
                    if ($val === null) continue;
                    // Insert dans la pivot (unique-safe)
                    DB::table('affectation_matieres')->updateOrInsert(
                        [
                            'affectation_enseignant_id' => $row->id,
                            'matiere_id' => $val,
                        ],
                        [
                            'updated_at' => now(),
                            'created_at' => $row->created_at ?? now(),
                        ]
                    );
                }
            }
        }

        // 3. Drop les 21 colonnes de affectations_enseignants
        Schema::table('affectations_enseignants', function (Blueprint $table) {
            for ($i = 1; $i <= 21; $i++) {
                $col = "matiere_{$i}_id";
                if (Schema::hasColumn('affectations_enseignants', $col)) {
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
