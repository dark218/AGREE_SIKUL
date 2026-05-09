<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\EnseignantsRH\Entities\PersonnelAdministratif;
use App\Models\User;

class PersonnelAdministratifFactory extends Factory
{
    protected $model = PersonnelAdministratif::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'matricule' => $this->faker->unique()->numerify('PADM-####'),
            'poste' => $this->faker->randomElement(['Directeur', 'Secrétaire', 'Comptable', 'Gardien', 'Chauffeur', 'Cuisinier', 'Nettoyeur']),
            'date_embauche' => $this->faker->dateTimeBetween('-10 years', 'now'),
            'type_contrat' => $this->faker->randomElement(['CDI', 'CDD']),
            'statut' => $this->faker->randomElement(['actif', 'inactif']),
        ];
    }
}
