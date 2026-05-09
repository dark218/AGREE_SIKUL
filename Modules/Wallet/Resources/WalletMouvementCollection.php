<?php

namespace Modules\Wallet\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     schema="WalletMouvementCollection",
 *     title="WalletMouvementCollection",
 *     description="Collection paginée des mouvements de wallet",
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/WalletMouvementResource")
 *     ),
 *     @OA\Property(
 *         property="meta",
 *         type="object",
 *         @OA\Property(property="total", type="integer", example=200),
 *         @OA\Property(property="count", type="integer", example=10),
 *         @OA\Property(property="per_page", type="integer", example=10),
 *         @OA\Property(property="current_page", type="integer", example=1),
 *         @OA\Property(property="total_pages", type="integer", example=20)
 *     )
 * )
 */
class WalletMouvementCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->count(),
                'count' => $this->count(),
                'per_page' => $this->perPage() ?? null,
                'current_page' => $this->currentPage() ?? null,
                'total_pages' => $this->lastPage() ?? null,
            ],
        ];
    }
}
