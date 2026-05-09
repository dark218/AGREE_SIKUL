<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Bibliotheque\Entities\Exemplaire;
use Modules\Bibliotheque\Entities\Ouvrage;
use Modules\Bibliotheque\Entities\Bibliotheque;

class ExemplaireFactory extends Factory
{
    protected $model = Exemplaire::class;

    public function definition(): array
    {
        return [
            'ouvrage_id' => Ouvrage::factory(),
            'bibliotheque_id' => Bibliotheque::factory(),
            'code_barre' => $this->faker->unique()->ean13(),
            'numero_exemplaire' => $this->faker->numberBetween(1, 5),
            'etat' => $this->faker->randomElement(['neuf', 'bon', 'mauvais']),
            'date_acquisition' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'cout_acquisition' => $this->faker->numberBetween(5000, 100000),
            'localisation_rayon' => $this->faker->bothify('RAYON-##-##'),
            'statut' => $this->faker->randomElement(['disponible', 'emprunte', 'perdu', 'deteriore', 'rebut']),
        ];
    }
}
