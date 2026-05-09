<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Parametrage\Entities\Banque;
use Modules\Parametrage\Entities\Pays;

class BanqueFactory extends Factory
{
    protected $model = Banque::class;

    public function definition(): array
    {
        return [
            'pays_id' => Pays::factory(),
            'nom' => $this->faker->company . ' Bank',
            'code' => $this->faker->unique()->lexify('BNK???'),
            'bic_swift' => $this->faker->swiftBicNumber,
            'is_active' => $this->faker->boolean(80),
            'meta_json' => [
                'website' => $this->faker->url,
                'phone' => $this->faker->phoneNumber,
            ],
        ];
    }
}