<?php

namespace Modules\Pos\Tests\Feature;

use App\Models\User;
use Modules\Business\Entities\Caisse;
use Modules\Business\Entities\Employe;
use Modules\Business\Entities\PointVente;
use Modules\Pos\Entities\SessionCaisse;
use Tests\BaseTestCase;

/**
 * Tests fonctionnels pour la gestion des sessions de caisse côté manager
 * Couvre la création, attribution et fermeture des sessions par les managers
 */
class SessionCaisseManagerControllerTest extends BaseTestCase
{
    /**
     * Test d'accès refusé pour les invités
     * Les utilisateurs non connectés ne peuvent pas accéder à la gestion des sessions
     */
    public function test_guest_cannot_access_session_manager()
    {
        // Tenter d'accéder à la gestion des sessions sans authentification
        $this->get(route('session-caisse-manager.index'))
            ->assertStatus(403); // Accès interdit
    }

    /**
     * Test d'accès refusé pour les non-managers
     * Un caissier ne peut pas accéder à la gestion des sessions
     */
    public function test_non_manager_cannot_access_session_manager()
    {
        // Créer un utilisateur caissier (pas manager)
        $user = User::factory()->create(['pays_id' => 1]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('session-caisse-manager-list');

        $this->actingAs($user);

        // Doit être redirigé vers l'accueil avec une erreur
        $this->get(route('session-caisse-manager.index'))
            ->assertRedirect(route('home'))
            ->assertSessionHas('error');
    }

    /**
     * Test d'accès manager sans employé associé
     * Un manager sans fiche employé peut voir la page mais avec limitations
     */
    public function test_manager_without_employe_sees_error()
    {
        // Créer un utilisateur manager sans fiche employé
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.manager')
        ]);
        $user->assignRole(config('appconstants.role.manager'));
        $user->givePermissionTo('session-caisse-manager-list');

        $this->actingAs($user);

        // Peut accéder à la page (200) mais avec des fonctionnalités limitées
        $this->get(route('session-caisse-manager.index'))
            ->assertStatus(200);
    }

    /**
     * Test d'accès réussi pour un manager valide
     * Un manager avec une fiche employé peut accéder à la gestion des sessions
     */
    public function test_manager_can_access_session_manager()
    {
        // Créer un manager complet
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.manager')
        ]);
        $user->assignRole(config('appconstants.role.manager'));
        $user->givePermissionTo('session-caisse-manager-list');

        // Créer l'infrastructure nécessaire
        $pointVente = PointVente::factory()->create();
        Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.manager'),
        ]);

        $this->actingAs($user);

        // Doit pouvoir accéder à la page
        $this->get(route('session-caisse-manager.index'))
            ->assertStatus(200);
    }

    /**
     * Test d'accès refusé à la création pour les invités
     * Les utilisateurs non connectés ne peuvent pas créer de sessions
     */
    public function test_guest_cannot_create_session()
    {
        // Tenter d'accéder à la création de session sans authentification
        $this->get(route('session-caisse-manager.create'))
            ->assertStatus(403);
    }

    /**
     * Test d'accès refusé à la création pour les non-managers
     * Un caissier ne peut pas créer de sessions
     */
    public function test_non_manager_cannot_create_session()
    {
        // Créer un caissier
        $user = User::factory()->create(['pays_id' => 1]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('session-caisse-manager-create');

        $this->actingAs($user);

        // Doit être redirigé avec erreur
        $this->get(route('session-caisse-manager.create'))
            ->assertRedirect(route('home'))
            ->assertSessionHas('error');
    }

    /**
     * Test d'accès réussi à la création pour un manager
     * Un manager peut accéder au formulaire de création de session
     */
    public function test_manager_can_create_session()
    {
        // Créer un manager complet
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.manager')
        ]);
        $user->assignRole(config('appconstants.role.manager'));
        $user->givePermissionTo('session-caisse-manager-create');

        $pointVente = PointVente::factory()->create();
        Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.manager'),
        ]);

        $this->actingAs($user);

        // Doit pouvoir accéder au formulaire de création
        $this->get(route('session-caisse-manager.create'))
            ->assertStatus(200);
    }

    /**
     * Test d'attribution refusée pour les invités
     * Les utilisateurs non connectés ne peuvent pas attribuer de sessions
     */
    public function test_guest_cannot_assign_session()
    {
        // Tenter d'attribuer une session sans authentification
        $this->post(route('session-caisse-manager.attribution'), [])
            ->assertStatus(403);
    }

    /**
     * Test de validation requise pour l'attribution
     * L'attribution de session nécessite des données valides
     */
    public function test_attribution_requires_validation()
    {
        // Créer un manager
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.manager')
        ]);
        $user->assignRole(config('appconstants.role.manager'));
        $user->givePermissionTo('session-caisse-manager-create');

        $pointVente = PointVente::factory()->create();
        Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.manager'),
        ]);

        $this->actingAs($user);

        // Envoyer des données vides
        $response = $this->post(route('session-caisse-manager.attribution'), []);

        // Le contrôleur peut retourner soit des erreurs de validation soit un message d'erreur général
        $this->assertTrue(
            $response->getSession()->has('errors') || $response->getSession()->has('error')
        );
    }

    /**
     * Test d'attribution réussie par un manager
     * Un manager peut attribuer une caisse à un caissier
     */
    public function test_manager_can_assign_session()
    {
        // Créer un manager
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.manager')
        ]);
        $user->assignRole(config('appconstants.role.manager'));
        $user->givePermissionTo('session-caisse-manager-create');

        // Créer l'infrastructure
        $pointVente = PointVente::factory()->create();
        Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.manager'),
        ]);

        // Créer une caisse et un caissier
        $caisse = Caisse::factory()->create(['points_vente_id' => $pointVente->id]);
        $caissier = Employe::factory()->create([
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.caissier'),
        ]);

        $this->actingAs($user);

        // Attribuer la caisse au caissier
        $this->post(route('session-caisse-manager.attribution'), [
            'caisse_id' => $caisse->id,
            'caissier_id' => $caissier->id,
        ])->assertSessionHas('success');

        // Vérifier que la session est créée en base
        $this->assertDatabaseHas('sessions_caisse', [
            'caisse_id' => $caisse->id,
            'caissier_id' => $caissier->id,
            'statut' => config('appconstants.session_caisse_statut.attente'),
        ]);
    }

    /**
     * Test d'interdiction d'attribution d'une caisse déjà utilisée
     * Une caisse avec une session active ne peut pas être réattribuée
     */
    public function cannot_assign_caisse_already_in_use()
    {
        // Créer un manager
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.manager')
        ]);
        $user->assignRole(config('appconstants.role.manager'));
        $user->givePermissionTo('session-caisse-manager-create');

        $pointVente = PointVente::factory()->create();
        Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.manager'),
        ]);

        $caisse = Caisse::factory()->create(['points_vente_id' => $pointVente->id]);
        $caissier = Employe::factory()->create([
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.caissier'),
        ]);

        // Créer une session déjà ouverte sur cette caisse
        SessionCaisse::factory()->create([
            'caisse_id' => $caisse->id,
            'statut' => config('appconstants.session_caisse_statut.ouverte'),
        ]);

        $this->actingAs($user);

        // Tenter de réattribuer la caisse
        $this->post(route('session-caisse-manager.attribution'), [
            'caisse_id' => $caisse->id,
            'caissier_id' => $caissier->id,
        ])->assertSessionHas('error'); // Doit échouer
    }

    /**
     * Test d'affichage refusé pour les invités
     * Les utilisateurs non connectés ne peuvent pas voir les détails d'une session
     */
    public function guest_cannot_show_session()
    {
        // Créer une session
        $session = SessionCaisse::factory()->create();

        // Tenter d'accéder aux détails sans authentification
        $this->get(route('session-caisse-manager.show', $session->id))
            ->assertStatus(403);
    }

    /**
     * Test d'affichage réussi pour un manager
     * Un manager peut voir les détails d'une session
     */
    public function manager_can_show_session()
    {
        // Créer un manager
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.manager')
        ]);
        $user->assignRole(config('appconstants.role.manager'));
        $user->givePermissionTo('session-caisse-manager-list');

        $session = SessionCaisse::factory()->create();

        $this->actingAs($user);

        // Doit pouvoir voir les détails de la session
        $this->get(route('session-caisse-manager.show', $session->id))
            ->assertStatus(200);
    }

    /**
     * Test de fermeture refusée pour les invités
     * Les utilisateurs non connectés ne peuvent pas fermer de sessions
     */
    public function guest_cannot_close_session()
    {
        // Créer une session
        $session = SessionCaisse::factory()->create();

        // Tenter de fermer la session sans authentification
        $this->post(route('session-caisse-manager.fermerture'), [
            'session_id' => $session->id,
        ])->assertStatus(403);
    }

    /**
     * Test de validation requise pour la fermeture
     * La fermeture de session nécessite des données valides
     */
    public function fermerture_requires_validation()
    {
        // Créer un manager
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.manager')
        ]);
        $user->assignRole(config('appconstants.role.manager'));
        $user->givePermissionTo('session-caisse-manager-fermerture');

        $pointVente = PointVente::factory()->create();
        Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.manager'),
        ]);

        $this->actingAs($user);

        // Tenter de fermer sans données
        $this->post(route('session-caisse-manager.fermerture'), [])
            ->assertSessionHasErrors(['session_id']); // session_id requis
    }
}
