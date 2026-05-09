<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finances\Entities\Paiement;
use Modules\Finances\Entities\Frais;
use App\Models\User;

class PaiementFactory extends Factory
{
    protected $model = Paiement::class;

    public function definition(): array
    {
        return [
            'frais_id' => Frais::factory(),
            'montant' => $this->faker->numberBetween(10000, 500000),
            'date_paiement' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'mode_paiement' => $this->faker->randomElement(['especes', 'cheque', 'virement', 'mobile', 'carte_bancaire']),
            'reference_paiement' => $this->faker->unique()->numerify('PAY-########'),
            'recu_genere' => $this->faker->boolean(80),
            'numero_recu' => $this->faker->optional()->numerify('REC-########'),
            'caissier_id' => User::factory(),
            'statut' => $this->faker->randomElement(['effectue', 'valide', 'rejete']),
        ];
    }

    public function valide()
    {
        return $this->state(function (array $attributes) {
            return [
                'statut' => 'valide',
                'recu_genere' => true,
            ];
        });
    }
}
