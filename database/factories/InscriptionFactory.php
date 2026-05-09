<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GestionApprenants\Entities\Inscription;
use Modules\GestionApprenants\Entities\Apprenant;
use Modules\Ecole\Entities\Classe;
use Modules\Ecole\Entities\AnneeScolaire;

class InscriptionFactory extends Factory
{
    protected $model = Inscription::class;

    public function definition(): array
    {
        return [
            'apprenant_id' => Apprenant::factory(),
            'classe_id' => Classe::factory(),
            'annee_scolaire_id' => AnneeScolaire::factory(),
            'date_inscription' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'numero_inscription' => $this->faker->unique()->numerify('INS-########'),
            'type_inscription' => $this->faker->randomElement(['nouveau', 'redouble', 'transfert']),
            'statut' => $this->faker->randomElement(['en_attente', 'validee', 'rejetee']),
        ];
    }

    public function validee()
    {
        return $this->state(function (array $attributes) {
            return [
                'statut' => 'validee',
            ];
        });
    }
}
