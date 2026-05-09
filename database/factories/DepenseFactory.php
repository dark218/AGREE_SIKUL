<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finances\Entities\Depense;
use App\Models\User;

class DepenseFactory extends Factory
{
    protected $model = Depense::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->bothify('DEP-##??'),
            'libelle' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'montant' => $this->faker->numberBetween(10000, 500000),
            'categorie' => $this->faker->randomElement(['fournitures', 'maintenance', 'salaires', 'utilitaires', 'investissement']),
            'date_depense' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'autorisant_id' => User::factory(),
            'responsable_id' => User::factory(),
            'mode_paiement' => $this->faker->randomElement(['especes', 'cheque', 'virement']),
            'statut' => $this->faker->randomElement(['enregistree', 'approuvee', 'paye']),
        ];
    }
}
