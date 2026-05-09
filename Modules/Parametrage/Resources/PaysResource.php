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
 *     @OA\Property(property="pays_devises_count", type="integer", example=1),
 *     @OA\Property(
 *         property="pays_devises",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/PaysDeviseResource")
 *     ),
 *     @OA\Property(property="zones_count", type="integer", example=15),
 *     @OA\Property(
 *         property="zones",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/ZoneResource")
 *     ),
 *     @OA\Property(property="banques_count", type="integer", example=5),
 *     @OA\Property(
 *         property="banques",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/BanqueResource")
 *     )
 * )
 */
class PaysResource extends JsonResource
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
            'libelle' => $this->libelle,
            'code' => $this->code,
            'phone_length' => $this->phone_length,
            'iso' => $this->iso,
            'full_phone_example' => $this->when($this->code, $this->code . 'XXXXXXXX'),
            'pays_devises_count' => $this->when($this->paysDevises_count, $this->paysDevises_count),
            'pays_devises' => $this->when($this->paysDevises, function () {
                return PaysDeviseResource::collection($this->paysDevises);
            }),
            'zones_count' => $this->when($this->zones_count, $this->zones_count),
            'zones' => $this->when($this->zones, function () {
                return ZoneResource::collection($this->zones);
            }),
            'banques_count' => $this->when($this->banques_count, $this->banques_count),
            'banques' => $this->when($this->banques, function () {
                return BanqueResource::collection($this->banques);
            }),
        ];
    }
}
