<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finances\Entities\InscriptionCantine;
use Modules\GestionApprenants\Entities\Apprenant;
use Modules\Finances\Entities\ServiceCantine;
use Modules\Ecole\Entities\AnneeScolaire;

class InscriptionCantineFactory extends Factory
{
    protected $model = InscriptionCantine::class;

    public function definition(): array
    {
        return [
            'apprenant_id' => Apprenant::factory(),
            'service_cantine_id' => ServiceCantine::factory(),
            'annee_scolaire_id' => AnneeScolaire::factory(),
            'date_inscription' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'type_abonnement' => $this->faker->randomElement(['journalier', 'hebdomadaire', 'mensuel']),
            'nombre_repas' => $this->faker->numberBetween(1, 22),
            'prix_total' => $this->faker->numberBetween(10000, 100000),
            'montant_paye' => $this->faker->numberBetween(0, 100000),
            'statut' => $this->faker->randomElement(['active', 'suspendue', 'terminee']),
        ];
    }
}
