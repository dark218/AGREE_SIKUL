<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ExamensNotes\Entities\Note;
use Modules\GestionApprenants\Entities\Apprenant;
use Modules\ExamensNotes\Entities\Evaluation;

class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        return [
            'apprenant_id' => Apprenant::factory(),
            'evaluation_id' => Evaluation::factory(),
            'note' => $this->faker->randomFloat(2, 0, 20),
            'date_saisie' => now(),
            'remarques' => $this->faker->optional()->sentence(),
            'statut' => $this->faker->randomElement(['saisie', 'corrigee', 'validee']),
        ];
    }
}
