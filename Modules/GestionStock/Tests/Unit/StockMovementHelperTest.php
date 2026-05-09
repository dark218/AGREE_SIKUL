<?php

namespace Modules\GestionStock\Tests\Unit;

use App\Helpers\StockMovementHelper;
use Modules\Business\Entities\PointVente;
use Modules\GestionStock\Entities\Article;
use Tests\BaseTestCase;

/**
 * Tests pour l'helper de gestion des mouvements de stock
 * Couvre les entrées, sorties et transferts d'articles
 */
class StockMovementHelperTest extends BaseTestCase
{
    /**
     * Test d'application d'un mouvement positif (entrée de stock)
     * Augmente la quantité en stock et enregistre le mouvement
     */
    public function test_apply_positive_movement()
    {
        // Créer un point de vente et un article avec stock initial
        $pointVente = PointVente::factory()->create();
        $article = Article::factory()->forPointVente($pointVente)->withStock(10)->create();

        // Appliquer un mouvement d'entrée de +5 unités
        StockMovementHelper::apply($article, 5, 'entree_stock');

        // Vérifier la mise à jour du stock
        $article->refresh();
        $this->assertEquals(15, $article->quantite_stock); // 10 + 5 = 15

        // Vérifier l'enregistrement du mouvement en base
        $this->assertDatabaseHas('mouvements_stock', [
            'article_id' => $article->id,
            'type_mouvement' => 'entree_stock',
            'quantite' => 5,                    // Quantité du mouvement
            'quantite_stock_avant' => 10,       // Stock avant le mouvement
            'quantite_stock_apres' => 15        // Stock après le mouvement
        ]);
    }

    /**
     * Test d'application d'un mouvement négatif (sortie de stock)
     * Diminue la quantité en stock et enregistre le mouvement
     */
    public function test_apply_negative_movement()
    {
        // Créer un point de vente et un article avec stock initial
        $pointVente = PointVente::factory()->create();
        $article = Article::factory()->forPointVente($pointVente)->withStock(10)->create();

        // Appliquer un mouvement de sortie de -3 unités
        StockMovementHelper::apply($article, -3, 'sortie_stock');

        // Vérifier la mise à jour du stock
        $article->refresh();
        $this->assertEquals(7, $article->quantite_stock); // 10 - 3 = 7

        // Vérifier l'enregistrement du mouvement en base
        $this->assertDatabaseHas('mouvements_stock', [
            'article_id' => $article->id,
            'type_mouvement' => 'sortie_stock',
            'quantite' => -3,                   // Quantité négative
            'quantite_stock_avant' => 10,       // Stock avant
            'quantite_stock_apres' => 7         // Stock après
        ]);
    }

    /**
     * Test de protection contre le stock négatif
     * Doit lever une exception si le mouvement crée un stock négatif
     */
    public function test_apply_throws_exception_for_negative_stock()
    {
        // Créer un article avec un stock faible
        $pointVente = PointVente::factory()->create();
        $article = Article::factory()->forPointVente($pointVente)->withStock(5)->create();

        // Configurer l'attente d'une exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Stock négatif interdit pour l'article");

        // Tenter un mouvement qui créerait un stock négatif (5 - 10 = -5)
        StockMovementHelper::apply($article, -10, 'sortie_stock');
    }

    /**
     * Test d'application avec tous les paramètres optionnels
     * Vérifie l'enregistrement des métadonnées complètes
     */
    public function test_apply_with_all_parameters()
    {
        // Créer deux points de vente pour un transfert
        $pointVente1 = PointVente::factory()->create();
        $pointVente2 = PointVente::factory()->create();
        $article = Article::factory()->forPointVente($pointVente1)->withStock(20)->create();

        // Appliquer un mouvement avec tous les paramètres
        StockMovementHelper::apply(
            $article,                    // Article concerné
            10,                         // Quantité du mouvement
            'transfert',                // Type de mouvement
            $pointVente1->id,          // Emplacement source
            $pointVente2->id,          // Emplacement destination
            null,                      // Utilisateur (null = système)
            'REF-001',                 // Référence du mouvement
            'Test transfert'           // Commentaire
        );

        // Vérifier l'enregistrement des métadonnées
        $this->assertDatabaseHas('mouvements_stock', [
            'article_id' => $article->id,
            'emplacement_source_id' => $pointVente1->id,      // Source du transfert
            'emplacement_destination_id' => $pointVente2->id, // Destination
            'reference' => 'REF-001',                         // Référence
            'commentaire' => 'Test transfert'                 // Commentaire
        ]);
    }
}
