<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Bibliotheque\Entities\Reservation;
use Modules\GestionApprenants\Entities\Apprenant;
use Modules\Bibliotheque\Entities\Ouvrage;

class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        return [
            'apprenant_id' => Apprenant::factory(),
            'ouvrage_id' => Ouvrage::factory(),
            'date_reservation' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'date_limite_retrait' => $this->faker->dateTimeBetween('now', '+30 days'),
            'date_retrait' => $this->faker->optional(0.6)->dateTimeBetween('now', '+30 days'),
            'rang' => $this->faker->numberBetween(1, 10),
            'statut' => $this->faker->randomElement(['en_attente', 'disponible', 'retire', 'annulee']),
        ];
    }

    public function disponible()
    {
        return $this->state(function (array $attributes) {
            return [
                'statut' => 'disponible',
            ];
        });
    }
}
