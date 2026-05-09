<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecole\Entities\Niveau;
use Modules\Ecole\Entities\Ecole;

class NiveauFactory extends Factory
{
    protected $model = Niveau::class;

    public function definition(): array
    {
        return [
            'ecole_id' => Ecole::factory(),
            'code' => $this->faker->unique()->bothify('NIV-##'),
            'libelle' => $this->faker->randomElement(['6ème', '5ème', '4ème', '3ème', '2nde', '1ère', 'Terminale', 'CP', 'CE1', 'CE2', 'CM1', 'CM2']),
            'ordre' => $this->faker->numberBetween(1, 12),
            'age_min' => $this->faker->numberBetween(5, 18),
            'age_max' => $this->faker->numberBetween(6, 19),
            'cycle' => $this->faker->randomElement(['elementaire', 'moyen', 'secondaire']),
            'statut' => $this->faker->randomElement(['actif', 'inactif']),
        ];
    }
}
