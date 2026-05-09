<?php

namespace Modules\GestionStock\Resources;

use App\Services\MoneyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Business\Resources\PointVenteResource;

class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @OA\Schema(
     *     schema="Article",
     *     title="Article",
     *     description="Modèle d'article",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="sku", type="string", maxLength=100, example="PROD-001"),
     *     @OA\Property(property="nom", type="string", maxLength=255, example="Smartphone XYZ"),
     *     @OA\Property(property="description", type="string", nullable=true, example="Smartphone haut de gamme avec écran AMOLED"),
     *     @OA\Property(property="prix_cents", type="integer", example=250000, description="Prix en centimes"),
     *     @OA\Property(property="devise", type="string", maxLength=3, example="XOF"),
     *     @OA\Property(property="quantite_stock", type="integer", example=50),
     *     @OA\Property(property="seuil_alert_stock", type="integer", example=5),
     *     @OA\Property(property="taxes_json", type="object", nullable=true,
     *         @OA\Property(property="tva", type="number", format="float", example=18.5),
     *         @OA\Property(property="autres_taxes", type="array",
     *             @OA\Items(type="object",
     *                 @OA\Property(property="nom", type="string", example="Taxe spéciale"),
     *                 @OA\Property(property="valeur", type="number", format="float", example=2.5)
     *             )
     *         )
     *     ),
     *     @OA\Property(
     *         property="pointVente",
     *         ref="#/components/schemas/PointVenteResource",
     *         description="Point de vente associé"
     *     )
     * )
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'nom' => $this->nom,
            'description' => $this->description,
            'prix_cents' => (float) str_replace(' ', '', MoneyService::toDisplay($this->prix_cents, $this->devise)),
            'devise' => $this->devise,
            'quantite_stock' => $this->quantite_stock,
            'seuil_alert_stock' => $this->seuil_alert_stock,
            'taxes_json' => $this->taxes_json,
            'pointVente' => new PointVenteResource($this->pointVente)

        ];
    }


}
