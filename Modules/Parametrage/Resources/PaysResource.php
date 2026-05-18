<?php

namespace Modules\Parametrage\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="PaysResource",
 *     title="Pays",
 *     description="Resource pour l'entité Pays",
 *     @OA\Property(property="id", type="integer", example=8),
 *     @OA\Property(property="libelle", type="string", example="Côte d'Ivoire"),
 *     @OA\Property(property="code", type="string", example="CI"),
 *     @OA\Property(property="phone_length", type="integer", example=10),
 *     @OA\Property(property="iso", type="string", example="CIV"),
 *     @OA\Property(property="full_phone_example", type="string", example="CIXXXXXXXX"),
 *     @OA\Property(property="zones_count", type="integer", example=15),
 *     @OA\Property(
 *         property="zones",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/ZoneResource")
 *     )
 * )
 */
class PaysResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'libelle' => $this->libelle,
            'code' => $this->code,
            'phone_length' => $this->phone_length,
            'iso' => $this->iso,
            'full_phone_example' => $this->when($this->code, $this->code . 'XXXXXXXX'),
            'zones_count' => $this->when($this->zones_count, $this->zones_count),
            'zones' => $this->when($this->zones, function () {
                return ZoneResource::collection($this->zones);
            }),
        ];
    }
}
