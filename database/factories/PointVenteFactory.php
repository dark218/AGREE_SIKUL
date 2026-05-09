<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Business\Entities\PointVente;
use Modules\Business\Entities\Marchand;
use Modules\Parametrage\Entities\Zone;

class PointVenteFactory extends Factory
{
    protected $model = PointVente::class;

    public function definition(): array
    {
        return [
            'marchand_id' => Marchand::factory(),
            'nom' => 'PV ' . strtoupper(Str::random(5)),
            'adresse' => $this->faker->address,
            'telephone' => $this->faker->unique()->numerify('##########'),
            'longitude' => $this->faker->randomFloat(8,-99,99),
            'latitude' => $this->faker->randomFloat(8,-99,99),
            'param_pos_json' => [
                'type' => 'point_vente',
            ],
            'photo_id' => null,
            'zone_id' => Zone::factory(),
            'statut' => config('appconstants.pointvente_statut.actif'),
            'motif' => null,
            'validated_at' => now(),
            'validated_by' => null,
            'blocked_by' => null,
            'suspended_by' => null,
            'create_by' => null,
            'parent_points_vente_id' => null,
        ];
    }

    public function actif(): self
    {
        return $this->state(fn () => [
            'statut' => config('appconstants.pointvente_statut.actif'),
        ]);
    }

    public function inactif(): self
    {
        return $this->state(fn () => [
            'statut' => config('appconstants.pointvente_statut.non_actif'),
        ]);
    }

    public function caisse(PointVente $parent): self
    {
        return $this->state(fn () => [
            'parent_points_vente_id' => $parent->id,
            'param_pos_json' => [
                'type' => 'caisse',
            ],
        ]);
    }

    public function forMarchand(Marchand $marchand): self
    {
        return $this->state(fn () => [
            'marchand_id' => $marchand->id,
        ]);
    }
}
