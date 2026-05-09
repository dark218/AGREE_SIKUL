<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finances\Entities\ServiceCantine;
use Modules\Ecole\Entities\Ecole;

class ServiceCantineFactory extends Factory
{
    protected $model = ServiceCantine::class;

    public function definition(): array
    {
        return [
            'ecole_id' => Ecole::factory(),
            'code' => $this->faker->unique()->bothify('CANT-##??'),
            'nom' => 'Cantine ' . $this->faker->word(),
            'localisation' => $this->faker->bothify('BATIMENT-##'),
            'responsable_id' => null,
            'capacite_places' => $this->faker->numberBetween(100, 500),
            'horaires_service' => '11:30-13:30',
            'prix_repas_journee' => $this->faker->numberBetween(2000, 5000),
            'prix_repas_semaine' => $this->faker->numberBetween(10000, 25000),
            'prix_repas_mois' => $this->faker->numberBetween(40000, 100000),
            'contact_phone' => $this->faker->optional()->phoneNumber(),
            'email' => $this->faker->optional()->email(),
            'statut' => $this->faker->randomElement(['actif', 'inactif']),
        ];
    }
}
