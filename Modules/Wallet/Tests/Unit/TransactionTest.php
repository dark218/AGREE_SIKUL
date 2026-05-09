<?php

namespace Modules\Wallet\Tests\Unit;

use App\Models\User;
use App\Services\MoneyService;
use Modules\Business\Entities\Marchand;
use Modules\Business\Entities\PointVente;
use Modules\Parametrage\Entities\FournisseurPaiement;
use Modules\Wallet\Entities\Transactions;
use Tests\BaseTestCase;

class TransactionTest extends BaseTestCase
{
    /**
     * Teste la création d'une transaction avec des attributs par défaut.
     * Vérifie que la transaction est bien enregistrée en base de données.
     */
    public function test_transaction_creation()
    {
        $transaction = Transactions::factory()->create([
            'statut' => Transactions::STATUT_INITIEE,
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'statut' => Transactions::STATUT_INITIEE,
        ]);
    }

    /**
     * Teste la relation avec l'utilisateur payeur.
     * Vérifie que la transaction est correctement liée à un utilisateur payeur.
     */
    public function test_transaction_belongs_to_payer()
    {
        $payer = User::factory()->create();
        $transaction = Transactions::factory()->create(['payer_id' => $payer->id]);

        $this->assertInstanceOf(User::class, $transaction->payer);
        $this->assertEquals($payer->id, $transaction->payer->id);
    }

    /**
     * Teste la relation avec le marchand.
     * Vérifie que la transaction est correctement liée à un marchand.
     */
    public function test_transaction_belongs_to_marchand()
    {
        $marchand = Marchand::factory()->create();
        $transaction = Transactions::factory()->create(['marchand_id' => $marchand->id]);

        $this->assertInstanceOf(Marchand::class, $transaction->marchand);
        $this->assertEquals($marchand->id, $transaction->marchand->id);
    }

    /**
     * Teste la relation avec le point de vente.
     * Vérifie que la transaction est correctement liée à un point de vente.
     */
    public function test_transaction_belongs_to_point_vente()
    {
        $pointVente = PointVente::factory()->create();
        $transaction = Transactions::factory()->create(['points_vente_id' => $pointVente->id]);

        $this->assertInstanceOf(PointVente::class, $transaction->pointVente);
        $this->assertEquals($pointVente->id, $transaction->pointVente->id);
    }

    /**
     * Teste la relation avec le fournisseur de paiement.
     * Vérifie que la transaction est correctement liée à un fournisseur de paiement.
     */
    public function test_transaction_belongs_to_fournisseur_paiement()
    {
        $fournisseur = FournisseurPaiement::factory()->create();
        $transaction = Transactions::factory()->create(['fournisseur_paiement_id' => $fournisseur->id]);

        $this->assertInstanceOf(FournisseurPaiement::class, $transaction->fournisseurPaiement);
        $this->assertEquals($fournisseur->id, $transaction->fournisseurPaiement->id);
    }

    /**
     * Teste l'accesseur 'montant'.
     * Vérifie que le montant est correctement formaté.
     */
    public function test_transaction_montant_accessor()
    {
        $transaction = Transactions::factory()->create([
            'montant_cents' => 1500, // 15.00 dans la devise par défaut
            'devise' => 'XOF',
        ]);

        $montant = $transaction->montant;
        $this->assertIsString($montant);
        // Vérifie que le montant est bien formaté selon les règles XOF (séparateur d'espace pour les milliers)
        $this->assertEquals('1 500', $montant);
    }

    /**
     * Teste le mutateur 'montant' avec une valeur numérique.
     * Vérifie que la valeur est correctement convertie en centimes.
     */
    public function test_transaction_montant_mutator_with_numeric()
    {
        $transaction = Transactions::factory()->make();
        $transaction->montant = 25.50; // Doit être converti en 2550 centimes

        $this->assertEquals(2550, $transaction->montant_cents);
    }

    /**
     * Teste le mutateur 'montant' avec une chaîne formatée.
     * Vérifie que la chaîne est correctement convertie en centimes.
     */
    public function test_transaction_montant_mutator_with_string()
    {
        $transaction = Transactions::factory()->make([
            'devise' => 'XOF',
        ]);

        // MoneyService::toDatabase('30.50', 'XOF') devrait retourner 3050
        $transaction->montant = '30.50';

        $this->assertEquals(3050, $transaction->montant_cents);
    }

    /**
     * Teste la méthode markAsReussie().
     * Vérifie que le statut est mis à jour et que la date de confirmation est définie.
     */
    public function test_transaction_mark_as_reussie()
    {
        $transaction = Transactions::factory()->create([
            'statut' => Transactions::STATUT_EN_ATTENTE,
            'confirmed_at' => null,
        ]);

        $transaction->markAsReussie();
        $transaction->refresh();

        $this->assertEquals(Transactions::STATUT_REUSSIE, $transaction->statut);
        $this->assertNotNull($transaction->confirmed_at);
    }

    /**
     * Teste la méthode markAsEchouee().
     * Vérifie que le statut est mis à jour et que la date d'échec est définie.
     */
    public function test_transaction_mark_as_echouee()
    {
        $transaction = Transactions::factory()->create([
            'statut' => Transactions::STATUT_EN_ATTENTE,
            'failed_at' => null,
        ]);

        $transaction->markAsEchouee();
        $transaction->refresh();

        $this->assertEquals(Transactions::STATUT_ECHOUEE, $transaction->statut);
        $this->assertNotNull($transaction->failed_at);
    }

    /**
     * Teste la méthode markAsRemboursee().
     * Vérifie que le statut est mis à jour.
     */
    public function test_transaction_mark_as_remboursee()
    {
        $transaction = Transactions::factory()->create([
            'statut' => Transactions::STATUT_REUSSIE,
        ]);

        $transaction->markAsRemboursee();
        $transaction->refresh();

        $this->assertEquals(Transactions::STATUT_REMBOURSEE, $transaction->statut);
    }

    /**
     * Teste les scopes de requête pour filtrer par statut.
     */
    public function test_transaction_scopes()
    {
        // Créer des transactions avec différents statuts
        $initiee = Transactions::factory()->create(['statut' => Transactions::STATUT_INITIEE]);
        $enAttente = Transactions::factory()->create(['statut' => Transactions::STATUT_EN_ATTENTE]);
        $reussie = Transactions::factory()->create(['statut' => Transactions::STATUT_REUSSIE]);
        $echouee = Transactions::factory()->create(['statut' => Transactions::STATUT_ECHOUEE]);
        $annulee = Transactions::factory()->create(['statut' => Transactions::STATUT_ANNULEE]);
        $remboursee = Transactions::factory()->create(['statut' => Transactions::STATUT_REMBOURSEE]);

        // Tester chaque scope
        $this->assertTrue(Transactions::initiees()->where('id', $initiee->id)->exists());
        $this->assertTrue(Transactions::enAttente()->where('id', $enAttente->id)->exists());
        $this->assertTrue(Transactions::reussies()->where('id', $reussie->id)->exists());
        $this->assertTrue(Transactions::echouees()->where('id', $echouee->id)->exists());
        $this->assertTrue(Transactions::annulees()->where('id', $annulee->id)->exists());
        $this->assertTrue(Transactions::remboursees()->where('id', $remboursee->id)->exists());
    }

    /**
     * Teste que le statut par défaut est bien défini à la création.
     */
    public function test_transaction_default_status()
    {
        // Vérifier que la valeur par défaut est bien définie dans le modèle
        $transaction = new Transactions();
        $this->assertEquals(Transactions::STATUT_INITIEE, $transaction->statut);
        
        // La factory utilise un statut aléatoire, donc on vérifie qu'il est bien défini
        $transactionFromFactory = Transactions::factory()->make();
        $this->assertContains($transactionFromFactory->statut, [
            Transactions::STATUT_INITIEE,
            Transactions::STATUT_EN_ATTENTE,
            Transactions::STATUT_REUSSIE,
            Transactions::STATUT_ECHOUEE,
            Transactions::STATUT_ANNULEE,
            Transactions::STATUT_REMBOURSEE,
        ]);
    }

    /**
     * Teste la création d'une transaction via la factory avec un statut spécifique.
     */
    public function test_transaction_factory_with_specific_status()
    {
        $transaction = Transactions::factory()
            ->reussie()
            ->create();

        $this->assertEquals(Transactions::STATUT_REUSSIE, $transaction->statut);
        $this->assertNotNull($transaction->confirmed_at);
    }

    /**
     * Teste la création d'une transaction avec un type de source spécifique.
     */
    public function test_transaction_factory_with_source_type()
    {
        $transaction = Transactions::factory()
            ->ventePos()
            ->create();

        $this->assertEquals(Transactions::SOURCE_VENTE_POS, $transaction->source_type);
    }
}
