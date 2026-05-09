<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Business\Entities\Caisse;
use Modules\Business\Entities\PointVente;

class CaisseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Caisse::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['physique', 'mobile', 'virtuelle'];
        $statuts = ['ouverte', 'fermee', 'bloquee'];

        return [
            'points_vente_id' => PointVente::factory(),
            'code' => 'CAISSE-' . $this->faker->unique()->numberBetween(1000, 9999),
            'nom' => 'Caisse ' . $this->faker->unique()->word,
            'type' => $this->faker->randomElement($types),
            'statut' => $this->faker->randomElement($statuts),
            'parametres_json' => [
                'fond_caisse' => $this->faker->randomFloat(2, 0, 10000),
                'devise' => 'XOF',
                'imprimante' => $this->faker->boolean(70) ? 'Thermique' : null,
            ],
        ];
    }

    /**
     * Indique que la caisse est ouverte
     */
    public function ouverte(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'ouverte',
        ]);
    }

    /**
     * Indique que la caisse est fermée
     */
    public function fermee(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'fermee',
        ]);
    }

    /**
     * Indique que la caisse est bloquée
     */
    public function bloquee(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'bloquee',
        ]);
    }

    /**
     * Spécifie le type de caisse
     */
    public function type(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    /**
     * Spécifie le point de vente
     */
    public function pourPointDeVente($pointVenteId): static
    {
        return $this->state(fn (array $attributes) => [
            'points_vente_id' => $pointVenteId,
        ]);
    }
}
