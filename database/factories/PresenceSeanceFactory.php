<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Presence\Entities\PresenceSeance;
use Modules\GestionApprenants\Entities\Apprenant;
use Modules\CoursHoraires\Entities\Seance;

class PresenceSeanceFactory extends Factory
{
    protected $model = PresenceSeance::class;

    public function definition(): array
    {
        return [
            'apprenant_id' => Apprenant::factory(),
            'seance_id' => Seance::factory(),
            'present' => $this->faker->boolean(80),
            'date_marquage' => now(),
            'remarques' => $this->faker->optional()->sentence(),
        ];
    }

    public function present()
    {
        return $this->state(function (array $attributes) {
            return [
                'present' => true,
            ];
        });
    }

    public function absent()
    {
        return $this->state(function (array $attributes) {
            return [
                'present' => false,
            ];
        });
    }
}
