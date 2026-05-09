<?php

namespace Tests\Unit;

use App\Services\MoneyService;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour le service de gestion monétaire
 * Teste les conversions entre devises avec et sans décimales
 */
class MoneyServiceTest extends TestCase
{
    /**
     * Test de détection des devises sans décimales (XOF)
     * Le franc CFA n'utilise pas de centimes
     */
    public function test_has_no_decimals_for_xof_currency()
    {
        // XOF (Franc CFA) n'a pas de décimales
        $this->assertTrue(MoneyService::hasNoDecimals('XOF'));
        // Test insensible à la casse
        $this->assertTrue(MoneyService::hasNoDecimals('xof'));
    }

    /**
     * Test de détection des devises avec décimales (EUR, USD)
     * Ces devises utilisent des centimes
     */
    public function test_has_decimals_for_eur_currency()
    {
        // EUR et USD ont des décimales
        $this->assertFalse(MoneyService::hasNoDecimals('EUR'));
        $this->assertFalse(MoneyService::hasNoDecimals('USD'));
    }

    /**
     * Test de conversion vers la base de données pour XOF
     * XOF : stockage direct sans multiplication par 100
     */
    public function test_to_database_with_xof_currency()
    {
        // Valeur entière directe
        $this->assertEquals(1000, MoneyService::toDatabase('1000', 'XOF'));
        // Arrondi automatique des décimales
        $this->assertEquals(1501, MoneyService::toDatabase('1500,50', 'XOF'));
        // Gestion des valeurs vides
        $this->assertEquals(0, MoneyService::toDatabase('', 'XOF'));
        $this->assertEquals(0, MoneyService::toDatabase(null, 'XOF'));
    }

    /**
     * Test de conversion vers la base de données pour EUR
     * EUR : multiplication par 100 pour stocker en centimes
     */
    public function test_to_database_with_eur_currency()
    {
        // 10.50 EUR = 1050 centimes
        $this->assertEquals(1050, MoneyService::toDatabase('10.50', 'EUR'));
        // Support virgule française
        $this->assertEquals(1050, MoneyService::toDatabase('10,50', 'EUR'));
        // 1000 EUR = 100000 centimes
        $this->assertEquals(100000, MoneyService::toDatabase('1000', 'EUR'));
        // Gestion des valeurs vides
        $this->assertEquals(0, MoneyService::toDatabase('', 'EUR'));
    }

    /**
     * Test d'affichage pour XOF
     * Format avec espaces comme séparateurs de milliers
     */
    public function test_to_display_with_xof_currency()
    {
        // 1000 -> "1 000" (séparateur d'espace)
        $this->assertEquals('1 000', MoneyService::toDisplay(1000, 'XOF'));
        // 1500 -> "1 500"
        $this->assertEquals('1 500', MoneyService::toDisplay(1500, 'XOF'));
    }

    /**
     * Test d'affichage pour EUR
     * Format avec décimales et séparateurs
     */
    public function test_to_display_with_eur_currency()
    {
        // 1050 centimes -> "10.50" EUR
        $this->assertEquals('10.50', MoneyService::toDisplay(1050, 'EUR'));
        // 100000 centimes -> "1 000.00" EUR
        $this->assertEquals('1 000.00', MoneyService::toDisplay(100000, 'EUR'));
    }
}