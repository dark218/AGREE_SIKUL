<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Business\Entities\Employe;
use Modules\Business\Entities\PointVente;
use App\Models\User;

class EmployeFactory extends Factory
{
    protected $model = Employe::class;

    public function definition(): array
    {
        return [
            'users_id' => User::factory(),
            'points_vente_id' => PointVente::factory(),
            'code_employe' => strtoupper(Str::random(8)),
            'date_embauche' => now()->subMonths(rand(1, 24)),
            'type_employe' => config('appconstants.type_employe.caissier'),
            'shift_info' => null,
            'validated_at' => now(),
            'validated_by' => null,
            'create_by' => null,
        ];
    }

    public function manager(): self
    {
        return $this->state(fn () => [
            'type_employe' => config('appconstants.type_employe.manager'),
        ]);
    }

    public function caissier(): self
    {
        return $this->state(fn () => [
            'type_employe' => config('appconstants.type_employe.caissier'),
        ]);
    }

    public function forUser(User $user): self
    {
        return $this->state(fn () => [
            'users_id' => $user->id,
        ]);
    }

    public function forPointVente(PointVente $pointVente): self
    {
        return $this->state(fn () => [
            'points_vente_id' => $pointVente->id,
        ]);
    }
}
