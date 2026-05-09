<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GestionApprenants\Entities\Apprenant;

class ApprenantFactory extends Factory
{
    protected $model = Apprenant::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'matricule' => $this->faker->unique()->bothify('MAT-####'),
            'date_naissance' => $this->faker->dateTimeBetween('-20 years', '-5 years'),
            'lieu_naissance' => $this->faker->city(),
            'sexe' => $this->faker->randomElement(['M', 'F']),
            'nationalite' => 'Ivoirienne',
            'groupe_sanguin' => $this->faker->randomElement(['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-']),
            'statut' => 'actif',
            'photo_id' => null,
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => ['statut' => 'actif']);
    }

    public function suspended(): static
    {
        return $this->state(fn(array $attributes) => ['statut' => 'suspendu']);
    }

    public function excluded(): static
    {
        return $this->state(fn(array $attributes) => ['statut' => 'exclu']);
    }
}
