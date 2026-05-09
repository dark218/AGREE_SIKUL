<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finances\Entities\PassageCantine;
use Modules\GestionApprenants\Entities\Apprenant;
use Modules\Finances\Entities\ServiceCantine;

class PassageCantineFactory extends Factory
{
    protected $model = PassageCantine::class;

    public function definition(): array
    {
        return [
            'apprenant_id' => Apprenant::factory(),
            'service_cantine_id' => ServiceCantine::factory(),
            'date_passage' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'heure_passage' => $this->faker->time('H:i', '12:00'),
            'plat_servi' => $this->faker->randomElement(['Thiéboudienne', 'Yassa', 'Mafé', 'Couscous']),
            'montant_repas' => $this->faker->numberBetween(2000, 5000),
            'statut' => $this->faker->randomElement(['servi', 'absent', 'annule']),
        ];
    }
}
