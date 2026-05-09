<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Documents\Entities\ModeleRapport;

class ModeleRapportFactory extends Factory
{
    protected $model = ModeleRapport::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->bothify('MODRAP-##??'),
            'nom' => $this->faker->words(3, true) . ' Rapport',
            'description' => $this->faker->paragraph(),
            'type_rapport' => $this->faker->randomElement(['activites', 'finances', 'pedagogie', 'ressources', 'securite']),
            'contenu_template' => json_encode($this->generateTemplate()),
            'sections' => json_encode(['Introduction', 'Développement', 'Conclusion']),
            'auteur_id' => null,
            'date_creation' => now(),
            'statut' => $this->faker->randomElement(['actif', 'inactif', 'archive']),
        ];
    }

    private function generateTemplate(): array
    {
        return [
            'titre' => 'Titre du rapport',
            'date' => date('Y-m-d'),
            'auteur' => 'Nom de l\'auteur',
            'contenu' => 'Contenu du rapport',
        ];
    }
}
