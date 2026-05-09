<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\EnseignantsRH\Entities\AbsenceEnseignant;
use Modules\EnseignantsRH\Entities\Enseignant;

class AbsenceEnseignantFactory extends Factory
{
    protected $model = AbsenceEnseignant::class;

    public function definition(): array
    {
        $dateAbsence = $this->faker->dateTimeBetween('-6 months', 'now');

        return [
            'enseignant_id' => Enseignant::factory(),
            'date_absence' => $dateAbsence,
            'motif' => $this->faker->randomElement(['Maladie', 'Congé', 'Mission', 'Raison personnelle', 'Formation']),
            'justification' => $this->faker->paragraph(),
            'statut' => $this->faker->randomElement(['justifiee', 'non_justifiee']),
        ];
    }
}
