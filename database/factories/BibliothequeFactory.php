<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Bibliotheque\Entities\Bibliotheque;
use Modules\Ecole\Entities\Ecole;

class BibliothequeFactory extends Factory
{
    protected $model = Bibliotheque::class;

    public function definition(): array
    {
        return [
            'ecole_id' => Ecole::factory(),
            'code' => $this->faker->unique()->bothify('BIB-##??'),
            'nom' => $this->faker->words(3, true) . ' Bibliothèque',
            'localisation' => $this->faker->bothify('BATIMENT-##'),
            'responsable_id' => null,
            'horaires_ouverture' => '08:00-17:00',
            'nombre_places' => $this->faker->numberBetween(50, 200),
            'numero_telephone' => $this->faker->optional()->phoneNumber(),
            'email' => $this->faker->optional()->email(),
            'statut' => $this->faker->randomElement(['actif', 'inactif']),
        ];
    }
}
