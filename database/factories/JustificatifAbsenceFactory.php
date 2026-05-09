<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Presence\Entities\JustificatifAbsence;
use Modules\Presence\Entities\AbsenceApprenant;

class JustificatifAbsenceFactory extends Factory
{
    protected $model = JustificatifAbsence::class;

    public function definition(): array
    {
        return [
            'absence_apprenant_id' => AbsenceApprenant::factory(),
            'type_justificatif' => $this->faker->randomElement(['certificat_medical', 'courrier_parent', 'autorisation', 'autre']),
            'date_justificatif' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'numero_reference' => $this->faker->numerify('JUST-########'),
            'description' => $this->faker->paragraph(),
            'fichier_id' => null,
            'statut' => $this->faker->randomElement(['accepte', 'en_attente', 'rejete']),
        ];
    }
}
