<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventaire\Entities\Equipement;
use Modules\Inventaire\Entities\CategorieEquipement;
use Modules\Ecole\Entities\Ecole;

class EquipementFactory extends Factory
{
    protected $model = Equipement::class;

    public function definition(): array
    {
        return [
            'categorie_equipement_id' => CategorieEquipement::factory(),
            'ecole_id' => Ecole::factory(),
            'code' => $this->faker->unique()->bothify('EQU-########'),
            'libelle' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'quantite' => $this->faker->numberBetween(1, 50),
            'numero_serie' => $this->faker->optional()->numerify('SN-########'),
            'date_acquisition' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'cout_acquisition' => $this->faker->numberBetween(10000, 500000),
            'localisation' => $this->faker->bothify('BATIMENT-##'),
            'etat' => $this->faker->randomElement(['neuf', 'bon', 'mauvais', 'hors_service']),
            'statut' => $this->faker->randomElement(['operationnel', 'maintenance', 'rebut']),
        ];
    }
}
