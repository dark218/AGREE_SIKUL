<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\ExamensNotes\Entities\Evaluation;
use Modules\ExamensNotes\Entities\Note;
use Modules\ExamensNotes\Entities\Bulletin;
use Modules\ExamensNotes\Entities\MoyenneMatiere;
use Modules\CoursHoraires\Entities\Cours;
use Modules\GestionApprenants\Entities\Apprenant;
use Modules\GestionApprenants\Entities\Inscription;

class ExamsNotesDataSeeder extends Seeder
{
    public function run(): void
    {
        // Créer cours
        Cours::factory(15)->create();

        // Créer évaluations
        $evaluations = Evaluation::factory(20)->create();

        // Créer notes pour chaque évaluation
        $apprenants = Apprenant::all();
        $evaluations->each(function ($evaluation) use ($apprenants) {
            $apprenants->random(rand(15, 30))->each(function ($apprenant) use ($evaluation) {
                Note::factory()
                    ->for($apprenant)
                    ->for($evaluation)
                    ->create();
            });
        });

        // Créer bulletins
        $inscriptions = Inscription::all();
        $inscriptions->each(function ($inscription) {
            for ($trimestre = 1; $trimestre <= 3; $trimestre++) {
                Bulletin::factory()
                    ->for($inscription->apprenant)
                    ->for($inscription->classe)
                    ->for($inscription->anneeScolaire)
                    ->state(['trimestre' => $trimestre])
                    ->create();

                // Créer moyennes matières
                $matieresIds = $inscription->classe->ecole->matieres()->pluck('id');
                foreach ($matieresIds as $matiereId) {
                    MoyenneMatiere::factory()
                        ->for($inscription->apprenant)
                        ->state(['matiere_id' => $matiereId, 'trimestre' => $trimestre])
                        ->for($inscription->anneeScolaire)
                        ->create();
                }
            }
        });

        $this->command->info('Exams and Notes data seeded successfully!');
    }
}
