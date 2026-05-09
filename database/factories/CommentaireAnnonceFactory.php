<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Communication\Entities\CommentaireAnnonce;
use App\Models\User;
use Modules\Communication\Entities\Annonce;

class CommentaireAnnonceFactory extends Factory
{
    protected $model = CommentaireAnnonce::class;

    public function definition(): array
    {
        return [
            'annonce_id' => Annonce::factory(),
            'auteur_id' => User::factory(),
            'contenu' => $this->faker->paragraph(),
            'date_creation' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'statut' => $this->faker->randomElement(['visible', 'cache', 'signale']),
        ];
    }
}
