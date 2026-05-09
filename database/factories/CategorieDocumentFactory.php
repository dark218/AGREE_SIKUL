<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Documents\Entities\CategorieDocument;

class CategorieDocumentFactory extends Factory
{
    protected $model = CategorieDocument::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->bothify('CAT-##??'),
            'libelle' => $this->faker->randomElement(['Administratif', 'Pédagogique', 'Financier', 'RH', 'Technique', 'Légal']),
            'description' => $this->faker->paragraph(),
            'statut' => $this->faker->randomElement(['actif', 'inactif']),
        ];
    }
}
