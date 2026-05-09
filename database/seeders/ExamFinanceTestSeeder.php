<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Academique\Entities\PlanificationExamen;
use Modules\Academique\Entities\PlanificationExamenPosteRecette;
use Modules\Finances\Entities\PosteRecette;
use Carbon\Carbon;

class ExamFinanceTestSeeder extends Seeder
{
    public function run()
    {
        echo "\n=== Creating Exam Finance Test Data ===\n";

        // Get some exams
        $exams = PlanificationExamen::where('etat', 'actif')
            ->whereNull('deleted_at')
            ->take(5)
            ->get();

        if ($exams->isEmpty()) {
            echo "⚠️ No active exams found. Skipping test data creation.\n";
            return;
        }

        // Get some postes recette
        $postes = PosteRecette::where('etat', 'actif')
            ->whereNull('deleted_at')
            ->take(5)
            ->get();

        if ($postes->isEmpty()) {
            echo "⚠️ No active postes recette found. Skipping test data creation.\n";
            return;
        }

        // Create associations
        $count = 0;
        foreach ($exams as $exam) {
            foreach ($postes as $poste) {
                // Skip if association already exists
                $existing = PlanificationExamenPosteRecette::where('exam_id', $exam->id)
                    ->where('recette_id', $poste->id)
                    ->exists();

                if ($existing) {
                    continue;
                }

                $montant = rand(100, 1000) + rand(0, 99) / 100;
                $pourcentage = rand(10, 100);

                $association = PlanificationExamenPosteRecette::create([
                    'exam_id' => $exam->id,
                    'recette_id' => $poste->id,
                    'montant_finance' => $montant,
                    'pourcentage_couverture' => $pourcentage,
                    'date_facturation' => now()->subDays(rand(5, 15)),
                    'date_limite_paiement' => now()->addDays(rand(10, 30)),
                    'etat_financement' => collect(['actif', 'en-attente', 'clôturé'])->random(),
                    'notes' => 'Données de test créées par seeder',
                    'creation_username' => 'seeder',
                ]);

                echo "✅ Created association: Exam {$exam->id} - Poste {$poste->id}\n";
                $count++;

                if ($count >= 10) {
                    break 2;
                }
            }
        }

        echo "\n✅ Created {$count} exam finance associations!\n";
    }
}
