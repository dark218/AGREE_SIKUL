<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Parametrage\Entities\Devises;

class DevisesFactory extends Factory
{
    protected $model = Devises::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->currencyCode,
            'symbol' => $this->faker->randomElement(['$', '€', '£', '¥', '₦', 'CFA']),
            'libelle' => $this->faker->word . ' Currency',
            'decimal_places' => $this->faker->numberBetween(0, 4),
        ];
    }
}