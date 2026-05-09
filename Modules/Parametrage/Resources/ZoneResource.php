<?php

namespace Modules\Parametrage\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Parametrage\Resources\PaysResource;

/**
 * @OA\Schema(
 *     schema="ZoneResource",
 *     title="Zone",
 *     description="Resource pour l'entité Zone",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="libelle", type="string", example="Abidjan"),
 *     @OA\Property(property="pays_id", type="integer", example=8),
 *     @OA\Property(
 *         property="pays", 
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=8),
 *         @OA\Property(property="libelle", type="string", example="Côte d'Ivoire"),
 *         @OA\Property(property="code", type="string", example="CI")
 *     ),
 *     @OA\Property(property="type_zone", type="string", enum={"commerciale", "region", "secteur"}, example="region"),
 *     @OA\Property(property="type_zone_label", type="string", example="Région"),
 *     @OA\Property(property="centroid_lat", type="number", format="float", example=5.3600),
 *     @OA\Property(property="centroid_lng", type="number", format="float", example=-4.0083),
 *     @OA\Property(property="polygon_geojson", type="object", example={"type": "Polygon"}),
 *     @OA\Property(property="description", type="string", example="Zone commerciale d'Abidjan")
 * )
 */
class ZoneResource extends JsonResource
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
            'pays_id' => $this->pays_id,
            'pays' => new PaysResource($this->pays),
            'type_zone' => $this->type_zone,
            'type_zone_label' => $this->getTypeZoneLabel(),
            'centroid_lat' => $this->centroid_lat,
            'centroid_lng' => $this->centroid_lng,
            'polygon_geojson' => $this->polygon_geojson,
            'description' => $this->description,
        ];
    }

    /**
     * Obtenir le libellé du type de zone
     */
    private function getTypeZoneLabel(): string
    {
        return match($this->type_zone) {
            'commerciale' => 'Zone Commerciale',
            'region' => 'Région',
            'secteur' => 'Secteur',
            default => 'Inconnu',
        };
    }
}
