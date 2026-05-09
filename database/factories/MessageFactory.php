<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Communication\Entities\Message;
use App\Models\User;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'expediteur_id' => User::factory(),
            'destinataire_id' => User::factory(),
            'objet' => $this->faker->words(5, true),
            'contenu' => $this->faker->paragraph(),
            'date_envoi' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'date_lecture' => $this->faker->optional(0.7)->dateTimeBetween('-3 months', 'now'),
            'type' => $this->faker->randomElement(['information', 'notification', 'question', 'autre']),
            'statut' => $this->faker->randomElement(['envoye', 'lu', 'archive']),
        ];
    }

    public function lu()
    {
        return $this->state(function (array $attributes) {
            return [
                'statut' => 'lu',
                'date_lecture' => now(),
            ];
        });
    }
}
