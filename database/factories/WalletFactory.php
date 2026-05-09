<?php

namespace Database\Factories;

use App\Models\User;
use App\Services\MoneyService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Parametrage\Entities\PaysDevise;
use Modules\Wallet\Entities\Wallet;

class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'owner_type' => $this->faker->randomElement([
                Wallet::OWNER_TYPE_UTILISATEUR,
                Wallet::OWNER_TYPE_MARCHAND,
                Wallet::OWNER_TYPE_AGENT,
                Wallet::OWNER_TYPE_CLIENT,
            ]),
            'pays_devise_id' => PaysDevise::factory(),
            'solde_cents' => MoneyService::toDatabase($this->faker->randomFloat(2, 0, 10000), 'XOF'),
            'solde_bloque_cents' => MoneyService::toDatabase($this->faker->randomFloat(2, 0, 500), 'XOF'),
            'solde_commission_cents' => MoneyService::toDatabase($this->faker->randomFloat(2, 0, 200), 'XOF'),
            'solde_attente_cents' => MoneyService::toDatabase($this->faker->randomFloat(2, 0, 300), 'XOF'),
            'statut' => $this->faker->randomElement([
                Wallet::STATUT_ACTIF,
                Wallet::STATUT_SUSPENDU,
                Wallet::STATUT_FERME,
            ]),
            'meta_json' => null,
        ];
    }

    public function actif(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => Wallet::STATUT_ACTIF,
        ]);
    }

    public function suspendu(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => Wallet::STATUT_SUSPENDU,
        ]);
    }
}