<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Documents\Entities\Document;
use Modules\Documents\Entities\CategorieDocument;
use App\Models\User;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'categorie_document_id' => CategorieDocument::factory(),
            'titre' => $this->faker->words(4, true),
            'slug' => $this->faker->slug(),
            'description' => $this->faker->paragraph(),
            'auteur_id' => User::factory(),
            'date_creation' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'date_modification' => $this->faker->optional()->dateTimeBetween('-3 months', 'now'),
            'version' => $this->faker->numberBetween(1, 5),
            'acces' => $this->faker->randomElement(['public', 'restreint', 'confidentiel']),
            'fichier_id' => null,
            'statut' => $this->faker->randomElement(['brouillon', 'publie', 'archive']),
        ];
    }
}
