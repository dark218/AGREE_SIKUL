<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Parametrage\Entities\Fichier;

class FichierFactory extends Factory
{
    protected $model = Fichier::class;

    public function definition(): array
    {
        return [
            'nom' => $this->faker->word . '.' . $this->faker->fileExtension,
            'source' => $this->faker->filePath(),
        ];
    }
}