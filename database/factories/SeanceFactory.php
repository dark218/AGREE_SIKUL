<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CoursHoraires\Entities\Seance;
use Modules\CoursHoraires\Entities\Cours;

class SeanceFactory extends Factory
{
    protected $model = Seance::class;

    public function definition(): array
    {
        $dateSeance = $this->faker->dateTimeBetween('-3 months', '+3 months');
        $heureDebut = $this->faker->time('H:i', '08:00');

        return [
            'cours_id' => Cours::factory(),
            'date_seance' => $dateSeance,
            'heure_debut' => $heureDebut,
            'heure_fin' => $this->faker->time('H:i', '09:00'),
            'salle' => $this->faker->bothify('SALLE-##'),
            'type' => $this->faker->randomElement(['cours', 'tp', 'td']),
            'statut' => $this->faker->randomElement(['planifiee', 'effectuee', 'annulee']),
        ];
    }
}
