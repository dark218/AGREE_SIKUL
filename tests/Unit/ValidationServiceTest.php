<?php

namespace Tests\Unit;

use App\Services\ValidationService;
use App\Services\SMSApiProService;
use App\Models\User;
use Tests\BaseTestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Mockery;

/**
 * Tests pour le service de validation KYC et gestion des utilisateurs
 * Couvre la validation, rejet, suspension et blocage des comptes
 */
class ValidationServiceTest extends BaseTestCase
{
    /**
     * Configuration initiale des tests
     * Mock des services et création des données de test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un utilisateur admin pour les clés étrangères
        $adminUser = User::factory()->create(['id' => 1]);
        
        // Configuration des constantes de statut KYC
        Config::set('appconstants.user_kyc_status.verifie', 'verifie');
        Config::set('appconstants.user_kyc_status.rejete', 'rejete');
        Config::set('appconstants.user_kyc_status.en_attente', 'en_attente');
        Config::set('appconstants.user_kyc_status.non_verifie', 'non_verifie');
        
        // Configuration des constantes de statut utilisateur
        Config::set('appconstants.user_statut.actif', 'actif');
        Config::set('appconstants.user_statut.suspendu', 'suspendu');
        Config::set('appconstants.user_statut.bloque', 'bloque');
        
        // Mock complet du système d'authentification
        $mockGuard = Mockery::mock();
        $mockGuard->shouldReceive('check')->andReturn(true);
        $mockGuard->shouldReceive('user')->andReturn($adminUser);
        
        // Mock des méthodes Auth principales
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($adminUser);
        Auth::shouldReceive('guard')->andReturn($mockGuard);
    }

    /**
     * Test de validation KYC réussie
     * Passage de 'en_attente' à 'verifie' avec activation du compte
     */
    public function test_validate_kyc_success()
    {
        // Créer un utilisateur en attente de validation
        $user = User::factory()->create([
            'kyc_status' => 'en_attente',
            'statut' => 'suspendu'
        ]);

        // Mock du service SMS pour les notifications
        $this->mock(SMSApiProService::class, function ($mock) {
            $mock->shouldReceive('sendNewSms')->zeroOrMoreTimes();
        });

        // Exécuter la validation KYC
        $result = ValidationService::validateKyc($user);

        // Vérifier le succès de l'opération
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['message']);
        
        // Vérifier les changements en base de données
        $user->refresh();
        $this->assertEquals('verifie', $user->kyc_status);      // KYC validé
        $this->assertEquals('actif', $user->statut);            // Compte activé
        $this->assertNotNull($user->validated_at);             // Date de validation
        $this->assertEquals(1, $user->validated_by);           // Validateur enregistré
    }

    /**
     * Test de rejet KYC avec motif
     * Passage à 'rejete' avec suspension du compte
     */
    public function test_reject_kyc_success()
    {
        // Créer un utilisateur en attente
        $user = User::factory()->create([
            'kyc_status' => 'en_attente',
            'statut' => 'actif'
        ]);

        // Mock du service SMS
        $this->mock(SMSApiProService::class, function ($mock) {
            $mock->shouldReceive('sendNewSms')->zeroOrMoreTimes();
        });

        // Exécuter le rejet avec motif
        $result = ValidationService::rejectKyc($user, 'Documents invalides');

        // Vérifier le succès
        $this->assertTrue($result['success']);
        
        // Vérifier les changements
        $user->refresh();
        $this->assertEquals('rejete', $user->kyc_status);           // KYC rejeté
        $this->assertEquals('suspendu', $user->statut);             // Compte suspendu
        $this->assertEquals('Documents invalides', $user->motif);   // Motif enregistré
    }

    /**
     * Test de rejet KYC sans motif
     * Doit échouer car le motif est obligatoire
     */
    public function test_reject_kyc_without_motif()
    {
        // Créer un utilisateur
        $user = User::factory()->create();

        // Tenter de rejeter sans motif
        $result = ValidationService::rejectKyc($user, '');

        // Vérifier l'échec
        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['message']);
    }

    /**
     * Test de suspension d'utilisateur
     * Passage du statut à 'suspendu' avec motif
     */
    public function test_suspend_user_success()
    {
        // Créer un utilisateur actif
        $user = User::factory()->create(['statut' => 'actif']);

        // Mock du service SMS
        $this->mock(SMSApiProService::class, function ($mock) {
            $mock->shouldReceive('sendNewSms')->zeroOrMoreTimes();
        });

        // Exécuter la suspension
        $result = ValidationService::suspendUser($user, 'Activité suspecte');

        // Vérifier le succès
        $this->assertTrue($result['success']);
        
        // Vérifier les changements
        $user->refresh();
        $this->assertEquals('suspendu', $user->statut);             // Statut suspendu
        $this->assertEquals('Activité suspecte', $user->motif);    // Motif enregistré
        $this->assertEquals(1, $user->suspended_by);               // Suspendeur enregistré
    }

    /**
     * Test de blocage d'utilisateur
     * Passage du statut à 'bloque' avec motif
     */
    public function test_block_user_success()
    {
        // Créer un utilisateur actif
        $user = User::factory()->create(['statut' => 'actif']);

        // Mock du service SMS
        $this->mock(SMSApiProService::class, function ($mock) {
            $mock->shouldReceive('sendNewSms')->zeroOrMoreTimes();
        });

        // Exécuter le blocage
        $result = ValidationService::blockUser($user, 'Fraude détectée');

        // Vérifier le succès
        $this->assertTrue($result['success']);
        
        // Vérifier les changements
        $user->refresh();
        $this->assertEquals('bloque', $user->statut);           // Statut bloqué
        $this->assertEquals('Fraude détectée', $user->motif);  // Motif enregistré
        $this->assertEquals(1, $user->blocked_by);              // Bloqueur enregistré
    }

    /**
     * Test de mise en attente KYC
     * Passage de 'non_verifie' à 'en_attente'
     */
    public function test_set_kyc_en_attente()
    {
        // Créer un utilisateur non vérifié
        $user = User::factory()->create(['kyc_status' => 'non_verifie']);

        // Mettre en attente
        $result = ValidationService::setKycEnAttente($user);

        // Vérifier le succès
        $this->assertTrue($result['success']);
        
        // Vérifier le changement de statut
        $user->refresh();
        $this->assertEquals('en_attente', $user->kyc_status);
    }

    /**
     * Test de mise en attente d'un utilisateur déjà vérifié
     * Ne doit pas changer le statut si déjà vérifié
     */
    public function test_set_kyc_en_attente_already_verified()
    {
        // Créer un utilisateur déjà vérifié
        $user = User::factory()->create(['kyc_status' => 'verifie']);

        // Tenter de mettre en attente
        $result = ValidationService::setKycEnAttente($user);

        // Vérifier le succès (pas d'erreur)
        $this->assertTrue($result['success']);
        
        // Vérifier que le statut n'a pas changé
        $user->refresh();
        $this->assertEquals('verifie', $user->kyc_status); // Pas de changement
    }

    /**
     * Nettoyage après chaque test
     * Fermeture des mocks Mockery
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}