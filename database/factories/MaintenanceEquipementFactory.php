<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventaire\Entities\MaintenanceEquipement;
use Modules\Inventaire\Entities\Equipement;

class MaintenanceEquipementFactory extends Factory
{
    protected $model = MaintenanceEquipement::class;

    public function definition(): array
    {
        $dateDebut = $this->faker->dateTimeBetween('-6 months', 'now');
        $dateFinMaintenance = $this->faker->optional(0.7)->dateTimeBetween($dateDebut, 'now');

        return [
            'equipement_id' => Equipement::factory(),
            'type_maintenance' => $this->faker->randomElement(['preventive', 'corrective']),
            'date_debut' => $dateDebut,
            'date_fin' => $dateFinMaintenance,
            'description' => $this->faker->paragraph(),
            'cout_maintenance' => $this->faker->numberBetween(5000, 100000),
            'fournisseur' => $this->faker->optional()->company(),
            'numero_bon_travail' => $this->faker->optional()->numerify('BT-########'),
            'responsable_maintenance' => $this->faker->name(),
            'statut' => $this->faker->randomElement(['planifiee', 'en_cours', 'terminee']),
        ];
    }
}
