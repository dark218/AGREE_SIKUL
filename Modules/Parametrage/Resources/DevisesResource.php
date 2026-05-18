<?php

namespace Modules\Parametrage\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="DevisesResource",
 *     title="Devises",
 *     description="Resource pour l'entité Devises",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="code", type="string", example="XOF"),
 *     @OA\Property(property="symbol", type="string", example="CFA"),
 *     @OA\Property(property="libelle", type="string", example="Franc CFA BCEAO"),
 *     @OA\Property(property="decimal_places", type="integer", example=0)
 * )
 */
class DevisesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'symbol' => $this->symbol,
            'libelle' => $this->libelle,
            'decimal_places' => $this->decimal_places,
        ];
    }
}
