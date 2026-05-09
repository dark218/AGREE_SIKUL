<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Presence\Entities\AbsenceApprenant;
use Modules\GestionApprenants\Entities\Apprenant;

class AbsenceApprenantFactory extends Factory
{
    protected $model = AbsenceApprenant::class;

    public function definition(): array
    {
        $dateAbsence = $this->faker->dateTimeBetween('-6 months', 'now');

        return [
            'apprenant_id' => Apprenant::factory(),
            'date_absence' => $dateAbsence,
            'nombre_heures' => $this->faker->randomElement([1, 2, 3, 4]),
            'motif' => $this->faker->randomElement(['Maladie', 'Raison personnelle', 'Décision parentale', 'Autre']),
            'justifiee' => $this->faker->boolean(50),
            'statut' => $this->faker->randomElement(['enregistree', 'justifiee', 'non_justifiee']),
        ];
    }
}
