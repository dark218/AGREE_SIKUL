<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finances\Entities\ServiceTransport;
use Modules\Ecole\Entities\Ecole;

class ServiceTransportFactory extends Factory
{
    protected $model = ServiceTransport::class;

    public function definition(): array
    {
        return [
            'ecole_id' => Ecole::factory(),
            'code' => $this->faker->unique()->bothify('TRANS-##??'),
            'nom' => 'Transport ' . $this->faker->city(),
            'route' => $this->faker->city() . ' - ' . $this->faker->city(),
            'responsable_id' => null,
            'nombre_places' => $this->faker->numberBetween(30, 60),
            'prix_transport_mois' => $this->faker->numberBetween(20000, 100000),
            'numero_permis_bus' => $this->faker->optional()->numerify('########'),
            'immatriculation_bus' => $this->faker->optional()->bothify('?? ## ??'),
            'contact_phone' => $this->faker->optional()->phoneNumber(),
            'statut' => $this->faker->randomElement(['actif', 'inactif']),
        ];
    }
}
