<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Business\Entities\Marchand;
use App\Models\User;

class MarchandFactory extends Factory
{
    protected $model = Marchand::class;

    public function definition(): array
    {
        return [
            'raison_sociale' => $this->faker->company,
            'identifiant_fiscal' => strtoupper($this->faker->bothify('IF-####')),
            'proprietaire_id' => User::factory()->state([
                'role' => config('appconstants.role.marchand'),
            ]),
            'rccm_id' => null,
            'dfe_id' => null,
            'validated_at' => now(),
            'validated_by' => null,
            'create_by' => null,
        ];
    }

    public function validated(): self
    {
        return $this->state(fn () => [
            'validated_at' => now(),
        ]);
    }

    public function notValidated(): self
    {
        return $this->state(fn () => [
            'validated_at' => null,
        ]);
    }

    public function forOwner(User $user): self
    {
        return $this->state(fn () => [
            'proprietaire_id' => $user->id,
        ]);
    }
}
