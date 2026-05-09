<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Personnel\Entities\MissionAgent;
use App\Models\User;
use Modules\Parametrage\Entities\Zone;

class MissionAgentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MissionAgent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agent_id' => User::factory()->agent(),
            'zone_id' => Zone::factory(),
            'titre' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(3),
            'date_debut' => $this->faker->dateTimeBetween('now', '+1 month'),
            'date_fin' => $this->faker->dateTimeBetween('+1 week', '+3 months'),
            'statut' => $this->faker->randomElement(MissionAgent::STATUTS),
            'objectif_json' => [
                'objectifs' => [
                    $this->faker->sentence(2),
                    $this->faker->sentence(2),
                ],
                'kpi' => [
                    'ventes' => $this->faker->numberBetween(100, 1000),
                    'clients' => $this->faker->numberBetween(10, 50),
                ],
                'critere_succes' => $this->faker->sentence(3),
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the mission is assigned but not started.
     */
    public function assignee(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => MissionAgent::STATUT_ASSIGNED,
        ]);
    }

    /**
     * Indicate that the mission is currently in progress.
     */
    public function enCours(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => MissionAgent::STATUT_EN_COURS,
            'date_debut' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the mission is completed.
     */
    public function terminee(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => MissionAgent::STATUT_TERMINEE,
            'date_debut' => $this->faker->dateTimeBetween('-2 weeks', '-1 week'),
            'date_fin' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the mission is delayed.
     */
    public function enRetard(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => MissionAgent::STATUT_EN_RETARD,
            'date_debut' => $this->faker->dateTimeBetween('-2 weeks', '-1 week'),
            'date_fin' => $this->faker->dateTimeBetween('-3 days', '-1 day'),
        ]);
    }

    /**
     * Indicate that the mission is cancelled.
     */
    public function annulee(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => MissionAgent::STATUT_ANNULEE,
        ]);
    }

    /**
     * Create a mission without a zone (for national/transverse missions).
     */
    public function sansZone(): static
    {
        return $this->state(fn (array $attributes) => [
            'zone_id' => null,
        ]);
    }

    /**
     * Create a mission for a specific agent.
     */
    public function pourAgent(int $agentId): static
    {
        return $this->state(fn (array $attributes) => [
            'agent_id' => $agentId,
        ]);
    }

    /**
     * Create a mission for a specific zone.
     */
    public function pourZone(int $zoneId): static
    {
        return $this->state(fn (array $attributes) => [
            'zone_id' => $zoneId,
        ]);
    }

    /**
     * Create a mission starting today.
     */
    public function commencantAujourdhui(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_debut' => now(),
        ]);
    }

    /**
     * Create a short mission (1 week duration).
     */
    public function courte(): static
    {
        $startDate = $this->faker->dateTimeBetween('now', '+1 month');
        return $this->state(fn (array $attributes) => [
            'date_debut' => $startDate,
            'date_fin' => (clone $startDate)->modify('+1 week'),
        ]);
    }

    /**
     * Create a long mission (2-3 months duration).
     */
    public function longue(): static
    {
        $startDate = $this->faker->dateTimeBetween('now', '+1 month');
        return $this->state(fn (array $attributes) => [
            'date_debut' => $startDate,
            'date_fin' => (clone $startDate)->modify('+2 months'),
        ]);
    }
}
