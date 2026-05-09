<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Bibliotheque\Entities\Ouvrage;

class OuvrageFactory extends Factory
{
    protected $model = Ouvrage::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->bothify('OUV-########'),
            'titre' => $this->faker->words(4, true),
            'auteur' => $this->faker->name(),
            'editeur' => $this->faker->company(),
            'isbn' => $this->faker->isbn13(),
            'annee_publication' => $this->faker->year(),
            'nombre_pages' => $this->faker->numberBetween(50, 1000),
            'categorie' => $this->faker->randomElement(['roman', 'scolaire', 'technique', 'reference', 'jeunesse']),
            'langue' => $this->faker->randomElement(['Français', 'Anglais', 'Autres']),
            'resume' => $this->faker->paragraph(),
            'statut' => $this->faker->randomElement(['disponible', 'emprunte', 'perdu', 'deteriore']),
        ];
    }
}
