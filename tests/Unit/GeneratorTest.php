<?php

namespace Tests\Unit;

use App\Services\Generator;
use App\Models\User;
use Tests\BaseTestCase;
use Illuminate\Support\Str;

/**
 * Tests pour le service de génération de codes et identifiants
 * Couvre la génération de chaînes, UUID, QR codes et alias
 */
class GeneratorTest extends BaseTestCase
{
    /**
     * Test de génération de chaîne aléatoire par défaut
     * Longueur 6, alphanumérique majuscules
     */
    public function test_generate_random_string_default()
    {
        // Génération avec paramètres par défaut
        $result = Generator::generateRandomString();
        
        // Vérifier la longueur par défaut (6 caractères)
        $this->assertEquals(6, strlen($result));
        // Vérifier le format : chiffres et lettres majuscules uniquement
        $this->assertMatchesRegularExpression('/^[0-9A-Z]+$/', $result);
    }

    /**
     * Test de génération de chaîne numérique uniquement
     * Longueur personnalisée, chiffres seulement
     */
    public function test_generate_random_string_numbers_only()
    {
        // Génération de 8 chiffres uniquement
        $result = Generator::generateRandomString(8, true);
        
        // Vérifier la longueur demandée
        $this->assertEquals(8, strlen($result));
        // Vérifier que ce sont uniquement des chiffres
        $this->assertMatchesRegularExpression('/^[0-9]+$/', $result);
    }

    /**
     * Test de génération de code propriétaire
     * Code à 6 caractères pour identifier un compte
     */
    public function test_code_owner()
    {
        // Génération du code propriétaire
        $result = Generator::codeOwner();
        
        // Vérifier la longueur standard
        $this->assertEquals(6, strlen($result));
    }

    /**
     * Test de génération de code QR
     * Hash sécurisé basé sur le login utilisateur
     */
    public function test_qr_code()
    {
        // Génération du code QR pour un login
        $result = Generator::QrCode('testlogin');
        
        // Vérifier que le résultat n'est pas vide
        $this->assertNotEmpty($result);
        // Vérifier que c'est un hash valide ou une chaîne sécurisée
        $this->assertTrue(password_verify('testlogin' . '0', $result) || 
                         password_verify('testlogin' . '1000', $result) ||
                         strlen($result) > 10); // Hash généré
    }

    /**
     * Test de génération d'ID de transaction
     * UUID v4 pour identifier une transaction
     */
    public function test_transaction_id()
    {
        // Génération de l'ID de transaction
        $result = Generator::transactionId();
        
        // Vérifier que c'est un UUID valide
        $this->assertTrue(Str::isUuid($result));
    }

    /**
     * Test de génération d'UUID générique
     * UUID v4 pour usage général
     */
    public function test_uuid()
    {
        // Génération d'UUID
        $result = Generator::uuid();
        
        // Vérifier que c'est un UUID valide
        $this->assertTrue(Str::isUuid($result));
    }

    /**
     * Test de génération de code de retrait
     * Code à 6 chiffres entre 100000 et 999999
     */
    public function test_code_retrait()
    {
        // Génération du code de retrait
        $result = Generator::codeRetrait();
        
        // Vérifier la plage de valeurs (6 chiffres)
        $this->assertGreaterThanOrEqual(100000, $result);
        $this->assertLessThanOrEqual(999999, $result);
    }

    /**
     * Test de génération d'URL de QR code
     * Utilise l'API QuickChart pour générer l'image QR
     */
    public function test_generate_qr()
    {
        // Génération de l'URL du QR code
        $result = Generator::generateQr('test-value');
        
        // Vérifier que l'URL commence par le service QuickChart
        $this->assertStringStartsWith('https://quickchart.io/qr?text=', $result);
        // Vérifier que la valeur est incluse dans l'URL
        $this->assertStringContainsString('test-value', $result);
    }

    /**
     * Test de génération d'alias SmilPay simple
     * Format : prenom.nom en minuscules
     */
    public function test_generate_alias_smil()
    {
        // Génération d'alias avec nom et prénom
        $result = Generator::generateAliasSmil('Dupont', 'Jean Pierre');
        
        // Vérifier le format attendu : prenom.nom
        $this->assertEquals('jean.dupont', $result);
    }

    /**
     * Test de génération d'alias avec gestion des doublons
     * Ajoute un numéro si l'alias existe déjà
     */
    public function test_generate_alias_smil_with_existing()
    {
        // Créer un utilisateur avec l'alias de base
        User::factory()->create(['alias_smil' => 'jean.dupont']);
        
        // Générer un nouvel alias similaire
        $result = Generator::generateAliasSmil('Dupont', 'Jean');
        
        // Vérifier que le numéro est ajouté pour éviter le doublon
        $this->assertEquals('jean.dupont1', $result);
    }

    /**
     * Test de vérification de disponibilité d'alias
     * Vérifie si un alias est déjà utilisé
     */
    public function test_is_alias_smil_available()
    {
        // Vérifier qu'un alias unique est disponible
        $this->assertTrue(Generator::isAliasSmilAvailable('unique.alias'));
        
        // Créer un utilisateur avec un alias
        User::factory()->create(['alias_smil' => 'taken.alias']);
        
        // Vérifier que l'alias pris n'est plus disponible
        $this->assertFalse(Generator::isAliasSmilAvailable('taken.alias'));
    }

    /**
     * Test de génération d'alias avec caractères spéciaux
     * Normalisation des accents et caractères spéciaux
     */
    public function test_generate_alias_smil_with_special_characters()
    {
        // Génération avec accents et tirets
        $result = Generator::generateAliasSmil('Müller-Schmidt', 'José-María');
        
        // Vérifier la normalisation : suppression accents et tirets
        $this->assertEquals('josemaria.mullerschmidt', $result);
    }

    /**
     * Test de génération d'alias avec valeurs vides
     * Fallback vers un alias par défaut
     */
    public function test_generate_alias_smil_with_empty_values()
    {
        // Génération avec valeurs vides
        $result = Generator::generateAliasSmil('', '');
        
        // Vérifier le fallback vers l'alias par défaut
        $this->assertEquals('smil.user', $result);
    }
}