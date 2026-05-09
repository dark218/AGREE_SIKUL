<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Business\Entities\PointVente;
use Modules\GestionStock\Entities\TransfertStock;

class TransfertStockFactory extends Factory
{
    protected $model = TransfertStock::class;

    public function definition(): array
    {
        $sourceId = PointVente::factory()->create()->id;
        $destinationId = PointVente::factory()->create()->id;

        return [
            'reference' => 'TRF-' . $this->faker->unique()->numerify('######'),
            'emplacement_source_id' => $sourceId,
            'emplacement_destination_id' => $destinationId,
            'statut' => TransfertStock::STATUT_EN_COURS,
            'date_demande' => now(),
            'commentaire' => $this->faker->optional()->sentence(),
        ];
    }
}