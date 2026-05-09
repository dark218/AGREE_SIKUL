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
 *     @OA\Property(property="symbole", type="string", example="CFA"),
 *     @OA\Property(property="libelle", type="string", example="Franc CFA BCEAO"),
 *     @OA\Property(property="decimal_point", type="integer", example=0),
 *     @OA\Property(property="pays_devises_count", type="integer", example=8),
 *     @OA\Property(
 *         property="pays_devises",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/PaysDeviseResource")
 *     )
 * )
 */
class DevisesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'symbole' => $this->symbole,
            'libelle' => $this->libelle,
            'decimal_point' => $this->decimal_point,
            'pays_devises_count' => $this->when($this->paysDevises_count, $this->paysDevises_count),
            'pays_devises' => PaysDeviseResource::collection($this->paysDevises),
        ];
    }
}
