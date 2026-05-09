<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Campus\Entities\Campus;
use Modules\Institution\Entities\Institution;

class CampusFactory extends Factory
{
    protected $model = Campus::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'code' => $this->faker->unique()->bothify('CAMP-##??'),
            'nom' => $this->faker->city() . ' Campus',
            'adresse' => $this->faker->streetAddress(),
            'ville' => $this->faker->city(),
            'code_postal' => $this->faker->postcode(),
            'longitude' => $this->faker->longitude(-17, -12),
            'latitude' => $this->faker->latitude(12, 15),
            'telephone' => $this->faker->phoneNumber(),
            'email' => $this->faker->companyEmail(),
            'statut' => $this->faker->randomElement(['actif', 'inactif']),
        ];
    }
}
