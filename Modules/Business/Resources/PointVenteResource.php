<?php

namespace Modules\Business\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Parametrage\Resources\FichierResource;
use Modules\Parametrage\Resources\ZoneResource;

class PointVenteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    /**
     * @OA\Schema(
     *     schema="PointVenteResource",
     *     title="PointVente",
     *     description="Modèle de point de vente",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="nom", type="string", example="Auchan"),
     *     @OA\Property(property="adresse", type="string", example="123 rue de l'église"),
     *     @OA\Property(property="telephone", type="string", example="+2250102030405"),
     *     @OA\Property(property="longitude", type="number", example=1.23456789),
     *     @OA\Property(property="latitude", type="number", example=1.23456789),
     *     @OA\Property(property="paramPosJson", type="string", example="{...}"),
     *     @OA\Property(property="statut", type="string", enum={"non_actif", "actif", "suspendu", "bloque"}, example="actif"),
     *     @OA\Property(property="checksum", type="string", example="0123456789abcdef"),
     *     @OA\Property(
     *         property="marchand",
     *         type="object",
     *         nullable=true,
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="raison_sociale", type="string", example="AUCHAN"),
     *         @OA\Property(property="type", type="string", example="grande_surface"),
     *         @OA\Property(property="type_label", type="string", example="Grande Surface")
     *     ),
     *     @OA\Property(property="zone", ref="#/components/schemas/ZoneResource", description="Zone"),
     *     @OA\Property(property="photo", ref="#/components/schemas/FichierResource", description="Photo"),
     * )
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'adresse' => $this->adresse,
            'telephone' => $this->telephone,
            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
            'paramPosJson' => $this->param_pos_json,
            'statut' => $this->statut,
            'checksum' => $this->checksum,
            // Relations
            'marchand' => $this->when($this->marchand, function() {
                return [
                    'id' => $this->marchand->id,
                    'raison_sociale' => $this->marchand->raison_sociale,
                    'type' => $this->marchand->type,
                    'type_label' => $this->marchand->getTypeLabel(),
                ];
            }),
            'zone' => new ZoneResource($this->zone),
            'photo' => new FichierResource($this->photo),
        ];
    }
}
