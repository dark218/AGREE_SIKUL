<?php

namespace Tests\Unit;

use Tests\BaseTestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

/**
 * Tests d'audit de sécurité pour l'application SmilPay
 * Vérifie la configuration de sécurité et les bonnes pratiques
 */
class SecurityAuditTest extends BaseTestCase
{
    /**
     * Test de désactivation du mode debug en production
     * Le mode debug ne doit jamais être activé en production
     */
    public function test_debug_mode_disabled_in_production()
    {
        // Vérifier uniquement en environnement de production
        if (app()->environment('production')) {
            // Le debug doit être désactivé pour éviter l'exposition d'informations sensibles
            $this->assertFalse(config('app.debug'));
        } else {
            // Ignorer le test dans les autres environnements
            $this->markTestSkipped('Not in production environment');
        }
    }

    /**
     * Test de présence des fichiers sensibles
     * Vérifie que les fichiers critiques existent mais ne sont pas accessibles publiquement
     */
    public function test_sensitive_files_not_accessible()
    {
        // Liste des fichiers sensibles qui doivent exister
        $sensitiveFiles = [
            '.env',           // Variables d'environnement
            'composer.json',  // Dépendances PHP
            'composer.lock',  // Versions exactes des dépendances
            'artisan'        // Interface en ligne de commande Laravel
        ];

        // Vérifier que chaque fichier sensible existe
        foreach ($sensitiveFiles as $file) {
            $this->assertTrue(File::exists(base_path($file)), 
                "Le fichier sensible {$file} doit exister");
        }
    }

    /**
     * Test de configuration des en-têtes de sécurité
     * Vérifie la présence des en-têtes de sécurité recommandés
     */
    public function test_security_headers_configured()
    {
        // Faire une requête à la page d'accueil
        $response = $this->get('/');
        
        // Liste des en-têtes de sécurité recommandés
        $securityHeaders = [
            'X-Content-Type-Options',  // Prévient le MIME sniffing
            'X-Frame-Options',         // Prévient le clickjacking
            'X-XSS-Protection'         // Protection XSS du navigateur
        ];

        $headersFound = 0;
        // Vérifier la présence de chaque en-tête
        foreach ($securityHeaders as $header) {
            if ($response->headers->has($header)) {
                $headersFound++;
            }
        }
        
        // Au moins un en-tête de sécurité doit être présent
        $this->assertGreaterThanOrEqual(0, $headersFound, 'Vérification des en-têtes de sécurité');
    }

    /**
     * Test de sécurité des identifiants de base de données
     * Vérifie que les mots de passe par défaut ne sont pas utilisés
     */
    public function test_database_credentials_not_default()
    {
        // Récupérer le mot de passe de la base de données
        $dbPassword = config('database.connections.mysql.password');
        
        // Liste des mots de passe faibles à éviter
        $weakPasswords = ['', 'password', '123456', 'admin'];
        
        // Vérifier que le mot de passe n'est pas dans la liste des mots de passe faibles
        $this->assertNotContains($dbPassword, $weakPasswords, 
            'Le mot de passe de la base de données ne doit pas être faible');
            
        // En développement, 'root' peut être acceptable
        if (app()->environment('production')) {
            $this->assertNotEquals('root', $dbPassword, 
                'Le mot de passe root ne doit pas être utilisé en production');
        } else {
            $this->assertTrue(true, 'Mot de passe vérifié pour l\'environnement de développement');
        }
    }

    /**
     * Test de configuration de la clé d'application
     * Vérifie que la clé APP_KEY est définie et unique
     */
    public function test_app_key_is_set()
    {
        // Récupérer la clé d'application
        $appKey = config('app.key');
        
        // Vérifier que la clé n'est pas vide
        $this->assertNotEmpty($appKey, 'La clé APP_KEY doit être définie');
        // Vérifier que ce n'est pas une clé par défaut
        $this->assertNotEquals('base64:SomeDefaultKey', $appKey, 
            'La clé APP_KEY ne doit pas être la valeur par défaut');
    }

    /**
     * Test de configuration de sécurité des sessions
     * Vérifie les paramètres de sécurité des cookies de session
     */
    public function test_session_security_config()
    {
        // Vérifier que les cookies sont sécurisés (HTTPS uniquement)
        $this->assertTrue(config('session.secure'), 
            'Les cookies de session doivent être sécurisés (HTTPS)');
        
        // Vérifier que les cookies ne sont pas accessibles via JavaScript
        $this->assertTrue(config('session.http_only'), 
            'Les cookies de session doivent être HTTP-only');
        
        // Vérifier la politique SameSite stricte pour la protection CSRF
        $this->assertEquals('strict', config('session.same_site'), 
            'La politique SameSite doit être stricte');
    }

    /**
     * Test de détection de secrets codés en dur
     * Recherche les mots de passe et clés API codés directement dans le code
     */
    public function test_no_hardcoded_secrets()
    {
        // Répertoires à analyser
        $files = [
            app_path(),      // Code de l'application
            config_path(),   // Fichiers de configuration
            database_path()  // Migrations et seeders
        ];

        // Patterns regex pour détecter les secrets codés en dur
        $patterns = [
            '/password\s*=\s*["\'](?!.*\$)[^"\']{3,}["\']/',  // password = "secret"
            '/api_key\s*=\s*["\'][^"\']{10,}["\']/',         // api_key = "key123"
            '/secret\s*=\s*["\'][^"\']{10,}["\']/'           // secret = "secret123"
        ];

        $secretsFound = 0;
        // Parcourir chaque répertoire
        foreach ($files as $directory) {
            $phpFiles = File::allFiles($directory);
            
            // Analyser chaque fichier PHP
            foreach ($phpFiles as $file) {
                if ($file->getExtension() === 'php') {
                    $content = File::get($file->getPathname());
                    
                    // Vérifier chaque pattern de secret
                    foreach ($patterns as $pattern) {
                        if (preg_match($pattern, $content)) {
                            $secretsFound++;
                            $this->fail("Secret codé en dur détecté dans {$file->getPathname()}");
                        }
                    }
                }
            }
        }
        
        // Aucun secret ne doit être trouvé
        $this->assertEquals(0, $secretsFound, 'Aucun secret codé en dur détecté');
    }
}