<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Communication\Entities\Annonce;
use App\Models\User;

class AnnonceFactory extends Factory
{
    protected $model = Annonce::class;

    public function definition(): array
    {
        return [
            'auteur_id' => User::factory(),
            'titre' => $this->faker->words(5, true),
            'contenu' => $this->faker->paragraph(3),
            'date_publication' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'date_expiration' => $this->faker->optional(0.5)->dateTimeBetween('now', '+3 months'),
            'categorie' => $this->faker->randomElement(['info', 'urgent', 'evenement', 'academique']),
            'destinataires' => 'tous',
            'statut' => $this->faker->randomElement(['publiee', 'brouillon', 'archive']),
        ];
    }

    public function publiee()
    {
        return $this->state(function (array $attributes) {
            return [
                'statut' => 'publiee',
            ];
        });
    }
}
