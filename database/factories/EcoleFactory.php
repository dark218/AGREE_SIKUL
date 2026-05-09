<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecole\Entities\Ecole;
use Modules\Campus\Entities\Campus;

class EcoleFactory extends Factory
{
    protected $model = Ecole::class;

    public function definition(): array
    {
        return [
            'campus_id' => Campus::factory(),
            'code' => $this->faker->unique()->bothify('ECO-##??'),
            'nom' => $this->faker->words(3, true) . ' ' . $this->faker->randomElement(['Primaire', 'Secondaire', 'Collège']),
            'type_enseignement' => $this->faker->randomElement(['primaire', 'secondaire', 'professionnel']),
            'capacite_totale' => $this->faker->numberBetween(100, 1000),
            'statut' => $this->faker->randomElement(['actif', 'inactif']),
        ];
    }
}
