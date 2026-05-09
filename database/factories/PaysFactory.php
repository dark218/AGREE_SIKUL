<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Parametrage\Entities\Pays;

class PaysFactory extends Factory
{
    protected $model = Pays::class;

    public function definition(): array
    {
        return [
            'libelle' => $this->faker->country,
            'code' => '+' . $this->faker->numberBetween(1, 999),
            'phone_length' => $this->faker->numberBetween(8, 12),
            'iso' => $this->faker->countryCode,
        ];
    }
}