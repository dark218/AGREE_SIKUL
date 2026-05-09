<?php

namespace App\Services;

use App\Models\Pays;

class VerifNumeroService
{

    public function verifNumero($num, $paysId) : String
    {
        $pays = Pays::where('id', $paysId)->first();
        $countryCode = $pays->code;
        $formattedCode = str_replace("+", "00", $countryCode);
        $phonelength = $pays->phone_length;
        if (strpos($num, $countryCode) === 0) {
            $num = substr($num, strlen($countryCode));
        } else {
            if (strpos($num, $formattedCode) === 0){
                $num = substr($num, strlen($formattedCode));
            }
        }
        return $num;
    }
}
