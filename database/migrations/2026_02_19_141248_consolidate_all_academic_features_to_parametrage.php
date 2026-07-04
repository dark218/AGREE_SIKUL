<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CONSOLIDATE ALL ACADEMIC ENTITIES TO PARAMÉTRAGE:
     * Move all features from modules 2-9 to module 23 (Paramétrage)
     * Modules 2-9 will then have 0 features and be hidden from sidebar
     */
    public function up(): void
    {
        // idempotence guard
        // Move all features from modules 2-9 to module 23
        DB::table('feature')
            ->whereIn('module_id', [2, 3, 4, 5, 6, 7, 8, 9])
            ->update(['module_id' => 23]);

        // Reorder all features in module 23 sequentially from 1 to 59
        $features = DB::table('feature')
            ->where('module_id', 23)
            ->orderBy('id')
            ->get();

        $ordre = 1;
        foreach ($features as $feature) {
            DB::table('feature')
                ->where('id', $feature->id)
                ->update(['ordre' => $ordre]);
            $ordre++;
        }

        info('✅ Consolidation complète: Toutes les fonctionnalités académiques (modules 2-9) sont maintenant des features du module 23 (Paramétrage). Total: 59 features.');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse: move features back to their original modules 2-9
        $mapping = [
            [2, [2]],                                      // Campus
            [3, [3, 29, 30, 31]],                          // Écoles, Niveaux, Classes, Années Scolaires
            [4, [4, 32, 33, 34]],                          // Apprenants, Tuteurs, Inscriptions, Dossiers
            [5, [5, 35, 36]],                              // Enseignants, Personnel Admin, Absences
            [6, [6, 37, 38, 39]],                          // Matières, Cours, Séances, Emplois du Temps
            [7, [7, 40, 41]],                              // Présences, Absences Apprenants, Justifications
            [8, [8, 42, 43, 44]],                          // Évaluations, Notes, Bulletins, Moyennes
            [9, [9, 45]],                                  // Devoirs, Rendus
        ];

        foreach ($mapping as [$moduleId, $featureIds]) {
            DB::table('feature')
                ->whereIn('id', $featureIds)
                ->update(['module_id' => $moduleId]);
        }
    }
};
