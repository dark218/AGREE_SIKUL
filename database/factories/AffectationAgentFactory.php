<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Personnel\Entities\AffectationAgent;
use App\Models\User;
use Modules\Parametrage\Entities\Zone;

class AffectationAgentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AffectationAgent::class;

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
            'date_affectation' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'date_desaffectation' => null,
            'actif' => true,
            'role_affectation' => $this->faker->randomElement(AffectationAgent::ROLES),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the affectation is currently active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'actif' => true,
            'date_desaffectation' => null,
        ]);
    }

    /**
     * Indicate that the affectation is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'actif' => false,
            'date_desaffectation' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the affectation is expired (past end date).
     */
    public function expiree(): static
    {
        return $this->state(fn (array $attributes) => [
            'actif' => false,
            'date_desaffectation' => $this->faker->dateTimeBetween('-3 months', '-1 month'),
        ]);
    }

    /**
     * Create an affectation for an agent commercial.
     */
    public function agentCommercial(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_affectation' => AffectationAgent::ROLE_AGENT_COMMERCIAL,
        ]);
    }

    /**
     * Create an affectation for a zone supervisor.
     */
    public function superviseurZone(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_affectation' => AffectationAgent::ROLE_SUPERVISEUR_ZONE,
        ]);
    }

    /**
     * Create an affectation for a controller.
     */
    public function controleur(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_affectation' => AffectationAgent::ROLE_CONTROLEUR,
        ]);
    }

    /**
     * Create an affectation for a terrain support.
     */
    public function supportTerrain(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_affectation' => AffectationAgent::ROLE_SUPPORT_TERRAIN,
        ]);
    }

    /**
     * Create an affectation for a specific agent.
     */
    public function pourAgent(int $agentId): static
    {
        return $this->state(fn (array $attributes) => [
            'agent_id' => $agentId,
        ]);
    }

    /**
     * Create an affectation for a specific zone.
     */
    public function pourZone(int $zoneId): static
    {
        return $this->state(fn (array $attributes) => [
            'zone_id' => $zoneId,
        ]);
    }

    /**
     * Create a recent affectation (started this week).
     */
    public function recente(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_affectation' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Create an old affectation (started more than 3 months ago).
     */
    public function ancienne(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_affectation' => $this->faker->dateTimeBetween('-6 months', '-3 months'),
        ]);
    }

    /**
     * Create a long-term affectation (6 months duration).
     */
    public function longTerme(): static
    {
        $startDate = $this->faker->dateTimeBetween('-3 months', '-1 month');
        return $this->state(fn (array $attributes) => [
            'date_affectation' => $startDate,
            'date_desaffectation' => (clone $startDate)->modify('+6 months'),
            'actif' => true,
        ]);
    }

    /**
     * Create a short-term affectation (1 month duration).
     */
    public function courtTerme(): static
    {
        $startDate = $this->faker->dateTimeBetween('-2 months', '-1 month');
        return $this->state(fn (array $attributes) => [
            'date_affectation' => $startDate,
            'date_desaffectation' => (clone $startDate)->modify('+1 month'),
            'actif' => false,
        ]);
    }

    /**
     * Create a permanent affectation (no end date).
     */
    public function permanente(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_desaffectation' => null,
            'actif' => true,
        ]);
    }

    /**
     * Create an affectation that will end soon.
     */
    public function seTermineBientot(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_desaffectation' => $this->faker->dateTimeBetween('now', '+1 week'),
            'actif' => true,
        ]);
    }
}
