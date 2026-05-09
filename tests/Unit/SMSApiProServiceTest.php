<?php

namespace Tests\Unit;

use App\Services\SMSApiProService;
use Tests\BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

/**
 * Tests pour le service d'envoi de SMS via l'API SMSPro
 * Couvre l'envoi d'OTP et de messages personnalisés
 */
class SMSApiProServiceTest extends BaseTestCase
{
    /**
     * Configuration initiale des tests
     * Mock des variables d'environnement et configuration API
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configuration des variables d'environnement pour les tests
        config([
            'app.env' => 'testing',
            'SMS_OTP_SECONDE_TIME_OUT' => 300,
            'SMSPRO_API_AUTHORIZATION' => 'Bearer test-token',
            'SMSPRO_API_URL' => 'https://api.test.com/sms',
            'SMSPRO_SENDER_NAME' => 'TestSender'
        ]);
        
        // Configuration des variables d'environnement globales
        $_ENV['SMS_OTP_SECONDE_TIME_OUT'] = '300';      // Timeout OTP en secondes
        $_ENV['SMSPRO_API_AUTHORIZATION'] = 'Bearer test-token';  // Token d'auth API
        $_ENV['SMSPRO_API_URL'] = 'https://api.test.com/sms';     // URL de l'API
        $_ENV['SMSPRO_SENDER_NAME'] = 'TestSender';               // Nom de l'expéditeur
    }

    /**
     * Test d'envoi d'OTP SMS réussi
     * Vérifie la génération et l'envoi d'un code OTP
     */
    public function test_send_sms_otp_success()
    {
        // Mock d'une réponse API réussie
        Http::fake([
            '*' => Http::response(['status' => 'success'], 200)
        ]);

        // Envoyer un OTP au numéro de test
        $result = SMSApiProService::sendSmsOTP('+225123456789');

        // Vérifier que l'OTP est généré (entier entre 1000 et 9999)
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(1000, $result);
        $this->assertLessThanOrEqual(9999, $result);
    }

    /**
     * Test d'envoi d'OTP SMS échoué
     * Vérifie la gestion des erreurs API
     */
    public function test_send_sms_otp_failure()
    {
        // Mock d'une réponse API d'échec
        Http::fake([
            '*' => Http::response(['error' => 'Failed'], 400)
        ]);

        // Tenter d'envoyer un OTP
        $result = SMSApiProService::sendSmsOTP('+225123456789');

        // Vérifier que null est retourné en cas d'échec
        $this->assertNull($result);
    }

    /**
     * Test d'envoi de SMS personnalisé réussi
     * Vérifie l'envoi d'un message texte libre
     */
    public function test_send_new_sms_success()
    {
        // Mock d'une réponse API réussie
        Http::fake([
            '*' => Http::response(['status' => 'success'], 200)
        ]);

        // Envoyer un message personnalisé
        $result = SMSApiProService::sendNewSms('+225123456789', 'Test message');

        // Vérifier le succès de l'envoi
        $this->assertTrue($result);
    }

    /**
     * Test d'envoi de SMS personnalisé échoué
     * Vérifie la gestion des erreurs serveur
     */
    public function test_send_new_sms_failure()
    {
        // Mock d'une réponse API d'erreur serveur
        Http::fake([
            '*' => Http::response(['error' => 'Failed'], 500)
        ]);

        // Tenter d'envoyer un message
        $result = SMSApiProService::sendNewSms('+225123456789', 'Test message');

        // Vérifier l'échec de l'envoi
        $this->assertFalse($result);
    }

    /**
     * Test du formatage des numéros de téléphone
     * Vérifie que les numéros internationaux sont acceptés
     */
    public function test_phone_number_formatting()
    {
        // Mock d'une réponse API réussie
        Http::fake([
            '*' => Http::response(['status' => 'success'], 200)
        ]);

        // Envoyer avec un numéro au format international
        $result = SMSApiProService::sendNewSms('+225123456789', 'Test');

        // Vérifier que l'envoi fonctionne avec ce format
        $this->assertTrue($result);
    }
}