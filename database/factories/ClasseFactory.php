<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecole\Entities\Classe;

class ClasseFactory extends Factory
{
    protected $model = Classe::class;

    public function definition(): array
    {
        return [
            'ecole_id' => null,
            'niveau_id' => null,
            'code' => $this->faker->unique()->bothify('CLS-####'),
            'nom' => $this->faker->randomElement(['A', 'B', 'C', 'D', 'E']) . $this->faker->randomElement(['1', '2', '3']),
            'capacite_max' => $this->faker->numberBetween(25, 45),
            'salle' => $this->faker->bothify('Salle ###'),
            'statut' => 'actif',
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => ['statut' => 'actif']);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => ['statut' => 'inactif']);
    }
}
