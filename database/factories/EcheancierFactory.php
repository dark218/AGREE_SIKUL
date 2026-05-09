<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finances\Entities\Echeancier;
use Modules\Finances\Entities\TypeFrais;

class EcheancierFactory extends Factory
{
    protected $model = Echeancier::class;

    public function definition(): array
    {
        return [
            'type_frais_id' => TypeFrais::factory(),
            'numero_echeance' => $this->faker->numberBetween(1, 12),
            'date_echeance' => $this->faker->dateTimeBetween('now', '+12 months'),
            'montant_partiel' => $this->faker->numberBetween(10000, 500000),
            'description' => $this->faker->optional()->sentence(),
            'statut' => $this->faker->randomElement(['planifiee', 'paye', 'depassee']),
        ];
    }
}
