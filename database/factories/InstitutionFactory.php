<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Institution\Entities\Institution;

class InstitutionFactory extends Factory
{
    protected $model = Institution::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->bothify('INST-####'),
            'nom' => $this->faker->company(),
            'sigle' => strtoupper($this->faker->bothify('??')),
            'type' => $this->faker->randomElement(['universite', 'ecole_primaire', 'college', 'lycee']),
            'statut_juridique' => $this->faker->randomElement(['publique', 'privee', 'semi_privee']),
            'email' => $this->faker->unique()->companyEmail(),
            'telephone' => $this->faker->phoneNumber(),
            'site_web' => $this->faker->url(),
            'adresse' => $this->faker->address(),
            'vision' => $this->faker->text(100),
            'mission' => $this->faker->text(100),
            'valeurs' => json_encode(['Excellence', 'Intégrité', 'Innovation', 'Respect']),
            'directeur_general_id' => null,
            'devise_principale' => 'XOF',
            'langues_officielles' => json_encode(['fr', 'en']),
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function active(): static
    {
        return $this->state(function (array $attributes) {
            return ['statut' => 'actif'];
        });
    }

    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return ['statut' => 'inactif'];
        });
    }
}
