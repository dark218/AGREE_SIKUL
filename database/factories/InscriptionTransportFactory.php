<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finances\Entities\InscriptionTransport;
use Modules\GestionApprenants\Entities\Apprenant;
use Modules\Finances\Entities\ServiceTransport;
use Modules\Ecole\Entities\AnneeScolaire;

class InscriptionTransportFactory extends Factory
{
    protected $model = InscriptionTransport::class;

    public function definition(): array
    {
        return [
            'apprenant_id' => Apprenant::factory(),
            'service_transport_id' => ServiceTransport::factory(),
            'annee_scolaire_id' => AnneeScolaire::factory(),
            'date_inscription' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'point_montee' => $this->faker->address(),
            'point_descente' => $this->faker->address(),
            'prix_total' => $this->faker->numberBetween(20000, 100000),
            'montant_paye' => $this->faker->numberBetween(0, 100000),
            'statut' => $this->faker->randomElement(['active', 'suspendue', 'terminee']),
        ];
    }
}
