<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ExamensNotes\Entities\Bulletin;
use Modules\GestionApprenants\Entities\Apprenant;
use Modules\Ecole\Entities\Classe;
use Modules\Ecole\Entities\AnneeScolaire;

class BulletinFactory extends Factory
{
    protected $model = Bulletin::class;

    public function definition(): array
    {
        return [
            'apprenant_id' => Apprenant::factory(),
            'classe_id' => Classe::factory(),
            'annee_scolaire_id' => AnneeScolaire::factory(),
            'trimestre' => $this->faker->randomElement([1, 2, 3]),
            'moyenne_generale' => $this->faker->randomFloat(2, 0, 20),
            'rang' => $this->faker->numberBetween(1, 50),
            'nombre_absences' => $this->faker->numberBetween(0, 10),
            'remarques' => $this->faker->optional()->paragraph(),
            'date_generation' => now(),
            'statut' => $this->faker->randomElement(['genere', 'valide', 'imprime']),
        ];
    }
}
