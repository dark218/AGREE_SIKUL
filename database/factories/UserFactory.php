<?php

namespace Database\Factories;

use App\Models\User;
use App\Services\Generator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Business\Entities\Marchand;
use Modules\Business\Entities\Employe;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'nom' => $this->faker->lastName(),
            'prenoms' => $this->faker->firstName(),
            'email' => $this->faker->unique()->safeEmail(),
            'login'     => $this->faker->unique()->numerify('##########'),
            'full_login'=> '+225' . $this->faker->unique()->numerify('##########'),
            'code_owner' => Generator::codeOwner(),
            'qr_data' => Generator::QrCode($this->faker->unique()->numerify('##########')),
            'uuid' => Generator::uuid(),
            'alias_smil' => Generator::generateAliasSmil($this->faker->lastName(), $this->faker->firstName()),
            'password' => Hash::make('12345'),
            'statut' => 'actif',
            'role' => 'admin',
            'pays_id' => 1,
        ];
    }

    public function superAdmin(): self
    {
        return $this->state(fn () => [
            'role' => 'superadmin',
        ])->afterCreating(fn (User $user) => $user->assignRole(config('appconstants.role.superadmin')));
    }

    public function admin(): self
    {
        return $this->state(fn () => [
            'role' => 'admin',
        ])->afterCreating(fn (User $user) => $user->assignRole(config('appconstants.role.admin')));
    }

    public function marchand(): self
    {
        return $this->state(fn () => [
            'role' => 'marchand',
        ])->afterCreating(function (User $user) {
            $user->assignRole(config('appconstants.role.marchand'));

            Marchand::factory()->create([
                'proprietaire_id' => $user->id,
            ]);
        });
    }

    public function employe(string $type = 'manager'): self
    {
        return $this->state(fn () => [
            'role' => 'employe',
        ])->afterCreating(function (User $user) use ($type) {

            $user->assignRole($type);

            Employe::factory()->create([
                'users_id' => $user->id,
                'type_employe' => $type,
            ]);
        });
    }

    public function agent(): self
    {
        return $this->state(fn () => [
            'role' => 'agent',
        ])->afterCreating(function (User $user) {
            $user->assignRole(config('appconstants.role.agent'));
        });
    }

}
