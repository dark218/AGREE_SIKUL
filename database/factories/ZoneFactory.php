<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Parametrage\Entities\Zone;
use Modules\Parametrage\Entities\Pays;

class ZoneFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Zone::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['commerciale', 'region', 'secteur'];

        return [
            'libelle' => $this->faker->city,
            'pays_id' => Pays::factory(),
            'type_zone' => $this->faker->randomElement($types),
            'centroid_lat' => $this->faker->latitude,
            'centroid_lng' => $this->faker->longitude,
            'polygon_geojson' => null, // Vous pouvez ajouter un GeoJSON valide si nécessaire
            'description' => $this->faker->sentence,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the zone is of type 'commerciale'.
     */
    public function commerciale(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_zone' => 'commerciale',
        ]);
    }

    /**
     * Indicate that the zone is of type 'region'.
     */
    public function region(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_zone' => 'region',
        ]);
    }

    /**
     * Indicate that the zone is of type 'secteur'.
     */
    public function secteur(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_zone' => 'secteur',
        ]);
    }
}
