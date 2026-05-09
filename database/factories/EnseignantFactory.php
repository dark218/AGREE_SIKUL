<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\EnseignantsRH\Entities\Enseignant;

class EnseignantFactory extends Factory
{
    protected $model = Enseignant::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'matricule' => $this->faker->unique()->bothify('ENS-####'),
            'specialite' => $this->faker->randomElement(['Mathématiques', 'Français', 'Anglais', 'Sciences', 'Histoire-Géographie', 'Informatique']),
            'diplome' => $this->faker->randomElement(['Licence', 'Master', 'Doctorat', 'CAPED']),
            'date_embauche' => $this->faker->dateTimeBetween('-15 years', 'now'),
            'type_contrat' => $this->faker->randomElement(['cdi', 'cdd', 'vacataire']),
            'statut' => 'actif',
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => ['statut' => 'actif']);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => ['statut' => 'inactif']);
    }
}
