<?php

namespace Database\Factories;

use App\Services\Generator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Pos\Entities\VentePos;
use Modules\Business\Entities\Employe;
use Modules\Business\Entities\PointVente;
use Modules\Pos\Entities\SessionCaisse;

class VentePosFactory extends Factory
{
    protected $model = VentePos::class;

    public function definition(): array
    {
        return [
            'reference' => 'VP-' . $this->faker->unique()->numerify('######'),
            'points_vente_id' => PointVente::factory(),
            'sessions_caisse_id' => SessionCaisse::factory(),
            'uuid' => Generator::uuid(),
            'employe_id' => Employe::factory(),
            'total_cents' => 1000,
            'devise' => 'XOF',
            'mode_paiement' => 'espece',
            'statut' => config('appconstants.statut_vente_pos.en_attente'),
            'refund_cents' => 0,
            'total_rembourse_cents' => 0,
        ];
    }

    public function validee(): static
    {
        return $this->state(fn () => [
            'statut' => config('appconstants.statut_vente_pos.validee'),
        ]);
    }
}
