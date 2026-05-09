<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GestionApprenants\Entities\DossierApprenant;
use Modules\GestionApprenants\Entities\Apprenant;

class DossierApprenantFactory extends Factory
{
    protected $model = DossierApprenant::class;

    public function definition(): array
    {
        return [
            'apprenant_id' => Apprenant::factory(),
            'extrait_naissance_id' => null,
            'certificat_residence_id' => null,
            'carnet_sante_id' => null,
            'dernier_bulletin_id' => null,
        ];
    }

    public function complet()
    {
        return $this->state(function (array $attributes) {
            return [
                'extrait_naissance_id' => 1,
                'certificat_residence_id' => 2,
                'carnet_sante_id' => 3,
            ];
        });
    }
}
