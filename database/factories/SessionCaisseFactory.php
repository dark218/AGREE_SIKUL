<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Pos\Entities\SessionCaisse;
use Modules\Business\Entities\Employe;
use Modules\Business\Entities\Caisse;

class SessionCaisseFactory extends Factory
{
    protected $model = SessionCaisse::class;

    public function definition(): array
    {
        return [
            'reference' => 'SC-' . $this->faker->unique()->numerify('######'),
            'caissier_id' => Employe::factory(),
            'caisse_id' => Caisse::factory(),
            'statut' => config('appconstants.session_caisse_statut.attente'),
            'fond_ouverture_cents' => 0,
            'total_encaisse_cents' => 0,
            'total_reel_cents' => null,
            'ecart_cents' => null,
            'opened_at' => null,
            'closed_at' => null,
            'devise' => 'XOF',
        ];
    }

    public function ouverte(): static
    {
        return $this->state(fn () => [
            'statut' => config('appconstants.session_caisse_statut.ouverte'),
            'opened_at' => now(),
        ]);
    }

    public function fermee(): static
    {
        return $this->state(fn () => [
            'statut' => config('appconstants.session_caisse_statut.fermee'),
            'closed_at' => now(),
        ]);
    }
}
