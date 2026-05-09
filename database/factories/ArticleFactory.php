<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\GestionStock\Entities\Article;
use Modules\Business\Entities\PointVente;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'points_vente_id' => PointVente::factory(),
            'sku' => strtoupper($this->faker->bothify('SKU-#####')),
            'nom' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'prix_cents' => $this->faker->numberBetween(500, 50000),
            'devise' => 'XOF',
            'marque' => $this->faker->company(),
            'quantite_stock' => $this->faker->numberBetween(0, 200),
            'seuil_alert_stock' => 10,
            'taxes_json' => null,
            'checksum' => Str::random(32),
            'external_id' => null,
            'source_system' => 'test',
            'creation_hostname' => 'phpunit',
            'modification_hostname' => 'phpunit',
            'creation_username' => 'test',
            'modification_username' => 'test',
        ];
    }

    public function withStock(int $quantite): self
    {
        return $this->state(fn () => [
            'quantite_stock' => $quantite,
        ]);
    }

    public function outOfStock(): self
    {
        return $this->state(fn () => [
            'quantite_stock' => 0,
        ]);
    }

    public function lowStock(): self
    {
        return $this->state(fn () => [
            'quantite_stock' => 5,
            'seuil_alert_stock' => 10,
        ]);
    }

    public function forPointVente(PointVente $pointVente): self
    {
        return $this->state(fn () => [
            'points_vente_id' => $pointVente->id,
        ]);
    }
}
