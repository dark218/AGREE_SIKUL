<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CoursHoraires\Entities\Matiere;
use Modules\Ecole\Entities\Ecole;

class MatiereFactory extends Factory
{
    protected $model = Matiere::class;

    public function definition(): array
    {
        return [
            'ecole_id' => Ecole::factory(),
            'code' => $this->faker->unique()->bothify('MAT-##??'),
            'libelle' => $this->faker->randomElement(['Mathématiques', 'Français', 'Anglais', 'Physique', 'Chimie', 'SVT', 'Histoire', 'Géographie', 'Philosophie', 'Education Civique', 'EPS', 'Informatique']),
            'coefficient' => $this->faker->randomElement([1, 1.5, 2, 2.5, 3]),
            'couleur' => $this->faker->hexColor(),
            'categorie' => $this->faker->randomElement(['scientifique', 'lettres', 'pratique']),
            'statut' => $this->faker->randomElement(['actif', 'inactif']),
        ];
    }
}
