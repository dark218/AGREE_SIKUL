<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CoursHoraires\Entities\EmploiTemps;
use Modules\Ecole\Entities\Classe;
use Modules\Ecole\Entities\AnneeScolaire;

class EmploiTempsFactory extends Factory
{
    protected $model = EmploiTemps::class;

    public function definition(): array
    {
        return [
            'classe_id' => Classe::factory(),
            'annee_scolaire_id' => AnneeScolaire::factory(),
            'date_valeur' => $this->faker->dateTimeBetween('-6 months', '+6 months'),
            'nom' => 'Emploi du temps ' . $this->faker->name(),
            'contenu' => json_encode($this->generateEmploiTempsData()),
            'statut' => $this->faker->randomElement(['actif', 'inactif', 'archive']),
        ];
    }

    private function generateEmploiTempsData(): array
    {
        $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
        $data = [];

        foreach ($jours as $jour) {
            $data[$jour] = [
                ['heure' => '08:00-09:00', 'matiere' => 'Mathématiques'],
                ['heure' => '09:00-10:00', 'matiere' => 'Français'],
                ['heure' => '10:15-11:15', 'matiere' => 'Anglais'],
                ['heure' => '14:00-15:00', 'matiere' => 'Sciences'],
            ];
        }

        return $data;
    }
}
