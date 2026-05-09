<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ExamensNotes\Entities\MoyenneMatiere;
use Modules\GestionApprenants\Entities\Apprenant;
use Modules\CoursHoraires\Entities\Matiere;
use Modules\Ecole\Entities\AnneeScolaire;

class MoyenneMatiereFactory extends Factory
{
    protected $model = MoyenneMatiere::class;

    public function definition(): array
    {
        return [
            'apprenant_id' => Apprenant::factory(),
            'matiere_id' => Matiere::factory(),
            'annee_scolaire_id' => AnneeScolaire::factory(),
            'trimestre' => $this->faker->randomElement([1, 2, 3]),
            'moyenne' => $this->faker->randomFloat(2, 0, 20),
            'nombre_evaluations' => $this->faker->numberBetween(3, 10),
            'date_calcul' => now(),
        ];
    }
}
