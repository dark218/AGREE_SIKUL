<?php

namespace App\Helpers;

use Modules\Business\Entities\Employe;
use Modules\GestionStock\Entities\Article;
use Modules\GestionStock\Entities\MouvementStock;

class StockMovementHelper
{
    /**
     * Appliquer un mouvement de stock et journaliser
     *
     * @param Article $article
     * @param int $quantite              (+ entrée / - sortie)
     * @param string $typeMouvement
     * @param int|null $emplacementSourceId
     * @param int|null $emplacementDestinationId
     * @param Employe|null $employe
     * @param string|null $reference
     * @param string|null $commentaire
     *
     * @throws \Exception
     */
    public static function apply(
        Article $article,
        int $quantite,
        string $typeMouvement,
        ?int $emplacementSourceId = null,
        ?int $emplacementDestinationId = null,
        ?Employe $employe = null,
        ?string $reference = null,
        ?string $commentaire = null
    ): void {
        // Verrouillage pessimiste
        $article->refresh();
        $article->lockForUpdate();

        $avant = $article->quantite_stock;
        $apres = $avant + $quantite;

        if ($apres < 0) {
            throw new \Exception(
                "Stock négatif interdit pour l'article {$article->sku}"
            );
        }

        // Mise à jour du stock
        $article->update([
            'quantite_stock' => $apres,
        ]);

        // Journalisation
        MouvementStock::create([
            'article_id' => $article->id,
            'type_mouvement' => $typeMouvement,
            'quantite' => $quantite,
            'quantite_stock_avant' => $avant,
            'quantite_stock_apres' => $apres,
            'emplacement_source_id' => $emplacementSourceId,
            'emplacement_destination_id' => $emplacementDestinationId,
            'employe_id' => $employe?->id,
            'reference' => $reference,
            'commentaire' => $commentaire,
        ]);
    }
}
