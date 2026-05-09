<?php

namespace Modules\Wallet\Tests\Unit;

use App\Models\User;
use App\Services\MoneyService;
use Modules\Business\Entities\PointVente;
use Modules\Wallet\Entities\Wallet;
use Modules\Wallet\Entities\WalletMouvement;
use Tests\BaseTestCase;

class WalletMouvementTest extends BaseTestCase
{
    /**
     * Teste la création d'un mouvement de wallet avec des données valides.
     * Vérifie que les données sont correctement enregistrées dans la base de données.
     */
    public function test_wallet_mouvement_creation()
    {
        $mouvement = WalletMouvement::factory()->create([
            'type_mouvement' => WalletMouvement::TYPE_CREDIT,
            'montant_cents' => MoneyService::toDatabase(100, 'XOF'),
        ]);

        $this->assertDatabaseHas('wallet_mouvements', [
            'id' => $mouvement->id,
            'type_mouvement' => WalletMouvement::TYPE_CREDIT,
            'montant_cents' => MoneyService::toDatabase(100, 'XOF'),
        ]);
    }

    /**
     * Teste la relation entre un mouvement et un wallet.
     * Vérifie que le mouvement est bien lié au wallet créé.
     */
    public function test_wallet_mouvement_belongs_to_wallet()
    {
        $wallet = Wallet::factory()->create();
        $mouvement = WalletMouvement::factory()->create(['wallet_id' => $wallet->id]);

        $this->assertInstanceOf(Wallet::class, $mouvement->wallet);
        $this->assertEquals($wallet->id, $mouvement->wallet->id);
    }

    /**
     * Teste la relation entre un mouvement et un utilisateur.
     * Vérifie que le mouvement est bien lié à l'utilisateur spécifié.
     */
    public function test_wallet_mouvement_belongs_to_user()
    {
        $user = User::factory()->create();
        $mouvement = WalletMouvement::factory()->create(['users_id' => $user->id]);

        $this->assertInstanceOf(User::class, $mouvement->user);
        $this->assertEquals($user->id, $mouvement->user->id);
    }

    /**
     * Teste la relation entre un mouvement et un emplacement (Point de Vente).
     * Vérifie que le mouvement est bien lié à l'emplacement spécifié.
     */
    public function test_wallet_mouvement_belongs_to_emplacement()
    {
        $pointVente = PointVente::factory()->create();
        $mouvement = WalletMouvement::factory()->create(['emplacement_id' => $pointVente->id]);

        $this->assertInstanceOf(PointVente::class, $mouvement->emplacement);
        $this->assertEquals($pointVente->id, $mouvement->emplacement->id);
    }

    /**
     * Teste l'accesseur pour l'attribut 'montant'.
     * Vérifie que le montant est formaté correctement (en devise) lorsqu'on y accède.
     */
    public function test_wallet_mouvement_montant_accessor()
    {
        $mouvement = WalletMouvement::factory()->create([
            'montant_cents' => MoneyService::toDatabase(250, 'XOF'),
        ]);

        $montant = $mouvement->montant;
        $this->assertIsString($montant);
        $this->assertMatchesRegularExpression('/2[.,]50/', $montant);

    }

    /**
     * Teste la méthode helper estCredit().
     * Vérifie qu'elle retourne true pour un mouvement de type CREDIT et false sinon.
     */
    public function test_wallet_mouvement_est_credit_method()
    {
        $mouvementCredit = WalletMouvement::factory()->create(['type_mouvement' => WalletMouvement::TYPE_CREDIT]);
        $mouvementDebit = WalletMouvement::factory()->create(['type_mouvement' => WalletMouvement::TYPE_DEBIT]);

        $this->assertTrue($mouvementCredit->estCredit());
        $this->assertFalse($mouvementDebit->estCredit());
    }

    /**
     * Teste la méthode helper estDebit().
     * Vérifie qu'elle retourne true pour un mouvement de type DEBIT et false sinon.
     */
    public function test_wallet_mouvement_est_debit_method()
    {
        $mouvementDebit = WalletMouvement::factory()->create(['type_mouvement' => WalletMouvement::TYPE_DEBIT]);
        $mouvementCredit = WalletMouvement::factory()->create(['type_mouvement' => WalletMouvement::TYPE_CREDIT]);

        $this->assertTrue($mouvementDebit->estDebit());
        $this->assertFalse($mouvementCredit->estDebit());
    }

    /**
     * Teste la méthode helper estBlocage().
     * Vérifie qu'elle retourne true pour un mouvement de type BLOCAGE et false sinon.
     */
    public function test_wallet_mouvement_est_blocage_method()
    {
        $mouvementBlocage = WalletMouvement::factory()->create(['type_mouvement' => WalletMouvement::TYPE_BLOCAGE]);
        $mouvementCredit = WalletMouvement::factory()->create(['type_mouvement' => WalletMouvement::TYPE_CREDIT]);

        $this->assertTrue($mouvementBlocage->estBlocage());
        $this->assertFalse($mouvementCredit->estBlocage());
    }

    /**
     * Teste la méthode helper estDeblocage().
     * Vérifie qu'elle retourne true pour un mouvement de type DEBLOCAGE et false sinon.
     */
    public function test_wallet_mouvement_est_deblocage_method()
    {
        $mouvementDeblocage = WalletMouvement::factory()->create(['type_mouvement' => WalletMouvement::TYPE_DEBLOCAGE]);
        $mouvementBlocage = WalletMouvement::factory()->create(['type_mouvement' => WalletMouvement::TYPE_BLOCAGE]);

        $this->assertTrue($mouvementDeblocage->estDeblocage());
        $this->assertFalse($mouvementBlocage->estDeblocage());
    }
}
