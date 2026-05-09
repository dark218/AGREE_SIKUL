<?php

namespace Database\Factories;

use App\Models\User;
use App\Services\MoneyService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Business\Entities\Marchand;
use Modules\Business\Entities\PointVente;
use Modules\Parametrage\Entities\FournisseurPaiement;
use Modules\Wallet\Entities\Transactions;

class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Transactions::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Création des relations nécessaires
        $payer = User::factory()->create();
        $marchand = Marchand::factory()->create();
        $pointVente = PointVente::factory()->create(['marchand_id' => $marchand->id]);
        $fournisseurPaiement = FournisseurPaiement::factory()->create();

        // Valeurs par défaut
        $devise = 'XOF';
        $montant = $this->faker->numberBetween(1000, 100000); // 1 000 à 100 000 FCFA
        $montantCents = MoneyService::toDatabase($montant, $devise); // Conversion en centimes
        $now = now();

        // Génération des dates en fonction du statut
        $initiatedAt = $this->faker->dateTimeBetween('-1 year', $now);
        $confirmedAt = null;
        $failedAt = null;

        // Sélection aléatoire du type de source
        $sourceType = $this->faker->randomElement([
            Transactions::SOURCE_VENTE_POS,
            Transactions::SOURCE_REMBOURSEMENT,
            Transactions::SOURCE_LIEN,
            Transactions::SOURCE_ABONNEMENT,
        ]);

        // Sélection aléatoire du statut
        $statut = $this->faker->randomElement([
            Transactions::STATUT_INITIEE,
            Transactions::STATUT_EN_ATTENTE,
            Transactions::STATUT_REUSSIE,
            Transactions::STATUT_ECHOUEE,
            Transactions::STATUT_ANNULEE,
            Transactions::STATUT_REMBOURSEE,
        ]);

        // Ajustement des dates en fonction du statut
        if (in_array($statut, [Transactions::STATUT_REUSSIE, Transactions::STATUT_REMBOURSEE])) {
            $confirmedAt = $this->faker->dateTimeBetween($initiatedAt, $now);
        } elseif ($statut === Transactions::STATUT_ECHOUEE) {
            $failedAt = $this->faker->dateTimeBetween($initiatedAt, $now);
        }

        return [
            'uuid' => $this->faker->uuid,
            'payer_id' => $payer->id,
            'marchand_id' => $marchand->id,
            'points_vente_id' => $pointVente->id,
            'source_type' => $sourceType,
            'source_id' => $this->faker->numberBetween(1, 1000),
            'fournisseur_paiement_id' => $fournisseurPaiement->id,
            'montant_cents' => $montantCents, // Montant déjà converti en centimes
            'devise' => $devise,
            'statut' => $statut,
            'reference_externe' => 'TRX' . $this->faker->unique()->numberBetween(100000, 999999),
            'meta_json' => null,
            'initiated_at' => $initiatedAt,
            'confirmed_at' => $confirmedAt,
            'failed_at' => $failedAt,
        ];
    }

    /**
     * État : Transactions initiée
     */
    public function initiee(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => Transactions::STATUT_INITIEE,
            'initiated_at' => now(),
            'confirmed_at' => null,
            'failed_at' => null,
        ]);
    }

    /**
     * État : Transactions en attente
     */
    public function enAttente(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => Transactions::STATUT_EN_ATTENTE,
            'initiated_at' => now()->subMinutes(5),
            'confirmed_at' => null,
            'failed_at' => null,
        ]);
    }

    /**
     * État : Transactions réussie
     */
    public function reussie(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => Transactions::STATUT_REUSSIE,
            'initiated_at' => now()->subMinutes(15),
            'confirmed_at' => now()->subMinutes(10),
            'failed_at' => null,
        ]);
    }

    /**
     * État : Transactions échouée
     */
    public function echouee(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => Transactions::STATUT_ECHOUEE,
            'initiated_at' => now()->subMinutes(15),
            'confirmed_at' => null,
            'failed_at' => now()->subMinutes(10),
        ]);
    }

    /**
     * État : Transactions annulée
     */
    public function annulee(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => Transactions::STATUT_ANNULEE,
            'initiated_at' => now()->subMinutes(15),
            'confirmed_at' => null,
            'failed_at' => null,
        ]);
    }

    /**
     * État : Transactions remboursée
     */
    public function remboursee(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => Transactions::STATUT_REMBOURSEE,
            'initiated_at' => now()->subDays(2),
            'confirmed_at' => now()->subDays(1),
            'failed_at' => null,
        ]);
    }

    /**
     * Type de source : Vente en point de vente
     */
    public function ventePos(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_type' => Transactions::SOURCE_VENTE_POS,
        ]);
    }

    /**
     * Type de source : Remboursement
     */
    public function remboursement(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_type' => Transactions::SOURCE_REMBOURSEMENT,
        ]);
    }

    /**
     * Type de source : Lien de paiement
     */
    public function lienPaiement(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_type' => Transactions::SOURCE_LIEN,
        ]);
    }

    /**
     * Type de source : Abonnement
     */
    public function abonnement(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_type' => Transactions::SOURCE_ABONNEMENT,
        ]);
    }

    /**
     * Spécifie un montant spécifique pour la transaction
     *
     * @param float $montant Montant en unité de la devise (ex: 1000 pour 10.00)
     * @return static
     */
    public function avecMontant(float $montant): static
    {
        $devise = $attributes['devise'] ?? 'XOF';
        return $this->state(fn (array $attributes) => [
            'montant_cents' => MoneyService::toDatabase($montant, $devise),
        ]);
    }

    /**
     * Spécifie une devise spécifique pour la transaction
     *
     * @param string $devise Code de la devise (ex: 'XOF', 'EUR', 'USD')
     * @return static
     */
    public function avecDevise(string $devise): static
    {
        return $this->state(fn (array $attributes) => [
            'devise' => $devise,
        ]);
    }
}
