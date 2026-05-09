<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finances\Entities\Frais;
use Modules\Finances\Entities\TypeFrais;
use Modules\GestionApprenants\Entities\Apprenant;
use Modules\Ecole\Entities\AnneeScolaire;

class FraisFactory extends Factory
{
    protected $model = Frais::class;

    public function definition(): array
    {
        $dateEcheance = $this->faker->dateTimeBetween('now', '+3 months');

        return [
            'apprenant_id' => Apprenant::factory(),
            'type_frais_id' => TypeFrais::factory(),
            'annee_scolaire_id' => AnneeScolaire::factory(),
            'montant' => $this->faker->numberBetween(10000, 500000),
            'montant_paye' => $this->faker->numberBetween(0, 500000),
            'date_facture' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'date_echeance' => $dateEcheance,
            'statut' => $this->faker->randomElement(['facture', 'partiellement_paye', 'paye', 'impaye']),
        ];
    }

    public function paye()
    {
        return $this->state(function (array $attributes) {
            return [
                'statut' => 'paye',
                'montant_paye' => $attributes['montant'],
            ];
        });
    }

    public function impaye()
    {
        return $this->state(function (array $attributes) {
            return [
                'statut' => 'impaye',
                'montant_paye' => 0,
            ];
        });
    }
}
