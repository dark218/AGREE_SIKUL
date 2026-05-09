<?php

namespace Modules\ServiceClient\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     schema="MoyenPaiementCollection",
 *     title="MoyenPaiement Collection",
 *     description="Collection de moyens de paiement",
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/MoyenPaiementResource")
 *     ),
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="last_page", type="integer", example=5),
 *     @OA\Property(property="per_page", type="integer", example=15),
 *     @OA\Property(property="total", type="integer", example=75),
 *     @OA\Property(
 *         property="links",
 *         type="object",
 *         @OA\Property(property="first", type="string", example="http://api.example.com/moyens-paiement?page=1"),
 *         @OA\Property(property="last", type="string", example="http://api.example.com/moyens-paiement?page=5"),
 *         @OA\Property(property="prev", type="string", example="http://api.example.com/moyens-paiement?page=1", nullable=true),
 *         @OA\Property(property="next", type="string", example="http://api.example.com/moyens-paiement?page=2", nullable=true)
 *     )
 * )
 */
class MoyenPaiementCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => MoyenPaiementResource::collection($this->collection),
            'meta' => [
                'count' => $this->collection->count(),
                'total_defaut' => $this->collection->where('is_defaut', true)->count(),
                'total_actifs' => $this->collection->where('statut', 'actif')->count(),
                'types' => $this->collection->groupBy('type')->map->count(),
            ],
        ];
    }
}
