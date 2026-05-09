<?php

namespace Modules\GestionStock\Tests\Feature;

use App\Models\User;
use Modules\Business\Entities\Employe;
use Modules\Business\Entities\Marchand;
use Modules\Business\Entities\PointVente;
use Modules\GestionStock\Entities\TransfertStock;
use Tests\BaseTestCase;

/**
 * Tests fonctionnels pour la gestion des transferts de stock
 * Couvre les permissions d'accès, création et validation des transferts
 */
class TransfertStockBasicControllerTest extends BaseTestCase
{
    // ==================== TESTS D'ACCÈS À LA LISTE ====================

    /**
     * Test d'accès refusé à la liste pour les invités
     * Les utilisateurs non connectés ne peuvent pas voir les transferts
     */
    public function test_guest_cannot_access_transfert_list()
    {
        // Tenter d'accéder à la liste des transferts sans authentification
        $this->get(route('transfert-stock.index'))
            ->assertStatus(403); // Accès interdit
    }

    /**
     * Test d'accès refusé pour utilisateur sans permission
     * Un utilisateur sans la permission appropriée ne peut pas voir les transferts
     */
    public function test_user_without_permission_cannot_access_transfert_list()
    {
        // Créer un caissier sans permission de transfert
        $user = User::factory()->create(['pays_id' => 1]);
        $user->assignRole(config('appconstants.role.caissier'));
        // Pas de permission 'transfert-stock-list'

        $this->actingAs($user);

        // Doit être refusé
        $this->get(route('transfert-stock.index'))
            ->assertStatus(403);
    }

    /**
     * Test d'accès réussi pour un administrateur
     * Un admin peut voir tous les transferts de stock
     */
    public function test_admin_can_access_transfert_list()
    {
        // Créer un administrateur avec permission
        $user = User::factory()->create(['pays_id' => 1]);
        $user->assignRole(config('appconstants.role.admin'));
        $user->givePermissionTo('transfert-stock-list');

        $this->actingAs($user);

        // Doit pouvoir accéder à la liste
        $this->get(route('transfert-stock.index'))
            ->assertStatus(200);
    }

    /**
     * Test d'accès réussi pour un marchand
     * Un marchand peut voir les transferts de ses points de vente
     */
    public function test_marchand_can_access_transfert_list()
    {
        // Créer un marchand avec permission
        $user = User::factory()->create(['pays_id' => 1]);
        $user->assignRole(config('appconstants.role.marchand'));
        $user->givePermissionTo('transfert-stock-list');

        // Créer l'entité Marchand associée (obligatoire)
        \Modules\Business\Entities\Marchand::factory()->create([
            'proprietaire_id' => $user->id,
        ]);

        $this->actingAs($user);

        // Doit pouvoir accéder à la liste
        $this->get(route('transfert-stock.index'))
            ->assertStatus(200);
    }

    /**
     * Test d'accès réussi pour un manager
     * Un manager peut voir les transferts de son point de vente
     */
    public function test_manager_can_access_transfert_list()
    {
        // Créer un manager avec permission
        $user = User::factory()->create(['pays_id' => 1]);
        $user->assignRole(config('appconstants.role.manager'));
        $user->givePermissionTo('transfert-stock-list');

        // Créer l'infrastructure nécessaire
        $pointVente = PointVente::factory()->create();
        Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.manager'),
        ]);

        $this->actingAs($user);

        // Doit pouvoir accéder à la liste
        $this->get(route('transfert-stock.index'))
            ->assertStatus(200);
    }

    // ==================== TESTS D'ACCÈS À LA CRÉATION ====================

    /**
     * Test d'accès refusé à la création pour les invités
     * Les utilisateurs non connectés ne peuvent pas créer de transferts
     */
    public function test_guest_cannot_access_transfert_create()
    {
        // Tenter d'accéder à la création sans authentification
        $this->get(route('transfert-stock.create'))
            ->assertStatus(403);
    }

    /**
     * Test d'accès refusé à la création pour un caissier
     * Les caissiers ne peuvent pas créer de transferts de stock
     */
    public function test_caissier_cannot_access_transfert_create()
    {
        // Créer un caissier avec permission
        $user = User::factory()->create(['pays_id' => 1]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('transfert-stock-create');

        Employe::factory()->create([
            'users_id' => $user->id,
            'type_employe' => config('appconstants.type_employe.caissier'),
        ]);

        $this->actingAs($user);

        // Doit voir une erreur (les caissiers ne peuvent pas créer de transferts)
        $this->get(route('transfert-stock.create'))
            ->assertSessionHas('error');
    }

    /**
     * Test d'accès réussi à la création pour un marchand
     * Un marchand peut créer des transferts entre ses points de vente
     */
    public function test_marchand_can_access_transfert_create()
    {
        // Créer un marchand avec permission
        $user = User::factory()->create(['pays_id' => 1]);
        $user->assignRole(config('appconstants.role.marchand'));
        $user->givePermissionTo('transfert-stock-create');

        // Créer l'entité Marchand associée
        Marchand::factory()->create([
            'proprietaire_id' => $user->id,
        ]);

        $this->actingAs($user);

        // Doit pouvoir accéder au formulaire de création
        $this->get(route('transfert-stock.create'))
            ->assertStatus(200);
    }

    /**
     * Test d'accès réussi à la création pour un manager
     * Un manager peut créer des transferts pour son point de vente
     */
    public function test_manager_can_access_transfert_create()
    {
        // Créer un manager avec permission
        $user = User::factory()->create(['pays_id' => 1]);
        $user->assignRole(config('appconstants.role.manager'));
        $user->givePermissionTo('transfert-stock-create');

        $pointVente = PointVente::factory()->create();
        Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.manager'),
        ]);

        $this->actingAs($user);

        // Doit pouvoir accéder au formulaire de création
        $this->get(route('transfert-stock.create'))
            ->assertStatus(200);
    }

    // ==================== TESTS D'ENREGISTREMENT DE TRANSFERT ====================

    /**
     * Test d'enregistrement refusé pour les invités
     * Les utilisateurs non connectés ne peuvent pas enregistrer de transferts
     */
    public function test_guest_cannot_store_transfert()
    {
        // Tenter d'enregistrer un transfert sans authentification
        $this->post(route('transfert-stock.store'), [])
            ->assertStatus(403);
    }

    /**
     * Test d'enregistrement refusé pour un caissier
     * Les caissiers ne peuvent pas enregistrer de transferts
     */
    public function test_caissier_cannot_store_transfert()
    {
        // Créer un caissier
        $user = User::factory()->create(['pays_id' => 1]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('transfert-stock-create');

        Employe::factory()->create([
            'users_id' => $user->id,
            'type_employe' => config('appconstants.type_employe.caissier'),
        ]);

        $this->actingAs($user);

        // Doit voir une erreur
        $this->post(route('transfert-stock.store'), [])
            ->assertSessionHas('error');
    }

    /**
     * Test de validation requise pour l'enregistrement
     * L'enregistrement d'un transfert nécessite des données valides
     */
    public function test_store_transfert_requires_validation()
    {
        // Créer un manager avec permission
        $user = User::factory()->create(['pays_id' => 1]);
        $user->assignRole(config('appconstants.role.manager'));
        $user->givePermissionTo('transfert-stock-create');

        $pointVente = PointVente::factory()->create();
        Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.manager'),
        ]);

        $this->actingAs($user);

        // Envoyer des données vides
        $this->post(route('transfert-stock.store'), [])
            ->assertSessionHasErrors([
                'emplacement_source_id',      // Emplacement source requis
                'emplacement_destination_id', // Emplacement destination requis
                'lignes',                     // Lignes de transfert requises
            ]);
    }

    // ==================== TESTS D'AFFICHAGE DE TRANSFERT ====================

    /**
     * Test d'affichage refusé pour les invités
     * Les utilisateurs non connectés ne peuvent pas voir les détails d'un transfert
     */
    public function test_guest_cannot_show_transfert()
    {
        // Créer un transfert
        $transfert = TransfertStock::factory()->create();

        // Tenter d'accéder aux détails sans authentification
        $this->get(route('transfert-stock.show', $transfert->id))
            ->assertStatus(403);
    }

    /**
     * Test d'affichage réussi pour un administrateur
     * Un admin peut voir les détails de tous les transferts
     */
    public function test_admin_can_show_transfert()
    {
        // Créer un administrateur
        $user = User::factory()->create(['pays_id' => 1]);
        $user->assignRole(config('appconstants.role.admin'));
        $user->givePermissionTo('transfert-stock-list');

        $transfert = TransfertStock::factory()->create();

        $this->actingAs($user);

        // Doit pouvoir voir les détails
        $this->get(route('transfert-stock.show', $transfert->id))
            ->assertStatus(200);
    }

    // ==================== TESTS DE VALIDATION DE TRANSFERT ====================

    /**
     * Test de validation refusée pour les invités
     * Les utilisateurs non connectés ne peuvent pas valider de transferts
     */
    public function test_guest_cannot_validate_transfert()
    {
        // Créer un transfert
        $transfert = TransfertStock::factory()->create();

        // Tenter de valider sans authentification
        $this->put(route('transfert-stock.validate', $transfert->id), [])
            ->assertStatus(403);
    }

    /**
     * Test de validation requise pour la validation de transfert
     * La validation d'un transfert nécessite des données valides
     */
    public function test_validate_transfert_requires_validation()
    {
        // Créer un administrateur
        $user = User::factory()->create(['pays_id' => 1]);
        $user->assignRole(config('appconstants.role.admin'));
        $user->givePermissionTo('transfert-stock-validate');

        // Créer un transfert en cours
        $transfert = TransfertStock::factory()->create([
            'statut' => TransfertStock::STATUT_EN_COURS,
        ]);

        $this->actingAs($user);

        // Tenter de valider sans données
        $this->put(route('transfert-stock.validate', $transfert->id), [])
            ->assertSessionHasErrors([
                'lignes', // Lignes de validation requises
            ]);
    }
}
