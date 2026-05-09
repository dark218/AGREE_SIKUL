<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecole\Entities\AnneeScolaire;

class AnneeScolaireFactory extends Factory
{
    protected $model = AnneeScolaire::class;

    public function definition(): array
    {
        $dateDebut = $this->faker->dateTimeBetween('-3 years', '+3 years');
        $dateFin = $this->faker->dateTimeBetween($dateDebut, '+1 year');

        return [
            'libelle' => $dateDebut->format('Y') . '-' . $dateFin->format('Y'),
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'est_courante' => false,
            'statut' => $this->faker->randomElement(['planifiee', 'en_cours', 'terminee']),
        ];
    }

    public function courante()
    {
        return $this->state(function (array $attributes) {
            return [
                'est_courante' => true,
                'statut' => 'en_cours',
                'date_debut' => now()->subMonths(3),
                'date_fin' => now()->addMonths(9),
            ];
        });
    }
}
