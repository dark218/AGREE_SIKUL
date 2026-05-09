<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Bibliotheque\Entities\Emprunt;
use Modules\GestionApprenants\Entities\Apprenant;
use Modules\Bibliotheque\Entities\Exemplaire;

class EmpruntFactory extends Factory
{
    protected $model = Emprunt::class;

    public function definition(): array
    {
        $dateEmprunt = $this->faker->dateTimeBetween('-6 months', 'now');
        $dateEcheance = $this->faker->dateTimeBetween($dateEmprunt, '+30 days');

        return [
            'apprenant_id' => Apprenant::factory(),
            'exemplaire_id' => Exemplaire::factory(),
            'date_emprunt' => $dateEmprunt,
            'date_echeance' => $dateEcheance,
            'date_retour' => $this->faker->optional(0.7)->dateTimeBetween($dateEmprunt, '+60 days'),
            'nombre_prolongation' => $this->faker->numberBetween(0, 3),
            'amende' => $this->faker->optional(0.2)->numberBetween(1000, 50000),
            'statut' => $this->faker->randomElement(['en_cours', 'retourne', 'perdu', 'endommage']),
        ];
    }

    public function retourne()
    {
        return $this->state(function (array $attributes) {
            return [
                'statut' => 'retourne',
                'date_retour' => now(),
            ];
        });
    }
}
