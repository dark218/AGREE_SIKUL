<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Devoirs\Entities\Devoir;
use Modules\CoursHoraires\Entities\Cours;

class DevoirFactory extends Factory
{
    protected $model = Devoir::class;

    public function definition(): array
    {
        $dateCreation = $this->faker->dateTimeBetween('-3 months', 'now');
        $dateEcheance = $this->faker->dateTimeBetween($dateCreation, '+15 days');

        return [
            'cours_id' => Cours::factory(),
            'titre' => $this->faker->words(4, true),
            'description' => $this->faker->paragraph(),
            'date_creation' => $dateCreation,
            'date_echeance' => $dateEcheance,
            'type' => $this->faker->randomElement(['exercice', 'projet', 'recherche', 'lecture']),
            'note_max' => 20,
            'statut' => $this->faker->randomElement(['ouvert', 'en_cours', 'ferme', 'corrige']),
        ];
    }
}
