<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Communication\Entities\Notification;
use App\Models\User;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'utilisateur_id' => User::factory(),
            'titre' => $this->faker->words(4, true),
            'message' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['info', 'alerte', 'erreur', 'succes']),
            'url_action' => $this->faker->optional()->url(),
            'date_creation' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'date_lecture' => $this->faker->optional(0.6)->dateTimeBetween('-3 months', 'now'),
            'statut' => $this->faker->randomElement(['non_lue', 'lue', 'archive']),
        ];
    }

    public function lue()
    {
        return $this->state(function (array $attributes) {
            return [
                'statut' => 'lue',
                'date_lecture' => now(),
            ];
        });
    }
}
