<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ExamensNotes\Entities\Evaluation;
use Modules\CoursHoraires\Entities\Cours;
use Modules\CoursHoraires\Entities\Matiere;

class EvaluationFactory extends Factory
{
    protected $model = Evaluation::class;

    public function definition(): array
    {
        return [
            'cours_id' => Cours::factory(),
            'matiere_id' => Matiere::factory(),
            'titre' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(['devoir', 'test', 'examen', 'composition']),
            'date_evaluation' => $this->faker->dateTimeBetween('-3 months', '+3 months'),
            'coefficient' => $this->faker->randomElement([1, 1.5, 2, 2.5, 3]),
            'note_max' => 20,
            'description' => $this->faker->paragraph(),
            'statut' => $this->faker->randomElement(['planifiee', 'en_cours', 'terminee', 'corrigee']),
        ];
    }
}
