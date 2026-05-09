<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GestionApprenants\Entities\Tuteur;
use Modules\GestionApprenants\Entities\Apprenant;
use App\Models\User;

class TuteurFactory extends Factory
{
    protected $model = Tuteur::class;

    public function definition(): array
    {
        return [
            'apprenant_id' => Apprenant::factory(),
            'user_id' => User::factory(),
            'relation' => $this->faker->randomElement(['parent', 'tuteur', 'oncle', 'tante', 'grand-parent']),
            'profession' => $this->faker->jobTitle(),
            'employeur' => $this->faker->company(),
            'numero_urgence' => $this->faker->phoneNumber(),
        ];
    }
}
