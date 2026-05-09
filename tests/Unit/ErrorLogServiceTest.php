<?php

namespace Tests\Unit;

use App\Services\ErrorLogService;
use Modules\Administration\Entities\ErrorLog;
use Tests\BaseTestCase;
use Illuminate\Support\Facades\Log;

/**
 * Tests pour le service de journalisation des erreurs
 * Couvre l'enregistrement et la gestion des erreurs applicatives
 */
class ErrorLogServiceTest extends BaseTestCase
{
    /**
     * Test de création d'un log d'erreur
     * Vérifie l'enregistrement correct des erreurs en base de données
     */
    public function test_save_creates_error_log()
    {
        // Enregistrer une erreur via le service
        ErrorLogService::save('TestModule', 'testMethod', 'Test error message');

        // Vérifier que l'erreur est enregistrée en base
        $this->assertDatabaseHas('errorlogs', [
            'module' => 'TestModule',           // Module concerné
            'methode' => 'testMethod',          // Méthode qui a généré l'erreur
            'message' => 'Test error message'   // Message d'erreur
        ]);
    }

    /**
     * Test de gestion des exceptions de base de données
     * Vérifie que les erreurs de sauvegarde sont logées
     */
    public function test_save_handles_database_exception()
    {
        // Mock du système de log pour capturer les erreurs
        Log::shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/Failed to save error log:/'));

        // Forcer une exception en utilisant un module trop long (dépassement de limite)
        ErrorLogService::save(str_repeat('x', 300), 'testMethod', 'Test message');
    }

    /**
     * Test de sauvegarde avec des valeurs vides
     * Vérifie que le service accepte les valeurs vides
     */
    public function test_save_with_empty_values()
    {
        // Enregistrer une erreur avec des valeurs vides
        ErrorLogService::save('', '', '');

        // Vérifier que l'enregistrement fonctionne même avec des valeurs vides
        $this->assertDatabaseHas('errorlogs', [
            'module' => '',     // Module vide
            'methode' => '',    // Méthode vide
            'message' => ''     // Message vide
        ]);
    }
}