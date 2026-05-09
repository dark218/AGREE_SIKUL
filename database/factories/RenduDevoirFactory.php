<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Devoirs\Entities\RenduDevoir;
use Modules\GestionApprenants\Entities\Apprenant;
use Modules\Devoirs\Entities\Devoir;

class RenduDevoirFactory extends Factory
{
    protected $model = RenduDevoir::class;

    public function definition(): array
    {
        return [
            'apprenant_id' => Apprenant::factory(),
            'devoir_id' => Devoir::factory(),
            'date_rendu' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'contenu' => $this->faker->paragraph(),
            'note' => $this->faker->optional()->randomFloat(2, 0, 20),
            'commentaire_correcteur' => $this->faker->optional()->paragraph(),
            'statut' => $this->faker->randomElement(['rendu', 'en_attente', 'accepte', 'rejete', 'corrige']),
            'fichier_id' => null,
        ];
    }
}
