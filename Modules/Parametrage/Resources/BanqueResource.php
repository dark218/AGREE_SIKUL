<?php

namespace Modules\Parametrage\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="BanqueResource",
 *     title="Banque",
 *     description="Resource pour l'entité Banque",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="pays_id", type="integer", example=8),
 *     @OA\Property(
 *         property="pays", 
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=8),
 *         @OA\Property(property="libelle", type="string", example="Côte d'Ivoire"),
 *         @OA\Property(property="code", type="string", example="CI")
 *     ),
 *     @OA\Property(property="nom", type="string", example="SGBCI"),
 *     @OA\Property(property="code", type="string", example="SGBCICI"),
 *     @OA\Property(property="bic_swift", type="string", example="SGBCICIAB"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="meta_json", type="object", example={"key": "value"})
 * )
 */
class BanqueResource extends JsonResource
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
            'pays_id' => $this->pays_id,
            'pays' => [
                'id' => $this->pays->id,
                'libelle' => $this->pays->libelle,
                'code' => $this->pays->code,
            ],
            'nom' => $this->nom,
            'code' => $this->code,
            'bic_swift' => $this->bic_swift,
            'is_active' => $this->is_active,
            'meta_json' => $this->meta_json,
        ];
    }
}
