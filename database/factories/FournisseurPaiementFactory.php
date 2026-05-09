<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Parametrage\Entities\FournisseurPaiement;
use Modules\Parametrage\Entities\PaysDevise;

class FournisseurPaiementFactory extends Factory
{
    protected $model = FournisseurPaiement::class;

    public function definition(): array
    {
        return [
            'nom' => $this->faker->company,
            'code' => $this->faker->unique()->lexify('FP???'),
            'type' => $this->faker->randomElement(['mm', 'bank', 'microfinance', 'aggregator', 'card']),
            'pays_devise_id' => PaysDevise::factory(),
            'config' => [
                'api_url' => $this->faker->url,
                'timeout' => 30,
            ],
            'settlement_config' => [
                'auto_settlement' => true,
                'settlement_delay' => 24,
            ],
            'statut' => $this->faker->randomElement(['actif', 'inactif']),
            'metadata' => [
                'description' => $this->faker->sentence,
            ],
        ];
    }
}