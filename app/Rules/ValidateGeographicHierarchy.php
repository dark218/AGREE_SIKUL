<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\Parametrage\Entities\Departement;
use Modules\Parametrage\Entities\Region;

class ValidateGeographicHierarchy implements Rule
{
    private $departementId;
    private $regionId;
    private $paysId;

    public function __construct($departementId, $regionId, $paysId)
    {
        $this->departementId = $departementId;
        $this->regionId = $regionId;
        $this->paysId = $paysId;
    }

    public function passes($attribute, $value)
    {
        $departement = Departement::find($this->departementId);
        $region = Region::find($this->regionId);

        if (!$departement || !$region) {
            return false;
        }

        // Verify departement belongs to selected region
        if ($departement->region_id != $this->regionId) {
            return false;
        }

        // Verify departement belongs to selected pays
        if ($departement->pays_id != $this->paysId) {
            return false;
        }

        // Verify region belongs to selected pays
        if ($region->pays_id != $this->paysId) {
            return false;
        }

        return true;
    }

    public function message()
    {
        return 'La hiérarchie géographique est incohérente. Le département doit appartenir à la région et au pays sélectionnés.';
    }
}
