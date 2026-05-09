<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finances\Entities\ConsultationInfirmerie;
use Modules\GestionApprenants\Entities\Apprenant;
use Modules\Ecole\Entities\Ecole;

class ConsultationInfirmerieFactory extends Factory
{
    protected $model = ConsultationInfirmerie::class;

    public function definition(): array
    {
        return [
            'apprenant_id' => Apprenant::factory(),
            'ecole_id' => Ecole::factory(),
            'date_consultation' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'heure_consultation' => $this->faker->time('H:i'),
            'motif' => $this->faker->randomElement(['Maladie', 'Blessure', 'Visite', 'Suivi', 'Urgence']),
            'diagnostic' => $this->faker->optional()->paragraph(),
            'traitement' => $this->faker->optional()->paragraph(),
            'observation' => $this->faker->optional()->sentence(),
            'nom_infirmier' => $this->faker->name(),
            'statut' => $this->faker->randomElement(['enregistree', 'en_cours', 'terminee']),
        ];
    }
}
