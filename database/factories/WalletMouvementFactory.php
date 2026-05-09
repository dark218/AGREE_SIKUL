<?php

namespace Database\Factories;

use App\Models\User;
use App\Services\MoneyService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Business\Entities\PointVente;
use Modules\Wallet\Entities\Wallet;
use Modules\Wallet\Entities\WalletMouvement;
use Modules\Wallet\Enums\WalletSourceType;

class WalletMouvementFactory extends Factory
{
    protected $model = WalletMouvement::class;

    public function definition(): array
    {
        // Solde avant TOUJOURS >= 0
        $soldeAvantUser = $this->faker->randomFloat(2, 0, 5000);
        $soldeAvant = MoneyService::toDatabase($soldeAvantUser, 'XOF');

        // Type de mouvement
        $type = $this->faker->randomElement([
            WalletMouvement::TYPE_CREDIT,
            WalletMouvement::TYPE_DEBIT,
            WalletMouvement::TYPE_BLOCAGE,
            WalletMouvement::TYPE_DEBLOCAGE,
            WalletMouvement::TYPE_COMMISSION,
            WalletMouvement::TYPE_REMBOURSEMENT,
            WalletMouvement::TYPE_AJUSTEMENT,
        ]);

        // Génération du montant en fonction du type
        $montant = match ($type) {
            WalletMouvement::TYPE_CREDIT,
            WalletMouvement::TYPE_DEBLOCAGE,
            WalletMouvement::TYPE_REMBOURSEMENT =>
            MoneyService::toDatabase(
                $this->faker->randomFloat(2, 1, 1000),
                'XOF'
            ),

            default =>
            -MoneyService::toDatabase(
                $this->faker->randomFloat(2, 1, min(500, $soldeAvantUser)),
                'XOF'
            ),
        };

        // Calcul du solde après (GARANTI >= 0)
        $soldeApres = $soldeAvant + $montant;

        // Sécurité ultime
        if ($soldeApres < 0) {
            $soldeApres = 0;
        }

        return [
            'wallet_id' => Wallet::factory(),
            'users_id' => User::factory(),
            'type_mouvement' => $type,
            'montant_cents' => $montant,
            'solde_avant_cents' => $soldeAvant,
            'solde_apres_cents' => $soldeApres,
            'emplacement_id' => $this->faker->boolean(70) ? PointVente::factory() : null,
            'reference' => $this->faker->unique()->regexify('[A-Z0-9]{10}'),
            'source_type' => $this->faker->randomElement(WalletSourceType::all()),
            'source_id' => $this->faker->numberBetween(1, 1000),
            'meta_json' => null,
        ];
    }


    public function credit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_mouvement' => WalletMouvement::TYPE_CREDIT,
            'montant_cents' => MoneyService::toDatabase($this->faker->randomFloat(2, 10, 1000), 'XOF'),
        ]);
    }

    public function debit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_mouvement' => WalletMouvement::TYPE_DEBIT,
            'montant_cents' => -MoneyService::toDatabase($this->faker->randomFloat(2, 10, 500), 'XOF'),
        ]);
    }
}
