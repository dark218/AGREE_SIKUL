<?php

namespace Modules\Wallet\Tests\Unit;

use App\Models\User;
use App\Services\MoneyService;
use Modules\Parametrage\Entities\PaysDevise;
use Modules\Wallet\Entities\Wallet;
use Modules\Wallet\Entities\WalletMouvement;
use Tests\BaseTestCase;

class WalletTest extends BaseTestCase
{
    /**
     * Teste la création d'un wallet avec des attributs par défaut.
     * Vérifie que le wallet est persisté en base de données avec les bonnes valeurs.
     */
    public function test_wallet_creation()
    {
        $wallet = Wallet::factory()->create([
            'owner_type' => Wallet::OWNER_TYPE_CLIENT,
            'statut' => Wallet::STATUT_ACTIF,
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'owner_type' => Wallet::OWNER_TYPE_CLIENT,
            'statut' => Wallet::STATUT_ACTIF,
        ]);
    }

    /**
     * Teste la relation BelongsTo avec PaysDevise.
     * Vérifie que le wallet est correctement lié à un objet PaysDevise.
     */
    public function test_wallet_belongs_to_pays_devise()
    {
        $paysDevise = PaysDevise::factory()->create();
        $wallet = Wallet::factory()->create(['pays_devise_id' => $paysDevise->id]);

        $this->assertInstanceOf(PaysDevise::class, $wallet->paysDevise);
        $this->assertEquals($paysDevise->id, $wallet->paysDevise->id);
    }

    /**
     * Teste la relation HasMany avec WalletMouvement.
     * Vérifie que l'on peut récupérer les mouvements associés à un wallet.
     */
    public function test_wallet_has_many_mouvements()
    {
        $wallet = Wallet::factory()->create([
            'solde_cents' => 1000,
        ]);

        $mouvement = WalletMouvement::factory()->create([
            'wallet_id' => $wallet->id,
            'montant_cents' => 100,
            'solde_avant_cents' => 1000,
            'solde_apres_cents' => 900,
        ]);

        $this->assertInstanceOf(WalletMouvement::class, $wallet->mouvements->first());
        $this->assertEquals($mouvement->id, $wallet->mouvements->first()->id);
    }
    /**
     * Teste que le solde d'un wallet ne peut pas être négatif (si une telle contrainte existe ou est simulée).
     * S'attend à une exception lors de la tentative de définition d'un solde négatif.
     */
    public function test_wallet_prevents_negative_balance()
    {
        $this->expectException(\Exception::class);

        $wallet = Wallet::factory()->create([
            'solde_cents' => 100,
        ]);

        // Tentative de créer un solde négatif
        $wallet->solde_cents = -50;
        $wallet->save();
    }

    /**
     * Teste la relation polymorphique avec le propriétaire (owner).
     * Vérifie que le wallet peut appartenir à un User (Client).
     */
    public function test_wallet_morph_to_owner()
    {
        $user = User::factory()->create();

        $wallet = Wallet::factory()->create([
            'owner_id' => $user->id,
            'owner_type' => Wallet::OWNER_TYPE_CLIENT,
        ]);

        $this->assertInstanceOf(User::class, $wallet->owner);
        $this->assertEquals($user->id, $wallet->owner->id);
    }



    /**
     * Teste l'accesseur 'solde'.
     * Vérifie que le solde est converti correctement depuis les centimes vers le format décimal/monétaire.
     */
    public function test_wallet_solde_accessor()
    {
        $wallet = Wallet::factory()->create([
            'solde_cents' => 1000, // 10.00 XOF
        ]);

        $solde = $wallet->solde;
        $this->assertIsString($solde);
        $this->assertStringContainsString('10.00', $solde);
    }

    /**
     * Teste l'accesseur 'solde_bloque'.
     * Vérifie que le solde bloqué est converti correctement depuis les centimes.
     */
    public function test_wallet_solde_bloque_accessor()
    {
        $wallet = Wallet::factory()->create([
            'solde_bloque_cents' => 500, // 5.00 XOF
        ]);

        $soldeBloque = $wallet->solde_bloque;
        $this->assertIsString($soldeBloque);
        $this->assertStringContainsString('5.00', $soldeBloque);
    }

    /**
     * Teste la méthode helper estActif().
     * Vérifie qu'elle retourne true si le statut est STATUT_ACTIF.
     */
    public function test_wallet_est_actif_method()
    {
        $walletActif = Wallet::factory()->create(['statut' => Wallet::STATUT_ACTIF]);
        $walletSuspendu = Wallet::factory()->create(['statut' => Wallet::STATUT_SUSPENDU]);

        $this->assertTrue($walletActif->estActif());
        $this->assertFalse($walletSuspendu->estActif());
    }

    /**
     * Teste la méthode helper estSuspendu().
     * Vérifie qu'elle retourne true si le statut est STATUT_SUSPENDU.
     */
    public function test_wallet_est_suspendu_method()
    {
        $walletSuspendu = Wallet::factory()->create(['statut' => Wallet::STATUT_SUSPENDU]);
        $walletActif = Wallet::factory()->create(['statut' => Wallet::STATUT_ACTIF]);

        $this->assertTrue($walletSuspendu->estSuspendu());
        $this->assertFalse($walletActif->estSuspendu());
    }

    /**
     * Teste la méthode helper estFerme().
     * Vérifie qu'elle retourne true si le statut est STATUT_FERME.
     */
    public function test_wallet_est_ferme_method()
    {
        $walletFerme = Wallet::factory()->create(['statut' => Wallet::STATUT_FERME]);
        $walletActif = Wallet::factory()->create(['statut' => Wallet::STATUT_ACTIF]);

        $this->assertTrue($walletFerme->estFerme());
        $this->assertFalse($walletActif->estFerme());
    }

    /**
     * Teste la méthode statique peutAvoirWallet().
     * Vérifie quels rôles ou types d'utilisateurs sont autorisés à posséder un wallet.
     */
    public function test_wallet_peut_avoir_wallet_method()
    {
        $this->assertTrue(Wallet::peutAvoirWallet('marchand'));
        $this->assertTrue(Wallet::peutAvoirWallet('client'));
        $this->assertTrue(Wallet::peutAvoirWallet('agent'));
        $this->assertFalse(Wallet::peutAvoirWallet('admin'));
        $this->assertFalse(Wallet::peutAvoirWallet('superadmin'));
    }
}
