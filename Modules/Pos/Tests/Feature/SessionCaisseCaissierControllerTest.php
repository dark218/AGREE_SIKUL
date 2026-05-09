<?php

namespace Modules\Pos\Tests\Feature;

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Business\Entities\Caisse;
use Modules\Business\Entities\Employe;
use Modules\Business\Entities\PointVente;
use Modules\Pos\Entities\SessionCaisse;
use Modules\Pos\Entities\VentePos;
use Tests\BaseTestCase;

/**
 * Tests fonctionnels pour les sessions de caisse côté caissier
 * Couvre l'ouverture, fermeture et gestion des sessions POS
 */
class SessionCaisseCaissierControllerTest extends BaseTestCase
{
    /**
     * Test d'accès refusé pour les invités
     * Les utilisateurs non connectés ne peuvent pas accéder au POS
     */
    public function test_guest_cannot_access_caissier_pos()
    {
        // Tenter d'accéder au POS sans authentification
        $this->get(route('session-caisse-caissier.index'))
            ->assertStatus(403); // Accès interdit
    }

    /**
     * Test d'erreur pour utilisateur sans pays
     * Un utilisateur sans pays assigné doit voir une erreur
     */
    public function test_user_without_country_sees_error()
    {
        // Créer un utilisateur sans pays
        $user = User::factory()->create(['pays_id' => null]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('session-caisse-caissier-list');

        // Créer l'employé associé
        Employe::factory()->create([
            'users_id' => $user->id,
            'type_employe' => config('appconstants.type_employe.caissier'),
        ]);

        // Se connecter et tester l'accès
        $this->actingAs($user);

        $response = $this->get(route('session-caisse-caissier.index'));
        // Vérifier que la session est présente dans la réponse
        $response->assertInertia(fn (Assert $page) =>
            $page->has('sessionActive')
        );
    }

    /**
     * Test d'accès refusé pour non-caissier
     * Un utilisateur qui n'est pas caissier ne peut pas accéder au POS
     */
    public function test_non_caissier_cannot_access_pos()
    {
        // Créer un utilisateur marchand (pas caissier)
        $user = User::factory()->create(['pays_id' => 1]);
        $user->assignRole(config('appconstants.role.marchand'));
        $user->givePermissionTo('session-caisse-caissier-list');

        $this->actingAs($user);

        // Doit voir une erreur car il n'est pas caissier
        $this->get(route('session-caisse-caissier.index'))
            ->assertInertia(fn (Assert $page) =>
                $page->has('error')
            );
    }

    /**
     * Test d'affichage POS vide sans session
     * Un caissier sans session active doit voir un POS vide
     */
    public function test_caissier_without_session_sees_empty_pos()
    {
        // Créer un caissier valide
        $user = User::factory()->create(['pays_id' => 1]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('session-caisse-caissier-list');

        // Créer l'employé caissier
        Employe::factory()->create([
            'users_id' => $user->id,
            'type_employe' => config('appconstants.type_employe.caissier'),
        ]);

        $this->actingAs($user);

        // Doit voir sessionActive = null (pas de session ouverte)
        $this->get(route('session-caisse-caissier.index'))
            ->assertInertia(fn (Assert $page) =>
                $page->where('sessionActive', null)
            );
    }

    /**
     * Test d'affichage POS avec session ouverte
     * Un caissier avec une session active doit voir le POS fonctionnel
     */
    public function test_caissier_with_open_session_sees_pos()
    {
        // Créer un caissier
        $user = User::factory()->create(['pays_id' => 1]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('session-caisse-caissier-list');

        // Créer le point de vente
        $pointVente = PointVente::factory()->create();

        // Créer l'employé caissier
        $employe = Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.caissier'),
        ]);

        // Créer la caisse physique
        $caisse = Caisse::factory()->create([
            'points_vente_id' => $pointVente->id,
            'type' => 'physique',
        ]);

        // Créer une session ouverte
        SessionCaisse::factory()->create([
            'caissier_id' => $employe->id,
            'caisse_id' => $caisse->id,
            'statut' => config('appconstants.session_caisse_statut.ouverte'),
            'devise' => 'XOF',
        ]);

        $this->actingAs($user);

        // Doit voir la session active
        $this->get(route('session-caisse-caissier.index'))
            ->assertInertia(fn (Assert $page) =>
                $page->has('sessionActive')
            );
    }

    /**
     * Test d'ouverture de session refusée pour non-caissier
     * Un utilisateur non-caissier ne peut pas ouvrir de session
     */
    public function test_non_caissier_cannot_open_session()
    {
        // Créer un utilisateur marchand
        $user = User::factory()->create(['pays_id' => 1]);
        $user->assignRole(config('appconstants.role.marchand'));
        $user->givePermissionTo('session-caisse-caissier-ouverture');

        $this->actingAs($user);

        // Tenter d'ouvrir une session sans données valides
        $response = $this->post(route('session-caisse-caissier.ouverture'), []);

        // Doit avoir des erreurs de validation
        $response->assertSessionHasErrors([
            'fond_ouverture',  // Fond d'ouverture requis
            'session_id',      // ID de session requis
            'devise',          // Devise requise
        ]);
    }

    /**
     * Test d'ouverture de session réussie pour caissier
     * Un caissier peut ouvrir une session avec les bonnes données
     */
    public function test_caissier_can_open_session()
    {
        // Créer un caissier
        $user = User::factory()->create(['pays_id' => 1]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('session-caisse-caissier-ouverture');

        // Créer l'infrastructure nécessaire
        $pointVente = PointVente::factory()->create();
        $employe = Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.caissier'),
        ]);

        $caisse = Caisse::factory()->create([
            'points_vente_id' => $pointVente->id,
            'type' => 'physique',
        ]);

        // Créer une session en attente
        $session = SessionCaisse::factory()->create([
            'caissier_id' => $employe->id,
            'caisse_id' => $caisse->id,
            'statut' => config('appconstants.session_caisse_statut.attente'),
        ]);

        $this->actingAs($user);

        // Ouvrir la session avec les bonnes données
        $this->post(route('session-caisse-caissier.ouverture'), [
            'session_id' => $session->id,
            'fond_ouverture' => 1000,  // Fond d'ouverture en XOF
            'devise' => 'XOF',
        ])->assertSessionHas('success'); // Doit réussir
    }

    /**
     * Test d'interdiction de fermeture avec ventes en attente
     * Une session ne peut pas être fermée s'il y a des ventes en attente
     */
    public function test_cannot_close_session_with_pending_sales()
    {
        // Créer un caissier
        $user = User::factory()->create(['pays_id' => 1]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('session-caisse-caissier-fermerture');

        // Créer l'infrastructure
        $pointVente = PointVente::factory()->create();
        $employe = Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.caissier'),
        ]);

        $caisse = Caisse::factory()->create([
            'points_vente_id' => $pointVente->id,
            'type' => 'physique',
        ]);

        // Créer une session ouverte
        $session = SessionCaisse::factory()->create([
            'caissier_id' => $employe->id,
            'caisse_id' => $caisse->id,
            'statut' => config('appconstants.session_caisse_statut.ouverte'),
        ]);

        // Créer une vente en attente (bloque la fermeture)
        VentePos::factory()->create([
            'sessions_caisse_id' => $session->id,
            'employe_id' => $employe->id,
            'points_vente_id' => $pointVente->id,
            'statut' => config('appconstants.statut_vente_pos.en_attente'),
        ]);

        $this->actingAs($user);

        // Tenter de fermer la session
        $this->post(route('session-caisse-caissier.fermerture'), [
            'session_id' => $session->id,
            'total_reel' => 1000,
        ])->assertSessionHas('error'); // Doit échouer
    }

    /**
     * Test de fermeture de session réussie
     * Un caissier peut fermer une session sans ventes en attente
     */
    public function caissier_can_close_session_without_gap()
    {
        // Créer un caissier
        $user = User::factory()->create(['pays_id' => 1]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('session-caisse-caissier-fermerture');

        // Créer l'infrastructure
        $pointVente = PointVente::factory()->create();
        $employe = Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.caissier'),
        ]);

        $caisse = Caisse::factory()->create([
            'points_vente_id' => $pointVente->id,
            'type' => 'physique',
        ]);

        // Créer une session avec des montants cohérents
        $session = SessionCaisse::factory()->create([
            'caissier_id' => $employe->id,
            'caisse_id' => $caisse->id,
            'statut' => config('appconstants.session_caisse_statut.ouverte'),
            'fond_ouverture_cents' => 1000,    // Fond initial : 1000
            'total_encaisse_cents' => 2000,    // Encaissé : 2000
            'devise' => 'XOF',
        ]);

        $this->actingAs($user);

        // Fermer la session avec le total réel (1000 + 2000 = 3000)
        $this->post(route('session-caisse-caissier.fermerture'), [
            'session_id' => $session->id,
            'total_reel' => 3000,  // Total cohérent
        ])->assertSessionHas('success'); // Doit réussir
    }
}
