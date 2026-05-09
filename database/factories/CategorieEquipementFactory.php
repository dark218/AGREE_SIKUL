<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventaire\Entities\CategorieEquipement;

class CategorieEquipementFactory extends Factory
{
    protected $model = CategorieEquipement::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->bothify('CEQU-##??'),
            'libelle' => $this->faker->randomElement(['Informatique', 'Mobilier', 'Laboratoire', 'Sports', 'Cuisine', 'Nettoyage']),
            'description' => $this->faker->paragraph(),
            'statut' => $this->faker->randomElement(['actif', 'inactif']),
        ];
    }
}
