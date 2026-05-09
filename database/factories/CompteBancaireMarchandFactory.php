<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Business\Entities\CompteBancaireMarchand;
use Modules\Business\Entities\Marchand;
use Modules\Parametrage\Entities\Banque;

class CompteBancaireMarchandFactory extends Factory
{
    protected $model = CompteBancaireMarchand::class;

    public function definition(): array
    {
        return [
            'marchand_id' => Marchand::factory(),
            'banque_id' => Banque::factory(),
            'nom_compte' => $this->faker->name,
            'numero_compte' => $this->faker->bankAccountNumber,
            'iban' => $this->faker->iban(),
            'bic_swift' => $this->faker->swiftBicNumber,
            'is_principal' => $this->faker->boolean(20),
            'is_active' => $this->faker->boolean(90),
            'meta_json' => [
                'date_ouverture' => $this->faker->date(),
                'type_compte' => $this->faker->randomElement(['courant', 'epargne', 'professionnel']),
            ],
        ];
    }
}