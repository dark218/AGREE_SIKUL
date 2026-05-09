<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ServiceClient\Entities\MoyenPaiement;
use Modules\Parametrage\Entities\FournisseurPaiement;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\ServiceClient\Entities\MoyenPaiement>
 */
class MoyenPaiementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MoyenPaiement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [MoyenPaiement::TYPE_MOBILE_MONEY, MoyenPaiement::TYPE_IBAN, MoyenPaiement::TYPE_CARD, MoyenPaiement::TYPE_WALLET];
        $type = $this->faker->randomElement($types);
        
        $identifiant = match($type) {
            MoyenPaiement::TYPE_MOBILE_MONEY => $this->faker->numerify('070######'),
            MoyenPaiement::TYPE_IBAN => $this->faker->iban('CI'),
            MoyenPaiement::TYPE_CARD => $this->faker->creditCardNumber(),
            MoyenPaiement::TYPE_WALLET => $this->faker->bothify('WAL########'),
        };

        return [
            'users_id' => User::factory(),
            'fournisseur_id' => FournisseurPaiement::factory(),
            'type' => $type,
            'label' => $this->faker->company() . ' - ' . $type,
            'external_id' => $this->faker->uuid(),
            'identifiant_chiffre' => $identifiant,
            'token_provider' => $this->faker->sha256(),
            'is_defaut' => false,
            'statut' => MoyenPaiement::STATUT_ACTIF,
            'metadata' => [
                'country_code' => $this->faker->countryCode(),
                'currency' => $this->faker->currencyCode(),
                'provider_ref' => $this->faker->uuid(),
            ],
        ];
    }

    /**
     * Indicate that the payment method is the default one.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_defaut' => true,
        ]);
    }

    /**
     * Indicate that the payment method is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => MoyenPaiement::STATUT_DESACTIVE,
        ]);
    }

    /**
     * Create a mobile money payment method.
     */
    public function mobileMoney(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => MoyenPaiement::TYPE_MOBILE_MONEY,
            'identifiant_chiffre' => $this->faker->numerify('070######'),
        ]);
    }

    /**
     * Create an IBAN payment method.
     */
    public function iban(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => MoyenPaiement::TYPE_IBAN,
            'identifiant_chiffre' => $this->faker->iban('CI'),
        ]);
    }

    /**
     * Create a card payment method.
     */
    public function card(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => MoyenPaiement::TYPE_CARD,
            'identifiant_chiffre' => $this->faker->creditCardNumber(),
        ]);
    }

    /**
     * Create a wallet payment method.
     */
    public function wallet(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => MoyenPaiement::TYPE_WALLET,
            'identifiant_chiffre' => $this->faker->bothify('WAL########'),
        ]);
    }

    /**
     * Create a payment method for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'users_id' => $user->id,
        ]);
    }

    /**
     * Create a payment method for a specific provider.
     */
    public function forProvider(FournisseurPaiement $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'fournisseur_id' => $provider->id,
        ]);
    }
}
