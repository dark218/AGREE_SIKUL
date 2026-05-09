<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finances\Entities\TypeFrais;

class TypeFraisFactory extends Factory
{
    protected $model = TypeFrais::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->bothify('TF-##??'),
            'libelle' => $this->faker->randomElement(['Frais de scolarité', 'Frais de transport', 'Frais de cantine', 'Frais d\'examen', 'Frais administratifs', 'Frais de fournitures']),
            'description' => $this->faker->paragraph(),
            'montant_defaut' => $this->faker->numberBetween(10000, 500000),
            'periode_facturation' => $this->faker->randomElement(['mensuel', 'trimestriel', 'annuel']),
            'statut' => $this->faker->randomElement(['actif', 'inactif']),
        ];
    }
}
