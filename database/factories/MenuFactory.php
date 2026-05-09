<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finances\Entities\Menu;
use Modules\Finances\Entities\ServiceCantine;

class MenuFactory extends Factory
{
    protected $model = Menu::class;

    public function definition(): array
    {
        return [
            'service_cantine_id' => ServiceCantine::factory(),
            'date_menu' => $this->faker->dateTimeBetween('-3 months', '+3 months'),
            'jour' => $this->faker->randomElement(['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi']),
            'plat_principal' => $this->faker->randomElement(['Thiéboudienne', 'Yassa', 'Mafé', 'Couscous', 'Riz sauce']),
            'accompagnement' => $this->faker->randomElement(['Riz', 'Pâtes', 'Haricots', 'Légumes']),
            'dessert' => $this->faker->optional()->randomElement(['Fruit', 'Yaourt', 'Tarte']),
            'boisson' => $this->faker->optional()->randomElement(['Eau', 'Jus', 'Lait']),
            'statut' => $this->faker->randomElement(['planifie', 'servi', 'modifie']),
        ];
    }
}
