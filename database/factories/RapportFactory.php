<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Documents\Entities\Rapport;
use Modules\Documents\Entities\ModeleRapport;
use App\Models\User;

class RapportFactory extends Factory
{
    protected $model = Rapport::class;

    public function definition(): array
    {
        return [
            'modele_rapport_id' => ModeleRapport::factory(),
            'titre' => $this->faker->words(5, true),
            'auteur_id' => User::factory(),
            'date_debut_periode' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'date_fin_periode' => $this->faker->dateTimeBetween('now', '+1 month'),
            'contenu' => $this->faker->paragraph(5),
            'resume_executif' => $this->faker->paragraph(2),
            'date_generation' => now(),
            'date_validation' => $this->faker->optional(0.6)->dateTimeBetween('-1 month', 'now'),
            'validateur_id' => $this->faker->optional()->randomElement([null, User::factory()]),
            'fichier_id' => null,
            'statut' => $this->faker->randomElement(['brouillon', 'complete', 'en_validation', 'valide', 'archive']),
        ];
    }

    public function valide()
    {
        return $this->state(function (array $attributes) {
            return [
                'statut' => 'valide',
                'date_validation' => now(),
            ];
        });
    }
}
