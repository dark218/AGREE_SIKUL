<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\ValidationService;
use Tests\BaseTestCase;
use Illuminate\Support\Facades\Hash;

/**
 * Tests de sécurité pour l'application SmilPay
 * Couvre les principales vulnérabilités OWASP
 */
class SecurityTest extends BaseTestCase
{
    /**
     * Test de sécurité du hachage des mots de passe
     * Vérifie que bcrypt est utilisé correctement
     */
    public function test_password_hashing_security()
    {
        // Mot de passe de test
        $password = 'test123';
        // Génération du hash avec bcrypt
        $hashedPassword = Hash::make($password);
        
        // Vérifier que le mot de passe peut être validé
        $this->assertTrue(Hash::check($password, $hashedPassword));
        // Vérifier que le hash n'est pas identique au mot de passe
        $this->assertNotEquals($password, $hashedPassword);
        // Vérifier que le hash bcrypt fait plus de 50 caractères
        $this->assertGreaterThan(50, strlen($hashedPassword));
    }

    /**
     * Test de prévention des injections SQL
     * Vérifie que les requêtes Eloquent sont sécurisées
     */
    public function test_sql_injection_prevention()
    {
        // Payload d'injection SQL classique
        $maliciousInput = "'; DROP TABLE users; --";
        
        // Tentative d'injection via une requête Eloquent
        $user = User::where('nom', $maliciousInput)->first();
        // Vérifier qu'aucun utilisateur n'est trouvé (pas d'injection)
        $this->assertNull($user);
        
        // Vérifier que la table users existe toujours
        $this->assertTrue(\Schema::hasTable('users'));
    }

    /**
     * Test de prévention XSS dans les données utilisateur
     * Vérifie le stockage et l'échappement des données
     */
    public function test_xss_prevention_in_user_data()
    {
        // Payload XSS classique
        $xssPayload = '<script>alert("XSS")</script>';
        
        // Créer un utilisateur avec du contenu malveillant
        $user = User::factory()->create([
            'nom' => $xssPayload,
            'prenoms' => $xssPayload
        ]);
        
        // Vérifier que les données sont stockées telles quelles
        $this->assertEquals($xssPayload, $user->nom);
        
        // Vérifier que l'échappement fonctionne
        $escaped = e($user->nom);
        $this->assertStringContainsString('&lt;script&gt;', $escaped);
    }

    /**
     * Test de non-exposition des données sensibles
     * Vérifie que les champs sensibles sont cachés
     */
    public function test_sensitive_data_not_exposed()
    {
        // Créer un utilisateur avec un mot de passe
        $user = User::factory()->create(['password' => Hash::make('secret123')]);
        
        // Convertir en tableau (simulation JSON/API)
        $userArray = $user->toArray();
        
        // Vérifier que le mot de passe n'est pas exposé
        $this->assertArrayNotHasKey('password', $userArray);
        // Vérifier que le token de session n'est pas exposé
        $this->assertArrayNotHasKey('remember_token', $userArray);
    }

    /**
     * Test de protection contre l'assignation de masse
     * Vérifie la configuration $fillable/$guarded du modèle
     */
    public function test_mass_assignment_protection()
    {
        // Créer une instance du modèle User
        $user = new User();
        
        // Récupérer la configuration de protection
        $fillable = $user->getFillable(); // Champs autorisés
        $guarded = $user->getGuarded();   // Champs protégés
        
        // Si $guarded contient '*', tous les champs sont protégés
        if (in_array('*', $guarded)) {
            $this->assertTrue(true, 'Mass assignment protection active avec guarded *');
        } else {
            // Sinon vérifier que les champs sensibles ne sont pas fillable
            $sensitiveFields = ['role', 'validated_by', 'is_admin'];
            foreach ($sensitiveFields as $field) {
                // Échec si un champ sensible est dans $fillable
                if (in_array($field, $fillable)) {
                    $this->fail("Champ sensible '$field' est fillable");
                }
            }
            $this->assertTrue(true, 'Champs sensibles protégés');
        }
    }

    /**
     * Test des contrôles d'autorisation
     * Vérifie que les rôles utilisateur fonctionnent correctement
     */
    public function test_authorization_checks()
    {
        // Créer un utilisateur normal
        $regularUser = User::factory()->create(['role' => 'client']);
        // Créer un administrateur
        $adminUser = User::factory()->create(['role' => 'admin']);
        
        // Vérifier que l'utilisateur normal n'a pas de privilèges admin
        $this->assertFalse($regularUser->isSuperAdmin());
        $this->assertFalse($regularUser->isAdmin());
        // Vérifier que l'admin a bien les privilèges
        $this->assertTrue($adminUser->isAdmin());
    }
}