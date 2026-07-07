<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CoursHoraires\Entities\Cours;
use Modules\CoursHoraires\Entities\Matiere;
use Modules\Ecole\Entities\Classe;
use App\Models\User;

class CoursFactory extends Factory
{
    protected $model = Cours::class;

    public function definition(): array
    {
        return [
            'matiere_id' => MatiereUnite::factory(),
            'classe_id' => Classe::factory(),
            'enseignant_id' => User::factory(),
            'annee_scolaire_id' => null,
            'volume_horaire' => $this->faker->numberBetween(1, 4),
            'statut' => $this->faker->randomElement(['actif', 'inactif']),
        ];
    }
}
